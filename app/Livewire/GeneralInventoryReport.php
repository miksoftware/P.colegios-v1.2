<?php

namespace App\Livewire;

use App\Exports\GeneralInventoryExport;
use App\Models\InventoryItem;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GeneralInventoryReport extends Component
{
    use WithPagination;

    public $cutOffDate;

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_items.view'), 403);
        $this->cutOffDate = now()->format('Y-m-d');
    }

    public function getItemsProperty()
    {
        return InventoryItem::with(['account', 'entry', 'discharge', 'supplier'])
            ->forSchool(session('selected_school_id'))
            ->orderBy('inventory_accounting_account_id')
            ->orderBy('acquisition_date')
            ->paginate(20);
    }

    public function exportExcel()
    {
        $schoolId = session('selected_school_id');
        $school = \App\Models\School::find($schoolId);
        $date = Carbon::parse($this->cutOffDate);
        
        $templatePath = base_path('docs/plantilla_inventarios.xlsx');
        
        if (!file_exists($templatePath)) {
            $this->dispatch('toast', message: 'No se encontró la plantilla en docs/plantilla_inventarios.xlsx', type: 'error');
            return;
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Llenar cabecera
        $sheet->setCellValue('C7', mb_strtoupper($school->name ?? ''));
        $sheet->setCellValue('J7', mb_strtoupper($school->rector_display_name ?? ''));
        $sheet->setCellValue('C8', mb_strtoupper($school->municipality ?? ''));
        $sheet->setCellValue('G8', $school->nit ?? '');
        $sheet->setCellValue('J8', $school->dane_code ?? '');
        $sheet->setCellValue('C9', $date->format('d/m/Y'));

        // Obtener items
        $items = InventoryItem::with(['account', 'discharge', 'supplier'])
            ->forSchool($schoolId)
            ->orderBy('inventory_accounting_account_id')
            ->orderBy('acquisition_date')
            ->get();

        $row = 14; // Fila donde inician los datos en la plantilla
        foreach ($items as $item) {
            $valorBaja = $item->inventory_discharge_id ? $item->initial_value : 0;
            $saldoFinal = $item->initial_value - $item->getAccumulatedDepreciation($date) - $valorBaja;

            $sheet->setCellValue('B' . $row, $item->account->code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->name);
            $sheet->setCellValue('D' . $row, $item->current_tag ?? 'S/P');
            $sheet->setCellValue('E' . $row, strtoupper(substr($item->state ?? 'B', 0, 1)));
            $sheet->setCellValue('F' . $row, $item->initial_value);
            $sheet->setCellValue('G' . $row, $item->getAccumulatedDepreciation($date));
            $sheet->setCellValue('H' . $row, $item->inventory_discharge_id ? $item->discharge_info : '0');
            $sheet->setCellValue('I' . $row, $valorBaja);
            $sheet->setCellValue('J' . $row, $saldoFinal);
            
            if ($item->acquisition_date) {
                $sheet->setCellValue('K' . $row, Date::PHPToExcel($item->acquisition_date));
            }
            
            $sheet->setCellValue('L' . $row, $item->supplier ? $item->supplier->full_name : 'N/A');
            $sheet->setCellValue('M' . $row, $item->funding_source ?? 'N/A');
            $sheet->setCellValue('N' . $row, $item->location ?? 'N/A');
            $sheet->setCellValue('O' . $row, mb_strtoupper($item->inventory_type ?? 'DEVOLUTIVO'));

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'inventario_general_' . $date->format('Y_m_d') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.general-inventory-report', [
            'parsedDate' => Carbon::parse($this->cutOffDate)
        ]);
    }
}
