<?php

namespace App\Livewire;

use App\Models\BudgetItem;
use App\Models\FundingSource;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class FundingSourceManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Filtros
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $perPage = 15;

    // Modal
    public $showModal = false;
    public $isEditing = false;
    public $fundingSourceId = null;

    // Campos del formulario
    public $budget_item_id = '';
    public $name = '';
    public $type = 'internal';
    public $description = '';
    public $is_active = true;

    // Modal eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Rubros disponibles
    public $budgetItems = [];

    protected function rules()
    {
        return [
            'budget_item_id' => 'required|exists:budget_items,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,external',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'name.required' => 'El nombre es obligatorio.',
        'type.required' => 'Debe seleccionar el tipo.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('funding_sources.view'), 403);

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            $school = auth()->user()->hasRole('Admin')
                ? \App\Models\School::first()
                : auth()->user()->schools()->first();

            if ($school) {
                session(['selected_school_id' => $school->id]);
                $this->schoolId = $school->id;
            } else {
                session()->flash('error', 'Debes seleccionar un colegio primero.');
                $this->redirect(route('dashboard'));

                return;
            }
        }

        $this->loadBudgetItems();
    }

    public function loadBudgetItems()
    {
        $this->budgetItems = BudgetItem::forSchool($this->schoolId)
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => "{$item->code} - {$item->name}",
            ])
            ->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getFundingSourcesProperty()
    {
        return FundingSource::forSchool($this->schoolId)
            ->with('budgetItem')
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->filterType, fn ($q) => $q->byType($this->filterType))
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('funding_sources.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear fuentes.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function editFundingSource($id)
    {
        if (!auth()->user()->can('funding_sources.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar fuentes.', type: 'error');

            return;
        }

        $source = FundingSource::forSchool($this->schoolId)->findOrFail($id);

        $this->fundingSourceId = $source->id;
        $this->budget_item_id = $source->budget_item_id;
        $this->name = $source->name;
        $this->type = $source->type;
        $this->description = $source->description;
        $this->is_active = $source->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'funding_sources.edit' : 'funding_sources.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');

            return;
        }

        $this->validate();

        $data = [
            'school_id' => $this->schoolId,
            'budget_item_id' => $this->budget_item_id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $source = FundingSource::forSchool($this->schoolId)->findOrFail($this->fundingSourceId);
            $source->update($data);
            $this->dispatch('toast', message: 'Fuente actualizada exitosamente.', type: 'success');
        } else {
            FundingSource::create($data);
            $this->dispatch('toast', message: 'Fuente creada exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('funding_sources.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar fuentes.', type: 'error');

            return;
        }

        $this->itemToDelete = FundingSource::forSchool($this->schoolId)->with('budgetItem')->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteFundingSource()
    {
        if (!auth()->user()->can('funding_sources.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar fuentes.', type: 'error');

            return;
        }

        if ($this->itemToDelete) {
            $name = $this->itemToDelete->name;
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: "Fuente '{$name}' eliminada exitosamente.", type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('funding_sources.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar fuentes.', type: 'error');

            return;
        }

        $source = FundingSource::forSchool($this->schoolId)->findOrFail($id);
        $source->update(['is_active' => !$source->is_active]);

        $status = $source->is_active ? 'activada' : 'desactivada';
        $this->dispatch('toast', message: "Fuente {$status} exitosamente.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function resetForm()
    {
        $this->fundingSourceId = null;
        $this->budget_item_id = '';
        $this->name = '';
        $this->type = 'internal';
        $this->description = '';
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterType', 'filterStatus']);
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.funding-source-management');
    }
}
