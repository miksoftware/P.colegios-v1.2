<?php

namespace App\Livewire;

use App\Models\News;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NewsViewer extends Component
{
    use WithPagination;

    public ?int $schoolId = null;

    // Modal viewer
    public bool $showViewer = false;
    public ?News $selectedNews = null;

    public function mount(): void
    {
        abort_if(!auth()->user()->can('news.view'), 403);

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
        }
    }

    public function getNewsProperty()
    {
        return News::with('creator')
            ->published()
            ->forSchool($this->schoolId)
            ->orderByDesc('created_at')
            ->paginate(9);
    }

    public function openNews(int $id): void
    {
        $this->selectedNews = News::published()
            ->forSchool($this->schoolId)
            ->findOrFail($id);

        $this->showViewer = true;
    }

    public function closeViewer(): void
    {
        $this->showViewer   = false;
        $this->selectedNews = null;
    }

    public function render()
    {
        return view('livewire.news-viewer');
    }
}
