<?php

namespace App\Livewire;

use App\Models\InventoryAccountingAccount;
use App\Models\InventoryEntry;
use App\Models\InventoryItem;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InventoryEntryDetail extends Component
{
    public InventoryEntry $entry;
    
    public $showCreateModal = false;
    public $showSelectModal = false;

    // Campos para creación múltiple
    public $quantity = 1;
    public $name = '';
    public $inventory_accounting_account_id = '';
    public $initial_value = 0;
    public $state = 'bueno';
    public $location = '';
    public $funding_source = '';
    public $inventory_type = 'devolutivo';
    public $base_tag = '';

    // Campos para selección existente
    public $selectedItems = [];

    public function mount(InventoryEntry $entry)
    {
        abort_if(!auth()->user()->can('inventory_entries.view'), 403);
        
        // Validar que la entrada pertenezca al colegio actual
        abort_if($entry->school_id !== session('selected_school_id'), 404);

        $this->entry = $entry;
    }

    public function getAccountsProperty()
    {
        return InventoryAccountingAccount::active()->orderBy('code')->get();
    }

    public function getAvailableItemsProperty()
    {
        // Artículos del colegio que no tienen entrada asignada
        return InventoryItem::forSchool(session('selected_school_id'))
            ->whereNull('inventory_entry_id')
            ->orderBy('name')
            ->get();
    }

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function openSelectModal()
    {
        $this->selectedItems = [];
        $this->showSelectModal = true;
    }

    public function resetCreateForm()
    {
        $this->quantity = 1;
        $this->name = '';
        $this->inventory_accounting_account_id = '';
        $this->initial_value = 0;
        $this->state = 'bueno';
        $this->location = '';
        $this->funding_source = '';
        $this->inventory_type = 'devolutivo';
        $this->base_tag = '';
        $this->resetValidation();
    }

    public function createMultipleItems()
    {
        if (!auth()->user()->can('inventory_items.create')) {
            $this->dispatch('toast', message: 'No tiene permisos para crear artículos.', type: 'error');
            return;
        }

        $this->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'name' => 'required|string|max:255',
            'inventory_accounting_account_id' => 'required|exists:inventory_accounting_accounts,id',
            'initial_value' => 'required|numeric|min:0',
            'state' => ['required', Rule::in(array_keys(InventoryItem::STATES))],
            'inventory_type' => ['required', Rule::in(array_keys(InventoryItem::INVENTORY_TYPES))],
            'location' => 'nullable|string|max:100',
            'funding_source' => 'nullable|string|max:100',
            'base_tag' => 'nullable|string|max:50',
        ]);

        for ($i = 1; $i <= $this->quantity; $i++) {
            $tag = $this->base_tag ? $this->base_tag . '-' . str_pad($i, 3, '0', STR_PAD_LEFT) : null;

            InventoryItem::create([
                'school_id' => session('selected_school_id'),
                'inventory_entry_id' => $this->entry->id,
                'inventory_accounting_account_id' => $this->inventory_accounting_account_id,
                'name' => $this->name,
                'initial_value' => $this->initial_value,
                'acquisition_date' => $this->entry->date,
                'supplier_id' => $this->entry->supplier_id,
                'state' => $this->state,
                'current_tag' => $tag,
                'location' => $this->location,
                'funding_source' => $this->funding_source,
                'inventory_type' => $this->inventory_type,
                'is_active' => true,
            ]);
        }

        $this->entry->recalculateTotal();
        $this->showCreateModal = false;
        $this->dispatch('toast', message: "Se han creado y asignado {$this->quantity} artículo(s).", type: 'success');
    }

    public function assignSelectedItems()
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('toast', message: 'Debe seleccionar al menos un artículo.', type: 'warning');
            return;
        }

        InventoryItem::whereIn('id', $this->selectedItems)
            ->where('school_id', session('selected_school_id'))
            ->whereNull('inventory_entry_id')
            ->update([
                'inventory_entry_id' => $this->entry->id,
                // Opcional: heredar fecha y proveedor de la entrada
                'acquisition_date' => $this->entry->date,
                'supplier_id' => $this->entry->supplier_id,
            ]);

        $this->entry->recalculateTotal();
        $this->showSelectModal = false;
        $this->dispatch('toast', message: 'Artículos asignados correctamente a la entrada.', type: 'success');
    }

    public function removeItem($itemId)
    {
        $item = InventoryItem::where('id', $itemId)
            ->where('inventory_entry_id', $this->entry->id)
            ->first();

        if ($item) {
            $item->update(['inventory_entry_id' => null]);
            $this->entry->recalculateTotal();
            $this->dispatch('toast', message: 'Artículo desvinculado de la entrada.', type: 'info');
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $this->entry->load(['items.account', 'supplier']);
        return view('livewire.inventory-entry-detail');
    }
}
