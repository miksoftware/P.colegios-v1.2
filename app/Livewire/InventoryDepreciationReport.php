<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InventoryDepreciationReport extends Component
{
    public int $year;
    public int $month;

    /** Meses en español */
    private const MONTHS_ES = [
        1  => 'Enero',   2  => 'Febrero',  3  => 'Marzo',
        4  => 'Abril',   5  => 'Mayo',     6  => 'Junio',
        7  => 'Julio',   8  => 'Agosto',   9  => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre',12 => 'Diciembre',
    ];

    /**
     * Mapeo: prefijo de cuenta activo (sin puntos) →
     *   [código débito, código crédito, nombre, categoría]
     *
     * Categorías: 'ppe' = Prop. Planta y Equipo | 'intangible'
     */
    public static function depreciationMapping(): array
    {
        return [
            '1610'   => ['5.3.60.03', '1.6.85.03', 'Semovientes',                                       'ppe'],
            '1640'   => ['5.3.60.01', '1.6.85.01', 'Edificaciones',                                     'ppe'],
            '1655'   => ['5.3.60.04', '1.6.85.04', 'Maquinaria y equipo',                               'ppe'],
            '1665'   => ['5.3.60.06', '1.6.85.06', 'Muebles, enseres y equipo de oficina',              'ppe'],
            '1670'   => ['5.3.60.07', '1.6.85.07', 'Equipos de comunicación y computación',             'ppe'],
            '1675'   => ['5.3.60.08', '1.6.85.08', 'Equipos de transporte, tracción y elevación',       'ppe'],
            '1680'   => ['5.3.60.09', '1.6.85.09', 'Equipos de comedor, cocina, despensa y hotelería',  'ppe'],
            // Intangibles — búsqueda del prefijo más específico primero
            '197007' => ['5.3.66.05', '1.9.75.07', 'Licencias',         'intangible'],
            '197008' => ['5.3.66.06', '1.9.75.08', 'Software',          'intangible'],
            '197090' => ['5.3.66.90', '1.9.75.90', 'Otros intangibles', 'intangible'],
        ];
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('inventory_items.view'), 403);
        $this->year  = (int) now()->format('Y');
        $this->month = (int) now()->format('n');
    }

    // ──────────────────────────────────────────────
    // Computed property: datos del comprobante
    // ──────────────────────────────────────────────
    public function getReportDataProperty(): array
    {
        $schoolId    = session('selected_school_id');
        $periodEnd   = Carbon::create($this->year, $this->month, 1)->endOfMonth();
        $prevEnd     = Carbon::create($this->year, $this->month, 1)->startOfMonth()->subDay();
        $mapping     = self::depreciationMapping();

        $items = InventoryItem::with(['account'])
            ->forSchool($schoolId)
            ->active()
            ->whereHas('account', fn($q) => $q->where('depreciation_years', '>', 0))
            ->where('acquisition_date', '<=', $periodEnd)
            ->get();

        // Agrupamos por prefijo de cuenta
        $grouped = [];
        foreach ($items as $item) {
            $normalized = str_replace('.', '', $item->account->code ?? '');

            // Buscar el prefijo más específico que coincida
            $matchKey  = null;
            $matchLen  = 0;
            foreach (array_keys($mapping) as $prefix) {
                if (str_starts_with($normalized, $prefix) && strlen($prefix) > $matchLen) {
                    $matchKey = $prefix;
                    $matchLen = strlen($prefix);
                }
            }
            if (!$matchKey) continue;

            $depEnd    = $item->getAccumulatedDepreciation($periodEnd);
            $depPrev   = $item->getAccumulatedDepreciation($prevEnd);
            $amount    = round($depEnd - $depPrev, 2);

            if ($amount <= 0) continue;

            $grouped[$matchKey] = ($grouped[$matchKey] ?? 0) + $amount;
        }

        $ppeRows         = [];
        $intangibleRows  = [];
        $ppeTotal        = 0.0;
        $intangibleTotal = 0.0;

        foreach ($grouped as $prefix => $amount) {
            [$debit, $credit, $name, $category] = $mapping[$prefix];
            $row = compact('debit', 'credit', 'name', 'amount');
            if ($category === 'ppe') {
                $ppeRows[] = $row;
                $ppeTotal += $amount;
            } else {
                $intangibleRows[] = $row;
                $intangibleTotal += $amount;
            }
        }

        return [
            'ppe'        => ['rows' => $ppeRows,        'total' => $ppeTotal],
            'intangible' => ['rows' => $intangibleRows, 'total' => $intangibleTotal],
            'grand_total' => $ppeTotal + $intangibleTotal,
            'period_label' => self::MONTHS_ES[$this->month] . ' ' . $this->year,
        ];
    }

    // ──────────────────────────────────────────────
    // Exportar a Excel
    // ──────────────────────────────────────────────
    public function exportExcel()
    {
        $schoolId = session('selected_school_id');
        $school   = \App\Models\School::find($schoolId);
        $data     = $this->reportData;
        $period   = self::MONTHS_ES[$this->month] . ' ' . $this->year;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Depreciación');

        // ── LOGO ──
        $logoPath = $school->logo ? public_path('storage/' . $school->logo) : null;
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo del colegio');
            $drawing->setPath($logoPath);
            $drawing->setHeight(65);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        // ── ENCABEZADO ──
        $sheet->mergeCells('B1:F1');
        $sheet->setCellValue('B1', mb_strtoupper($school->name ?? ''));
        $sheet->getStyle('B1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1A3C6E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->mergeCells('B2:F2');
        $sheet->setCellValue('B2', 'COMPROBANTE DE DEPRECIACIÓN Y AMORTIZACIÓN');
        $sheet->getStyle('B2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF1A3C6E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('B3:F3');
        $sheet->setCellValue('B3', 'Período: ' . $period);
        $sheet->getStyle('B3')->applyFromArray([
            'font'      => ['size' => 10, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('B4:C4');
        $sheet->setCellValue('B4', 'Municipio: ' . ($school->municipality ?? 'N/A'));
        $sheet->setCellValue('D4', 'NIT: ' . ($school->nit ?? 'N/A'));
        $sheet->mergeCells('E4:F4');
        $sheet->setCellValue('E4', 'Impreso: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('B4:F4')->getFont()->setSize(9);

        // ── HEADERS DE LA TABLA ──
        $hRow = 6;
        $sheet->setCellValue('A' . $hRow, 'CÓDIGO');
        $sheet->mergeCells('B' . $hRow . ':D' . $hRow);
        $sheet->setCellValue('B' . $hRow, 'CONCEPTO');
        $sheet->setCellValue('E' . $hRow, 'DEBE');
        $sheet->setCellValue('F' . $hRow, 'HABER');
        $sheet->getStyle('A' . $hRow . ':F' . $hRow)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3C6E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
        ]);
        $sheet->getRowDimension($hRow)->setRowHeight(18);

        $row = $hRow + 1;

        // ── Helpers ──
        $fmtMoney = '"$"#,##0.00';

        $writeSection = function (string $title) use ($sheet, &$row) {
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", $title);
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1A3C6E']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
            ]);
            $row++;
        };

        $writeSubSection = function (string $title) use ($sheet, &$row) {
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", '    ' . $title);
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'    => ['bold' => true, 'italic' => true, 'size' => 9, 'color' => ['argb' => 'FF2C5282']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F5FF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
            ]);
            $row++;
        };

        $writeDataRow = function (string $code, string $name, float $amount, bool $isCredit) use ($sheet, &$row, $fmtMoney) {
            $sheet->setCellValue("A{$row}", $code);
            $sheet->mergeCells("B{$row}:D{$row}");
            $sheet->setCellValue("B{$row}", $name);
            if (!$isCredit) {
                $sheet->setCellValue("E{$row}", $amount);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
            } else {
                $sheet->setCellValue("F{$row}", $amount);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
            }
            $bgColor = $isCredit ? 'FFFFF8F0' : 'FFFFFFFF';
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
                'font'    => ['size' => 9],
            ]);
            $sheet->getStyle("A{$row}")->getFont()->setItalic($isCredit)->setColor(
                (new \PhpOffice\PhpSpreadsheet\Style\Color($isCredit ? 'FF805000' : 'FF1A3C6E'))
            );
            $row++;
        };

        $writeSubtotal = function (string $label, float $amount) use ($sheet, &$row, $fmtMoney) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("E{$row}", $amount);
            $sheet->setCellValue("F{$row}", $amount);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF1A3C6E']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
            ]);
            $row++;
        };

        // ── SECCIÓN PP&E ──
        if (!empty($data['ppe']['rows'])) {
            $writeSection('5.3  DETERIORO, DEPRECIACIONES, AMORTIZACIONES Y PROVISIONES');
            $writeSubSection('5.3.60  DEPRECIACIÓN DE PROPIEDADES, PLANTA Y EQUIPO');
            foreach ($data['ppe']['rows'] as $r) {
                $writeDataRow($r['debit'], $r['name'], $r['amount'], false);
                $writeDataRow($r['credit'], $r['name'], $r['amount'], true);
                $row++; // espacio entre pares
            }
            $writeSubtotal('Subtotal Depreciación PP&E', $data['ppe']['total']);
            $row++;
        }

        // ── SECCIÓN INTANGIBLES ──
        if (!empty($data['intangible']['rows'])) {
            $writeSection('5.3.66  AMORTIZACIÓN DE ACTIVOS INTANGIBLES');
            foreach ($data['intangible']['rows'] as $r) {
                $writeDataRow($r['debit'], $r['name'], $r['amount'], false);
                $writeDataRow($r['credit'], $r['name'], $r['amount'], true);
                $row++;
            }
            $writeSubtotal('Subtotal Amortización Intangibles', $data['intangible']['total']);
            $row++;
        }

        // ── TOTAL GENERAL ──
        $row++;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("E{$row}", $data['grand_total']);
        $sheet->setCellValue("F{$row}", $data['grand_total']);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($fmtMoney);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3C6E']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF1A3C6E']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);

        // ── FIRMAS ──
        $row += 4;
        foreach (['B' => 'Elaboró', 'D' => 'Revisó', 'F' => 'Aprobó'] as $col => $label) {
            $sheet->setCellValue("{$col}{$row}", '_______________________');
            $sheet->setCellValue("{$col}" . ($row + 1), $label);
            $sheet->getStyle("{$col}" . ($row + 1))->getFont()->setBold(true)->setSize(9);
        }

        // ── ANCHOS DE COLUMNA ──
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);

        // ── IMPRIMIR ──
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getHeaderFooter()->setOddHeader('&C&"Arial,Bold"&12' . mb_strtoupper($school->name ?? ''));
        $sheet->getHeaderFooter()->setOddFooter('&L' . $period . '&C&P de &N&RGenerado: ' . now()->format('d/m/Y'));

        $fileName = 'depreciacion_' . $this->year . '_' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-depreciation-report', [
            'data'       => $this->reportData,
            'monthsEs'   => self::MONTHS_ES,
        ]);
    }
}
