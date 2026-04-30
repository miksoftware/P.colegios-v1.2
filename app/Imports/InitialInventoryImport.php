<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InitialInventoryImport implements WithMultipleSheets
{
    protected $schoolId;

    public function __construct($schoolId)
    {
        $this->schoolId = $schoolId;
    }

    public function sheets(): array
    {
        return [
            0 => new InitialInventorySheetImport($this->schoolId)
        ];
    }
}
