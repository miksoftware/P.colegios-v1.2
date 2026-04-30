<?php

namespace App\Livewire;

use App\Models\InventoryDischarge;
use App\Models\InventoryItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InventoryDischargeDetail extends Component
{
    public InventoryDischarge $discharge;
    
    public $showSelectModal = false;
    public $itemSearch = '';

    // Campos para selección existente
    public $selectedItems = [];

    public function mount(InventoryDischarge $discharge)
    {
        abort_if(!auth()->user()->can('inventory_discharges.view'), 403);
        
        // Validar que la baja pertenezca al colegio actual
        abort_if($discharge->school_id !== session('selected_school_id'), 404);

        $this->discharge = $discharge;
    }

    /**
     * Verificar si la baja está terminada (tiene artículos asignados)
     */
    public function getIsFinishedProperty(): bool
    {
        return $this->discharge->items()->count() > 0;
    }

    public function getAvailableItemsProperty()
    {
        if (empty($this->itemSearch)) {
            return collect();
        }

        return InventoryItem::forSchool(session('selected_school_id'))
            ->whereNotNull('inventory_entry_id')
            ->whereNull('inventory_discharge_id')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->itemSearch}%")
                  ->orWhere('current_tag', 'like', "%{$this->itemSearch}%")
                  ->orWhere('location', 'like', "%{$this->itemSearch}%");
            })
            ->with('account')
            ->take(20)
            ->get();
    }

    public function openSelectModal()
    {
        if ($this->isFinished) {
            $this->dispatch('toast', message: 'Esta resolución ya está terminada. No se pueden agregar más artículos.', type: 'warning');
            return;
        }

        $this->selectedItems = [];
        $this->itemSearch = '';
        $this->showSelectModal = true;
    }

    public function assignSelectedItems()
    {
        if ($this->isFinished) {
            $this->dispatch('toast', message: 'Esta resolución ya está terminada.', type: 'warning');
            return;
        }

        if (empty($this->selectedItems)) {
            $this->dispatch('toast', message: 'Debe seleccionar al menos un artículo.', type: 'warning');
            return;
        }

        InventoryItem::whereIn('id', $this->selectedItems)
            ->where('school_id', session('selected_school_id'))
            ->update([
                'inventory_discharge_id' => $this->discharge->id,
                'is_active' => false
            ]);

        $this->discharge->recalculateTotal();
        $this->showSelectModal = false;
        $this->dispatch('toast', message: 'Artículos dados de baja correctamente. La resolución queda en estado TERMINADO.', type: 'success');
    }

    public function removeItem($itemId)
    {
        $item = InventoryItem::where('id', $itemId)
            ->where('inventory_discharge_id', $this->discharge->id)
            ->first();

        if ($item) {
            $item->update([
                'inventory_discharge_id' => null,
                'is_active' => true
            ]);
            $this->discharge->recalculateTotal();
            $this->dispatch('toast', message: 'Artículo revertido. Vuelve a estar disponible.', type: 'info');
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $this->discharge->load(['items.account', 'items.entry']);
        return view('livewire.inventory-discharge-detail');
    }
}
