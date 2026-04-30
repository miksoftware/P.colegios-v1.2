<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InspectExcel extends Command
{
    protected $signature = 'app:inspect-excel {file}';
    protected $description = 'Inspect Excel headers';

    public function handle()
    {
        $file = $this->argument('file');
        try {
            $array = Excel::toArray(new \stdClass(), $file);
            $sheet = $array[0];
            // Encontrar la primera fila con datos (probablemente la cabecera real)
            foreach(array_slice($sheet, 0, 15) as $index => $row) {
                $this->info("Fila $index:");
                $this->line(json_encode(array_values($row)));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
