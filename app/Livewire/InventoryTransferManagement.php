<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryTransferManagement extends Component
{
    use WithPagination;

    // View state
    public $isCreating = false;
    public $search = '';

    // Form state
    public $from_name;
    public $from_document;
    public $from_location;
    
    public $to_name;
    public $to_document;
    public $to_location;
    
    public $observations;
    public $transfer_date;

    // Items selection
    public $itemSearch = '';
    public $selectedItems = []; // Array of ids

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_items.view'), 403);
        $this->transfer_date = now()->format('Y-m-d');
    }

    public function create()
    {
        $this->resetForm();
        $this->isCreating = true;
    }

    public function cancel()
    {
        $this->isCreating = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->from_name = '';
        $this->from_document = '';
        $this->from_location = '';
        $this->to_name = '';
        $this->to_document = '';
        $this->to_location = '';
        $this->observations = '';
        $this->transfer_date = now()->format('Y-m-d');
        $this->itemSearch = '';
        $this->selectedItems = [];
        $this->resetValidation();
    }

    public function toggleItemSelection($itemId)
    {
        if (in_array($itemId, $this->selectedItems)) {
            $this->selectedItems = array_diff($this->selectedItems, [$itemId]);
        } else {
            $this->selectedItems[] = $itemId;
        }
    }

    public function getAvailableItemsProperty()
    {
        if (empty($this->itemSearch)) {
            return collect();
        }

        return InventoryItem::forSchool(session('selected_school_id'))
            ->whereNull('inventory_discharge_id')
            ->where(function($q) {
                $q->where('name', 'like', "%{$this->itemSearch}%")
                  ->orWhere('current_tag', 'like', "%{$this->itemSearch}%");
            })
            ->take(10)
            ->get();
    }

    public function getSelectedItemsListProperty()
    {
        if (empty($this->selectedItems)) return collect();
        return InventoryItem::whereIn('id', $this->selectedItems)->get();
    }

    public function save()
    {
        $this->validate([
            'from_name' => 'required|string|max:255',
            'from_document' => 'nullable|string|max:255',
            'from_location' => 'required|string|max:255',
            'to_name' => 'required|string|max:255',
            'to_document' => 'nullable|string|max:255',
            'to_location' => 'required|string|max:255',
            'transfer_date' => 'required|date',
            'selectedItems' => 'required|array|min:1',
        ], [
            'from_name.required' => 'El nombre de quien entrega es obligatorio.',
            'from_location.required' => 'La ubicación de origen es obligatoria.',
            'to_name.required' => 'El nombre de quien recibe es obligatorio.',
            'to_location.required' => 'La nueva ubicación es obligatoria.',
            'selectedItems.required' => 'Debe seleccionar al menos un artículo.',
            'selectedItems.min' => 'Debe seleccionar al menos un artículo.',
        ]);

        $schoolId = session('selected_school_id');

        DB::beginTransaction();
        try {
            // Generar consecutivo (ej: TR-2026-0001)
            $year = Carbon::parse($this->transfer_date)->format('Y');
            $count = InventoryTransfer::where('school_id', $schoolId)
                ->whereYear('transfer_date', $year)
                ->count() + 1;
            $consecutive = "TR-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);

            $transfer = InventoryTransfer::create([
                'school_id' => $schoolId,
                'consecutive' => $consecutive,
                'transfer_date' => $this->transfer_date,
                'from_name' => $this->from_name,
                'from_document' => $this->from_document,
                'from_location' => $this->from_location,
                'to_name' => $this->to_name,
                'to_document' => $this->to_document,
                'to_location' => $this->to_location,
                'observations' => $this->observations,
                'created_by' => auth()->id(),
            ]);

            $items = InventoryItem::whereIn('id', $this->selectedItems)->get();

            foreach ($items as $item) {
                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'inventory_item_id' => $item->id,
                    'old_location' => $item->location,
                    'new_location' => $this->to_location,
                    'old_account_id' => $item->inventory_accounting_account_id,
                    'new_account_id' => $item->inventory_accounting_account_id, // Por ahora igual
                ]);

                // Update item
                $item->update([
                    'location' => $this->to_location,
                ]);
            }

            DB::commit();

            $this->dispatch('toast', message: 'Acta de reintegro creada correctamente.', type: 'success');
            $this->cancel();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al crear el acta: ' . $e->getMessage(), type: 'error');
        }
    }

    public function getTransfersProperty()
    {
        return InventoryTransfer::forSchool(session('selected_school_id'))
            ->when($this->search, function($q) {
                $q->where('consecutive', 'like', "%{$this->search}%")
                  ->orWhere('from_name', 'like', "%{$this->search}%")
                  ->orWhere('to_name', 'like', "%{$this->search}%");
            })
            ->orderBy('transfer_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-transfer-management');
    }
}
