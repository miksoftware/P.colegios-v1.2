<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InspectExcelHeaders extends Command
{
    protected $signature = 'app:inspect-excel-headers {file}';
    protected $description = 'Inspect Excel headers';

    public function handle()
    {
        $file = $this->argument('file');
        try {
            $array = Excel::toArray(new \stdClass(), $file);
            $sheet = $array[0];
            
            for ($i = 0; $i <= 14; $i++) {
                $this->info("Row " . ($i + 1) . ":");
                $this->line(json_encode(array_slice($sheet[$i], 0, 16), JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
