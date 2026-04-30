<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectTemplate extends Command
{
    protected $signature = 'app:inspect-template {file}';

    public function handle()
    {
        $file = $this->argument('file');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        $cellsToInspect = ['A8', 'B8', 'C8', 'G8', 'H8', 'I8', 'J8', 'B9', 'C9', 'H12', 'B2', 'B3', 'B4', 'B5', 'B6', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6'];
        
        foreach ($cellsToInspect as $cell) {
            $val = $sheet->getCell($cell)->getValue();
            if ($val) {
                $this->info("$cell: $val");
            }
        }
        
        // Also let's find exact coordinates by searching for keywords
        $keywords = ['Institución Educativa:', 'NIT', 'DANE', 'Municipio', 'Representante', 'CÓDIGO CONTABLE', 'INSTRUCTIVO'];
        
        foreach ($sheet->getRowIterator(1, 20) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = $cell->getValue();
                if (is_string($val)) {
                    foreach ($keywords as $kw) {
                        if (str_contains($val, $kw)) {
                            $this->line("Found '$kw' in " . $cell->getCoordinate() . " -> $val");
                        }
                    }
                }
            }
        }
    }
}
