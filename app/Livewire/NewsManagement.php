<?php

namespace App\Livewire;

use App\Jobs\SendNewsNotificationJob;
use App\Models\News;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.app')]
class NewsManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public string $search = '';
    public string $filterPublished = '';

    // Modal state
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $newsId = null;

    // Form fields
    public string $title = '';
    public string $description = '';
    public $file = null;
    public bool $for_all_schools = true;
    public array $selectedSchools = [];
    public bool $is_published = true;

    // Current file info (when editing)
    public ?string $currentFilePath = null;
    public ?string $currentFileType = null;
    public ?string $currentOriginalFilename = null;
    public bool $removeFile = false;

    // Delete modal
    public bool $showDeleteModal = false;
    public ?int $itemToDelete = null;

    // Schools list for multi-select
    public array $schoolsList = [];

    protected $queryString = [
        'search'          => ['except' => ''],
        'filterPublished' => ['except' => ''],
    ];

    protected function rules(): array
    {
        $fileRule = $this->isEditing ? 'nullable' : 'nullable';

        return [
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'file'           => [$fileRule, 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
            'for_all_schools'=> 'boolean',
            'selectedSchools'=> 'array',
            'is_published'   => 'boolean',
        ];
    }

    protected $messages = [
        'title.required'   => 'El título es obligatorio.',
        'file.mimes'       => 'El archivo debe ser una imagen (jpg, png, gif, webp) o un PDF.',
        'file.max'         => 'El archivo no debe superar los 10 MB.',
        'selectedSchools'  => 'Seleccione al menos un colegio cuando no es para todos.',
    ];

    public function mount(): void
    {
        abort_if(!auth()->user()->can('news.view'), 403);

        $this->schoolsList = School::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getNewsProperty()
    {
        return News::with(['creator', 'schools'])
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->filterPublished !== '', fn ($q) => $q->where('is_published', (bool) $this->filterPublished))
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    public function openCreateModal(): void
    {
        abort_if(!auth()->user()->can('news.create'), 403);

        $this->resetForm();
        $this->showModal  = true;
        $this->isEditing  = false;
    }

    public function openEditModal(int $id): void
    {
        abort_if(!auth()->user()->can('news.edit'), 403);

        $news = News::with('schools')->findOrFail($id);

        $this->newsId                = $news->id;
        $this->title                 = $news->title;
        $this->description           = $news->description ?? '';
        $this->for_all_schools       = $news->for_all_schools;
        $this->selectedSchools       = $news->schools->pluck('id')->toArray();
        $this->is_published          = $news->is_published;
        $this->currentFilePath       = $news->file_path;
        $this->currentFileType       = $news->file_type;
        $this->currentOriginalFilename = $news->original_filename;
        $this->removeFile            = false;
        $this->file                  = null;

        $this->showModal = true;
        $this->isEditing = true;
    }

    public function save(): void
    {
        if ($this->isEditing) {
            abort_if(!auth()->user()->can('news.edit'), 403);
        } else {
            abort_if(!auth()->user()->can('news.create'), 403);
        }

        $this->validate();

        if (!$this->for_all_schools && empty($this->selectedSchools)) {
            $this->addError('selectedSchools', 'Seleccione al menos un colegio.');
            return;
        }

        $filePath        = $this->currentFilePath;
        $fileType        = $this->currentFileType;
        $originalFilename = $this->currentOriginalFilename;

        // Handle file removal
        if ($this->removeFile && $filePath) {
            Storage::disk('public')->delete($filePath);
            $filePath        = null;
            $fileType        = null;
            $originalFilename = null;
        }

        // Handle new file upload
        if ($this->file) {
            // Delete old file if replacing
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $mime = $this->file->getMimeType();
            $fileType = str_starts_with($mime, 'image/') ? 'image' : 'pdf';
            $originalFilename = $this->file->getClientOriginalName();
            $filePath = $this->file->store('news', 'public');
        }

        $data = [
            'title'             => $this->title,
            'description'       => $this->description ?: null,
            'file_path'         => $filePath,
            'file_type'         => $fileType,
            'original_filename' => $originalFilename,
            'for_all_schools'   => $this->for_all_schools,
            'is_published'      => $this->is_published,
        ];

        $wasPublished = false;

        if ($this->isEditing) {
            $news = News::findOrFail($this->newsId);
            $wasPublished = !$news->is_published && $this->is_published;
            $news->update($data);
        } else {
            $data['created_by'] = auth()->id();
            $news = News::create($data);
            $wasPublished = $this->is_published;
        }

        // Sync schools
        if ($this->for_all_schools) {
            $news->schools()->detach();
        } else {
            $news->schools()->sync($this->selectedSchools);
        }

        // Send email notification when publishing
        if ($wasPublished) {
            SendNewsNotificationJob::dispatch($news->id)->onQueue('default');
        }

        $this->closeModal();
        $this->dispatch('toast', message: $this->isEditing ? 'Noticia actualizada correctamente.' : 'Noticia publicada correctamente.', type: 'success');
    }

    public function togglePublished(int $id): void
    {
        abort_if(!auth()->user()->can('news.edit'), 403);

        $news = News::findOrFail($id);
        $wasUnpublished = !$news->is_published;
        $news->update(['is_published' => !$news->is_published]);
        $news->refresh();

        // Send email when toggling ON (unpublished → published)
        if ($wasUnpublished && $news->is_published) {
            SendNewsNotificationJob::dispatch($news->id)->onQueue('default');
        }

        $status = $news->is_published ? 'publicada' : 'despublicada';
        $this->dispatch('toast', message: "Noticia {$status}.", type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        abort_if(!auth()->user()->can('news.delete'), 403);

        $this->itemToDelete    = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_if(!auth()->user()->can('news.delete'), 403);

        $news = News::findOrFail($this->itemToDelete);

        if ($news->file_path) {
            Storage::disk('public')->delete($news->file_path);
        }

        $news->delete();

        $this->showDeleteModal = false;
        $this->itemToDelete    = null;

        $this->dispatch('toast', message: 'Noticia eliminada.', type: 'success');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->itemToDelete    = null;
    }

    private function resetForm(): void
    {
        $this->newsId                = null;
        $this->title                 = '';
        $this->description           = '';
        $this->file                  = null;
        $this->for_all_schools       = true;
        $this->selectedSchools       = [];
        $this->is_published          = true;
        $this->currentFilePath       = null;
        $this->currentFileType       = null;
        $this->currentOriginalFilename = null;
        $this->removeFile            = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.news-management');
    }
}
