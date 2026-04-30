<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InspectExcelCols extends Command
{
    protected $signature = 'app:inspect-excel-cols {file}';
    protected $description = 'Inspect Excel data rows';

    public function handle()
    {
        $file = $this->argument('file');
        try {
            $array = Excel::toArray(new \stdClass(), $file);
            $sheet = $array[0];
            
            // Buscar la palabra "CAMACHO" para ver en qué fila está
            foreach($sheet as $index => $row) {
                foreach($row as $colIndex => $val) {
                    if (is_string($val) && str_contains(strtoupper($val), 'CAMACHO')) {
                        $this->info("Found in Row $index, Col $colIndex: $val");
                        $this->line("Entire Row: " . json_encode($row));
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
