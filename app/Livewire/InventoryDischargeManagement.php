<?php

namespace App\Livewire;

use App\Models\InventoryDischarge;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryDischargeManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    public $dischargeId;

    public $date = '';
    public $resolution_number = '';
    public $observations = '';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function rules()
    {
        return [
            'date' => 'required|date',
            'resolution_number' => 'nullable|string|max:50',
            'observations' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_discharges.view'), 403);
    }

    public function getDischargesProperty()
    {
        return InventoryDischarge::forSchool(session('selected_school_id'))
            ->withCount('items')
            ->when($this->search, fn($q) => $q->search($this->search))
            ->latest()
            ->paginate(15);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('inventory_discharges.create')) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->date = date('Y-m-d');
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('inventory_discharges.edit')) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }

        $discharge = InventoryDischarge::forSchool(session('selected_school_id'))->findOrFail($id);
        
        // Bloquear edición si ya tiene artículos
        if ($discharge->items()->count() > 0) {
            $this->dispatch('toast', message: 'Esta resolución ya tiene artículos asignados y no se puede editar. Debe revertir los artículos primero.', type: 'warning');
            return;
        }

        $this->dischargeId = $discharge->id;
        $this->date = $discharge->date ? $discharge->date->format('Y-m-d') : '';
        $this->resolution_number = $discharge->resolution_number;
        $this->observations = $discharge->observations;
        $this->is_active = $discharge->is_active;
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'inventory_discharges.edit' : 'inventory_discharges.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }

        $this->validate();

        $discharge = InventoryDischarge::updateOrCreate(
            [
                'id' => $this->dischargeId,
                'school_id' => session('selected_school_id'),
            ],
            [
                'date' => $this->date,
                'resolution_number' => $this->resolution_number,
                'observations' => $this->observations,
                'is_active' => $this->is_active,
            ]
        );

        $this->dispatch('toast', message: $this->isEditing ? 'Baja actualizada.' : 'Comprobante de Baja creado.', type: 'success');
        $this->closeModal();

        if (!$this->isEditing) {
            // Redirect to detail page to add items
            return redirect()->route('inventory.discharges.details', $discharge->id);
        }
    }

    public function resetForm()
    {
        $this->dischargeId = null;
        $this->date = '';
        $this->resolution_number = '';
        $this->observations = '';
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-discharge-management');
    }
}
