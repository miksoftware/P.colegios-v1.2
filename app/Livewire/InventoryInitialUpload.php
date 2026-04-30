<?php

namespace App\Livewire;

use App\Imports\InitialInventoryImport;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class InventoryInitialUpload extends Component
{
    use WithFileUploads;

    public $file;
    public $isUploading = false;

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_items.create'), 403);
    }

    public function rules()
    {
        return [
            'file' => 'required|file|max:10240', // 10MB max
        ];
    }

    public function importExcel()
    {
        $this->validate();

        $this->isUploading = true;

        try {
            $schoolId = session('selected_school_id');
            Excel::import(new InitialInventoryImport($schoolId), $this->file->getRealPath());

            $this->dispatch('toast', message: 'Inventario inicial importado con éxito.', type: 'success');
            $this->reset('file');
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error en la importación: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isUploading = false;
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-initial-upload');
    }
}
