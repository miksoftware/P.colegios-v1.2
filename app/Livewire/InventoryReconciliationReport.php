<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use App\Models\School;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InventoryReconciliationReport extends Component
{
    public string $cutoffDate;
    public string $storageKeeperName = '';

    private const MONTHS_ES = [
        1  => 'Enero',   2  => 'Febrero',  3  => 'Marzo',
        4  => 'Abril',   5  => 'Mayo',     6  => 'Junio',
        7  => 'Julio',   8  => 'Agosto',   9  => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const MONTHS_ES_UPPER = [
        1  => 'ENERO',     2  => 'FEBRERO',   3  => 'MARZO',
        4  => 'ABRIL',     5  => 'MAYO',       6  => 'JUNIO',
        7  => 'JULIO',     8  => 'AGOSTO',     9  => 'SEPTIEMBRE',
        10 => 'OCTUBRE',  11  => 'NOVIEMBRE', 12  => 'DICIEMBRE',
    ];

    /**
     * Prefijo de cuenta (sin puntos) → [código depreciación acumulada, nombre]
     */
    private static function depreciationAccountMapping(): array
    {
        return [
            '1610'   => ['1.6.85.03', 'Semovientes'],
            '1640'   => ['1.6.85.01', 'Edificaciones'],
            '1655'   => ['1.6.85.04', 'Maquinaria y equipo'],
            '1665'   => ['1.6.85.06', 'Muebles, enseres y equipo de oficina'],
            '1670'   => ['1.6.85.07', 'Equipos de comunicación y computación'],
            '1675'   => ['1.6.85.08', 'Equipos de transporte, tracción y elevación'],
            '1680'   => ['1.6.85.09', 'Equipos de comedor, cocina, despensa y hotelería'],
            '197007' => ['1.9.75.07', 'Licencias'],
            '197008' => ['1.9.75.08', 'Software'],
            '197090' => ['1.9.75.90', 'Otros intangibles'],
        ];
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('inventory_items.view'), 403);
        $this->cutoffDate = now()->endOfYear()->format('Y-m-d');
        $school = School::find(session('selected_school_id'));
        $this->storageKeeperName = $school->pagador_name ?? '';
    }

    // ──────────────────────────────────────────────
    // Computed property: datos del reporte
    // ──────────────────────────────────────────────
    public function getReportDataProperty(): array
    {
        $schoolId   = session('selected_school_id');
        $cutoff     = Carbon::parse($this->cutoffDate)->endOfDay();
        $depMapping = self::depreciationAccountMapping();

        $items = InventoryItem::with(['account'])
            ->forSchool($schoolId)
            ->where('inventory_type', 'devolutivo')
            ->where('is_active', true)
            ->where('acquisition_date', '<=', $cutoff)
            ->get();

        // --- Sección Activos: agrupado por código de cuenta ---
        $assetGroups = [];
        foreach ($items as $item) {
            $code = $item->account->code ?? 'S/C';
            $name = $item->account->name ?? 'Sin cuenta';
            if (!isset($assetGroups[$code])) {
                $assetGroups[$code] = ['code' => $code, 'name' => $name, 'books_value' => 0.0];
            }
            $assetGroups[$code]['books_value'] += (float) $item->initial_value;
        }
        ksort($assetGroups);
        $assetRows  = array_values($assetGroups);
        $assetTotal = array_sum(array_column($assetRows, 'books_value'));

        // --- Sección Depreciación Acumulada: agrupado por cuenta depreciación ---
        $depGroups = [];
        foreach ($items as $item) {
            $acc = $item->account;
            if (!$acc || ($acc->depreciation_years ?? 0) <= 0) continue;

            $normalized = str_replace('.', '', $acc->code ?? '');
            $matchKey   = null;
            $matchLen   = 0;
            foreach (array_keys($depMapping) as $prefix) {
                if (str_starts_with($normalized, $prefix) && strlen($prefix) > $matchLen) {
                    $matchKey = $prefix;
                    $matchLen = strlen($prefix);
                }
            }
            if (!$matchKey) continue;

            [$depCode, $depName] = $depMapping[$matchKey];
            if (!isset($depGroups[$depCode])) {
                $depGroups[$depCode] = ['code' => $depCode, 'name' => $depName, 'books_value' => 0.0];
            }
            $depGroups[$depCode]['books_value'] += $item->getAccumulatedDepreciation($cutoff);
        }
        ksort($depGroups);
        $depRows  = array_values($depGroups);
        $depTotal = array_sum(array_column($depRows, 'books_value'));

        $cutoffCarbon = Carbon::parse($this->cutoffDate);
        $cutoffLabel  = 'A ' . self::MONTHS_ES_UPPER[$cutoffCarbon->month]
            . ' ' . $cutoffCarbon->day . ' DE ' . $cutoffCarbon->year;

        return [
            'asset_rows'   => $assetRows,
            'asset_total'  => $assetTotal,
            'dep_rows'     => $depRows,
            'dep_total'    => $depTotal,
            'net_total'    => $assetTotal - $depTotal,
            'cutoff_label' => $cutoffLabel,
        ];
    }

    // ──────────────────────────────────────────────
    // Exportar a Excel
    // ──────────────────────────────────────────────
    public function exportExcel()
    {
        $schoolId = session('selected_school_id');
        $school   = School::find($schoolId);
        $data     = $this->reportData;
        $cutoff   = Carbon::parse($this->cutoffDate);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Conciliación PP&E');

        // ── Anchos de columna ──
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(44);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(22);

        // ─────────────────────────────────────────
        // BLOQUE DE CABECERA (Filas 1-4)
        // ─────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // Logo en A1
        $logoPath = $school->logo_absolute_path;
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo colegio');
            $drawing->setPath($logoPath);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4)->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        // Nombre del colegio (A1:B4)
        $sheet->mergeCells('A1:B4');
        $sheet->setCellValue('A1', mb_strtoupper($school->name ?? '') . "\n" . mb_strtoupper($school->municipality ?? ''));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF1A3C6E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Título principal (C1:F1)
        $sheet->mergeCells('C1:F1');
        $sheet->setCellValue('C1', 'FORMATO PARA CONCILIACIÓN PROPIEDAD PLANTA Y EQUIPO');
        $sheet->getStyle('C1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1A3C6E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Subtítulo (C2:F2)
        $sheet->mergeCells('C2:F2');
        $sheet->setCellValue('C2', 'PROCESO GESTIÓN FINANCIERA Y CONTABLE');
        $sheet->getStyle('C2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Descripción proceso (C3:F4)
        $sheet->mergeCells('C3:F4');
        $sheet->setCellValue('C3',
            'PROCEDIMIENTO PREPARACION, PRESENTACION Y PUBLICACION DE '
            . 'INFORMES FINANCIEROS Y CONTABLES PARA LA VIGENCIA ' . $cutoff->year);
        $sheet->getStyle('C3')->applyFromArray([
            'font'      => ['size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ─────────────────────────────────────────
        // FILAS DE INFORMACIÓN (6-9)
        // ─────────────────────────────────────────
        $infoRows = [
            6 => ['FECHA ELABORACION',  'A ' . now()->format('d/m/Y')],
            7 => ['FECHA DE CORTE',     $data['cutoff_label']],
            8 => ['CUENTA CONTABLE',    'PROPIEDAD PLANTA Y EQUIPO'],
            9 => ['NOMBRE ALMACENISTA', mb_strtoupper($this->storageKeeperName)],
        ];

        foreach ($infoRows as $row => [$label, $value]) {
            $sheet->getRowDimension($row)->setRowHeight(18);
            $sheet->setCellValue('A' . $row, $label);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font'  => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
                'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF595959']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle('B' . $row)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        // ─────────────────────────────────────────
        // CABECERA DE TABLA (Fila 12)
        // ─────────────────────────────────────────
        $sheet->getRowDimension(12)->setRowHeight(50);
        $headers = [
            'A12' => 'CODIGO',
            'B12' => 'DESCRIPCION',
            'C12' => "SALDO EN LIBROS\nDE CONTABILIDAD\n(SIIF NACION)",
            'D12' => "SALDO FINAL\nREPORTADO POR LA\nDEPENDENCIA -\nINVENTARIO FISICO",
            'E12' => 'DIFERENCIAS',
            'F12' => 'OBSERVACIONES',
        ];
        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF000000']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        // ─────────────────────────────────────────
        // FILAS DE ACTIVOS (desde fila 13)
        // ─────────────────────────────────────────
        $row = 13;
        $moneyFmt = '"$"#,##0.00';

        foreach ($data['asset_rows'] as $r) {
            $sheet->getRowDimension($row)->setRowHeight(16);
            $sheet->setCellValue('A' . $row, $r['code']);
            $sheet->setCellValue('B' . $row, $r['name']);
            $sheet->setCellValue('C' . $row, $r['books_value']);
            $sheet->setCellValue('D' . $row, $r['books_value']); // físico = libros
            $sheet->setCellValue('E' . $row, 0);                // diferencia = 0
            $sheet->setCellValue('F' . $row, '');

            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
            $sheet->getStyle('C' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('B' . $row)->getFont()->setSize(9);
            $row++;
        }

        // Fila TOTAL activos
        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, $data['asset_total']);
        $sheet->setCellValue('D' . $row, $data['asset_total']);
        $sheet->setCellValue('E' . $row, 0);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 10],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
        $totalAssetRow = $row;
        $row += 2; // blank row

        // ─────────────────────────────────────────
        // ENCABEZADO DEPRECIACIÓN
        // ─────────────────────────────────────────
        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, '1685     DEPRECIACIÓN ACUMULADA (CR)');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF000000']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;

        // Filas de depreciación
        foreach ($data['dep_rows'] as $r) {
            $sheet->getRowDimension($row)->setRowHeight(16);
            $sheet->setCellValue('A' . $row, str_replace('.', '', $r['code']));
            $sheet->setCellValue('B' . $row, $r['name']);
            $sheet->setCellValue('C' . $row, $r['books_value']);
            $sheet->setCellValue('D' . $row, $r['books_value']);
            $sheet->setCellValue('E' . $row, 0);
            $sheet->setCellValue('F' . $row, '');

            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
            $sheet->getStyle('C' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('B' . $row)->getFont()->setSize(9);
            $row++;
        }

        // Fila TOTAL depreciación
        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, $data['dep_total']);
        $sheet->setCellValue('D' . $row, $data['dep_total']);
        $sheet->setCellValue('E' . $row, 0);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 10],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
        $row += 2; // blank row

        // ─────────────────────────────────────────
        // FILA TOTAL PP&E
        // ─────────────────────────────────────────
        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'TOTAL PROPIEDAD PLANTA Y EQUIPO');
        $sheet->setCellValue('C' . $row, $data['net_total']);
        $sheet->setCellValue('D' . $row, $data['net_total']);
        $sheet->setCellValue('E' . $row, 0);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 10],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
        $row += 2;

        // ─────────────────────────────────────────
        // SECCIÓN ANÁLISIS / FIRMAS
        // ─────────────────────────────────────────
        // Encabezado "Análisis"
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('A' . $row, 'Análisis');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'    => ['bold' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $row++;

        // Cabecera de firmas
        $sigHeaders = ['PROCESO RESPONSABLE', 'SOPORTES DE LA CONCILIACION', 'ELABORACIÓN', 'REVISIÓN', 'APROBACIÓN'];
        $sigCols    = ['A', 'B', 'C', 'D', 'E'];
        $sheet->getRowDimension($row)->setRowHeight(22);
        foreach (array_combine($sigCols, $sigHeaders) as $col => $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font'    => ['bold' => true, 'size' => 9],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true],
            ]);
        }
        $row++;

        $elaboracionLabel = 'A ' . self::MONTHS_ES[$cutoff->month] . ' ' . $cutoff->day . ' de ' . $cutoff->year;
        $sigData = [
            ['Pagaduría y Contabilidad', 'Inventario Físico presentado por la dependencia', $elaboracionLabel, $school->rector_name ?? '', 'Aprobado'],
            ['', 'Estado de Situación Financiera a ' . strtolower($data['cutoff_label']), '', '', ''],
        ];

        foreach ($sigData as $sigRow) {
            $sheet->getRowDimension($row)->setRowHeight(18);
            foreach (array_combine($sigCols, $sigRow) as $col => $val) {
                $sheet->setCellValue($col . $row, $val);
                $sheet->getStyle($col . $row)->applyFromArray([
                    'font'    => ['size' => 9],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
            }
            $row++;
        }

        // ─────────────────────────────────────────
        // CONFIGURACIÓN DE PÁGINA
        // ─────────────────────────────────────────
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()->setTop(0.75)->setBottom(0.75)->setLeft(0.5)->setRight(0.5);
        $sheet->getHeaderFooter()
            ->setOddHeader('&C&B' . mb_strtoupper($school->name ?? '') . ' — Conciliación PP&E')
            ->setOddFooter('&L' . date('d/m/Y') . '&C&P de &N&R' . $data['cutoff_label']);

        // ─────────────────────────────────────────
        // DESCARGA
        // ─────────────────────────────────────────
        $filename = 'conciliacion_ppe_' . $cutoff->format('Y') . '.xlsx';
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-reconciliation-report', [
            'data' => $this->reportData,
        ]);
    }
}
