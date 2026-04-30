<?php

namespace App\Livewire;

use App\Models\InventoryAccountingAccount;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryItemManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    public $itemId;

    // Form fields
    public $name = '';
    public $inventory_accounting_account_id = '';
    public $initial_value = 0;
    public $acquisition_date = '';
    public $supplier_id = null;
    public $state = 'bueno';
    public $current_tag = '';
    public $location = '';
    public $funding_source = '';
    public $inventory_type = 'devolutivo';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'inventory_accounting_account_id' => 'required|exists:inventory_accounting_accounts,id',
            'initial_value' => 'required|numeric|min:0',
            'acquisition_date' => 'nullable|date',
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(function ($query) {
                    $query->where('school_id', session('selected_school_id'));
                }),
            ],
            'state' => ['required', Rule::in(array_keys(InventoryItem::STATES))],
            'current_tag' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'funding_source' => 'nullable|string|max:100',
            'inventory_type' => ['required', Rule::in(array_keys(InventoryItem::INVENTORY_TYPES))],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'name.required' => 'La descripción es obligatoria.',
        'inventory_accounting_account_id.required' => 'Debe seleccionar una cuenta contable.',
        'initial_value.required' => 'El valor es obligatorio.',
        'initial_value.min' => 'El valor no puede ser negativo.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_items.view'), 403);
    }

    public function getItemsProperty()
    {
        return InventoryItem::with(['account', 'supplier', 'discharge'])
            ->forSchool(session('selected_school_id'))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->latest()
            ->paginate(15);
    }

    public function getAccountsProperty()
    {
        return InventoryAccountingAccount::active()->orderBy('code')->get();
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
        if (!auth()->user()->can('inventory_items.create')) {
            $this->dispatch('toast', message: 'No tiene permisos para crear artículos.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('inventory_items.edit')) {
            $this->dispatch('toast', message: 'No tiene permisos para editar artículos.', type: 'error');
            return;
        }

        $item = InventoryItem::forSchool(session('selected_school_id'))->findOrFail($id);
        
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->inventory_accounting_account_id = $item->inventory_accounting_account_id;
        $this->initial_value = $item->initial_value;
        $this->acquisition_date = $item->acquisition_date ? $item->acquisition_date->format('Y-m-d') : '';
        $this->supplier_id = $item->supplier_id;
        $this->state = $item->state;
        $this->current_tag = $item->current_tag;
        $this->location = $item->location;
        $this->funding_source = $item->funding_source;
        $this->inventory_type = $item->inventory_type;
        $this->is_active = $item->is_active;
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'inventory_items.edit' : 'inventory_items.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tiene permisos para realizar esta acción.', type: 'error');
            return;
        }

        $this->validate();

        InventoryItem::updateOrCreate(
            [
                'id' => $this->itemId,
                'school_id' => session('selected_school_id'),
            ],
            [
                'name' => $this->name,
                'inventory_accounting_account_id' => $this->inventory_accounting_account_id,
                'initial_value' => $this->initial_value,
                'acquisition_date' => $this->acquisition_date ?: null,
                'supplier_id' => $this->supplier_id ?: null,
                'state' => $this->state,
                'current_tag' => $this->current_tag,
                'location' => $this->location,
                'funding_source' => $this->funding_source,
                'inventory_type' => $this->inventory_type,
                'is_active' => $this->is_active,
            ]
        );

        $this->dispatch('toast', message: $this->isEditing ? 'Artículo actualizado.' : 'Artículo creado.', type: 'success');
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->inventory_accounting_account_id = '';
        $this->initial_value = 0;
        $this->acquisition_date = '';
        $this->supplier_id = null;
        $this->state = 'bueno';
        $this->current_tag = '';
        $this->location = '';
        $this->funding_source = '';
        $this->inventory_type = 'devolutivo';
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
        return view('livewire.inventory-item-management');
    }
}
