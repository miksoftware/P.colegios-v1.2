<?php

namespace App\Livewire;

use App\Models\InventoryEntry;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryEntryManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    public $entryId;

    public $date = '';
    public $supplier_id = null;
    public $invoice_number = '';
    public $observations = '';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function rules()
    {
        return [
            'date' => 'required|date',
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(function ($query) {
                    $query->where('school_id', session('selected_school_id'));
                }),
            ],
            'invoice_number' => 'nullable|string|max:50',
            'observations' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_entries.view'), 403);
    }

    public function getEntriesProperty()
    {
        return InventoryEntry::with(['supplier'])
            ->forSchool(session('selected_school_id'))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->latest()
            ->paginate(15);
    }

    public function getSuppliersProperty()
    {
        return Supplier::forSchool(session('selected_school_id'))->active()->orderBy('first_surname')->orderBy('first_name')->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('inventory_entries.create')) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->date = date('Y-m-d');
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('inventory_entries.edit')) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }

        $entry = InventoryEntry::forSchool(session('selected_school_id'))->findOrFail($id);
        
        $this->entryId = $entry->id;
        $this->date = $entry->date ? $entry->date->format('Y-m-d') : '';
        $this->supplier_id = $entry->supplier_id;
        $this->invoice_number = $entry->invoice_number;
        $this->observations = $entry->observations;
        $this->is_active = $entry->is_active;
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'inventory_entries.edit' : 'inventory_entries.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tiene permisos.', type: 'error');
            return;
        }

        $this->validate();

        $entry = InventoryEntry::updateOrCreate(
            [
                'id' => $this->entryId,
                'school_id' => session('selected_school_id'),
            ],
            [
                'date' => $this->date,
                'supplier_id' => $this->supplier_id ?: null,
                'invoice_number' => $this->invoice_number,
                'observations' => $this->observations,
                'is_active' => $this->is_active,
            ]
        );

        $this->dispatch('toast', message: $this->isEditing ? 'Entrada actualizada.' : 'Entrada creada.', type: 'success');
        $this->closeModal();

        if (!$this->isEditing) {
            // Redirect to detail page to add items
            return redirect()->route('inventory.entries.details', $entry->id);
        }
    }

    public function resetForm()
    {
        $this->entryId = null;
        $this->date = '';
        $this->supplier_id = null;
        $this->invoice_number = '';
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
        return view('livewire.inventory-entry-management');
    }
}
