<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExtractImage extends Command
{
    protected $signature = 'app:extract-image';

    public function handle()
    {
        $file = base_path('docs/plantilla_inventarios.xlsx');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        $drawings = $sheet->getDrawingCollection();
        foreach ($drawings as $drawing) {
            if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                // handle memory drawing
                $this->info("Memory drawing");
            } elseif ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                $imagePath = $drawing->getPath();
                $extension = $drawing->getExtension();
                $destPath = public_path('storage/gobernacion.' . $extension);
                copy($imagePath, $destPath);
                $this->info("Extracted image to: $destPath");
                
                // Let's remove the drawing from the template
                //$drawings->offsetUnset(0);
            }
        }
    }
}
