<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InitialInventoryImport implements WithMultipleSheets, SkipsUnknownSheets
{
    protected $schoolId;
    protected $filePath;

    public function __construct($schoolId, $filePath = null)
    {
        $this->schoolId = $schoolId;
        $this->filePath = $filePath;
    }

    public function onUnknownSheet($sheetName)
    {
        // Ignorar cualquier otra hoja que no sea la de inventario general
    }

    public function sheets(): array
    {
        $targetSheetName = null;

        if ($this->filePath && file_exists($this->filePath)) {
            try {
                $reader = IOFactory::createReaderForFile($this->filePath);
                $sheetNames = $reader->listWorksheetNames($this->filePath);

                // 1. Coincidencia exacta con "inventario general" (insensible a mayúsculas/espacios)
                foreach ($sheetNames as $name) {
                    if (mb_strtolower(trim($name)) === 'inventario general') {
                        $targetSheetName = $name;
                        break;
                    }
                }

                // 2. Si no hay exacta, buscar si contiene "inventario general"
                if (!$targetSheetName) {
                    foreach ($sheetNames as $name) {
                        if (mb_stripos(trim($name), 'inventario general') !== false) {
                            $targetSheetName = $name;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback si ocurre error al listar hojas
            }
        }

        if (!$targetSheetName) {
            $targetSheetName = 'INVENTARIO GENERAL';
        }

        return [
            $targetSheetName => new InitialInventorySheetImport($this->schoolId)
        ];
    }
}
