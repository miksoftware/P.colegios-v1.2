<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectDrawings extends Command
{
    protected $signature = 'app:inspect-drawings {file}';

    public function handle()
    {
        $file = $this->argument('file');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        $drawings = $sheet->getDrawingCollection();
        $this->info("Drawings found: " . count($drawings));
        foreach ($drawings as $drawing) {
            $this->info("Name: " . $drawing->getName());
            $this->info("Coordinates: " . $drawing->getCoordinates());
        }
    }
}
