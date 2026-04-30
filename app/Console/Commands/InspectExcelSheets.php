<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InspectExcelSheets extends Command
{
    protected $signature = 'app:inspect-excel-sheets {file}';
    protected $description = 'Inspect Excel sheets';

    public function handle()
    {
        $file = $this->argument('file');
        try {
            $array = Excel::toArray(new \stdClass(), $file);
            foreach($array as $index => $sheet) {
                $this->info("Sheet $index has " . count($sheet) . " rows.");
                if (count($sheet) > 0) {
                    $this->line("First row: " . json_encode($sheet[0]));
                    // buscar si hay alguna fila que tenga 'MACHO'
                    foreach($sheet as $rIndex => $row) {
                        foreach($row as $cIndex => $val) {
                            if (is_string($val) && str_contains(strtoupper($val), 'MACHO')) {
                                $this->line("Found 'MACHO' in row $rIndex: " . json_encode($row));
                                break 2;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
