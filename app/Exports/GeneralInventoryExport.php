<?php

namespace App\Exports;

use App\Models\InventoryItem;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralInventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $schoolId;
    protected Carbon $cutOffDate;
    protected School $school;

    public function __construct($schoolId, Carbon $cutOffDate)
    {
        $this->schoolId = $schoolId;
        $this->cutOffDate = $cutOffDate;
        $this->school = School::find($schoolId);
    }

    public function collection()
    {
        return InventoryItem::with(['account', 'discharge'])
            ->forSchool($this->schoolId)
            ->orderBy('inventory_accounting_account_id')
            ->orderBy('acquisition_date')
            ->get();
    }

    public function startCell(): string
    {
        return 'B13';
    }

    public function headings(): array
    {
        return [
            'CÓDIGO CONTABLE',
            'DESCRIPCIÓN DEL ARTÍCULO',
            'CALCO. ACTUAL',
            'ESTADO',
            "VALOR\n(inicial de  compra)",
            'DEPRECIACIÓN Acumulada',
            'ACTA DE BAJA No',
            'VALOR BAJA',
            'SALDO FINAL',
            'FECHA ADQUISICIÓN O INGRESO',
            'PROVEEDOR Y No. DE FACTURA',
            'PROCEDENCIA RECURSOS',
            'SEDE DE UBICACIÓN',
            'TIPO INVENTARIO'
        ];
    }

    /**
     * @param InventoryItem $item
     */
    public function map($item): array
    {
        $valorBaja = $item->inventory_discharge_id ? $item->initial_value : 0;
        $saldoFinal = $item->initial_value - $item->getAccumulatedDepreciation($this->cutOffDate) - $valorBaja;

        return [
            $item->account->code ?? 'N/A', // B
            $item->name, // C
            $item->current_tag ?? 'S/P', // D
            strtoupper(substr($item->state ?? 'B', 0, 1)), // E (B, R, M)
            $item->initial_value, // F
            $item->getAccumulatedDepreciation($this->cutOffDate), // G
            $item->inventory_discharge_id ? $item->discharge_info : '0', // H
            $valorBaja, // I
            $saldoFinal, // J
            $item->acquisition_date ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($item->acquisition_date) : '', // K
            $item->supplier ? $item->supplier->full_name : 'N/A', // L
            $item->funding_source ?? 'N/A', // M
            $item->location ?? 'N/A', // N
            mb_strtoupper($item->inventory_type ?? 'DEVOLUTIVO') // O
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            13 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFE0E0E0']], 'alignment' => ['wrapText' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Textos fijos superiores
                $sheet->setCellValue('M4', 'FECHA DE APROBACION');
                $sheet->setCellValue('O4', Carbon::now()->format('d/m/Y'));
                
                $sheet->setCellValue('M5', 'PAGINA');
                $sheet->setCellValue('O5', '1 de 1');
                
                $sheet->setCellValue('B8', 'Institución Educativa:');
                $sheet->setCellValue('C8', mb_strtoupper($this->school->name));
                $sheet->getStyle('B8')->getFont()->setBold(true);
                $sheet->getStyle('C8')->getFont()->setBold(true);
                
                $sheet->setCellValue('H8', 'Representante Legal:');
                $sheet->setCellValue('J8', mb_strtoupper($this->school->rector_name ?? ''));
                $sheet->getStyle('H8')->getFont()->setBold(true);
                
                $sheet->setCellValue('B9', 'NIT:');
                $sheet->setCellValue('C9', $this->school->nit ?? 'N/A');
                
                $sheet->setCellValue('H12', 'BAJAS REALIZADAS');
                $sheet->getStyle('H12')->getFont()->setBold(true);

                // Formatos de celdas (Desde la fila 14 hacia abajo)
                $highestRow = $sheet->getHighestRow();
                
                // Formato de Moneda para Valores
                $sheet->getStyle('F14:F' . $highestRow)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                $sheet->getStyle('G14:G' . $highestRow)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                $sheet->getStyle('I14:I' . $highestRow)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                $sheet->getStyle('J14:J' . $highestRow)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                
                // Formato de Fecha
                $sheet->getStyle('K14:K' . $highestRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
                
                // Auto ajustar ciertas columnas que no son descripcion
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setWidth(50); // Descripción más ancha
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('K')->setAutoSize(true);
                $sheet->getColumnDimension('L')->setWidth(30);
            },
        ];
    }

    public function drawings()
    {
        $drawings = [];

        // Si el colegio tiene logo, lo agregamos a la esquina superior izquierda
        if ($this->school->logo && file_exists(public_path('storage/' . $this->school->logo))) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo del Colegio');
            $drawing->setPath(public_path('storage/' . $this->school->logo));
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawings[] = $drawing;
        }

        return $drawings;
    }
}
