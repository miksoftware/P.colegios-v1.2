<?php

namespace App\Http\Controllers;

use App\Models\InventoryDischarge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InventoryDischargePdfController extends Controller
{
    public function downloadPdf(InventoryDischarge $discharge)
    {
        if ($discharge->school_id !== session('selected_school_id')) {
            abort(403, 'No autorizado para ver este comprobante.');
        }

        $discharge->load('items', 'school');

        $pdf = Pdf::loadView('pdf.inventory-discharge', [
            'discharge' => $discharge,
            'school' => $discharge->school,
        ]);

        return $pdf->stream('Resolucion_Baja_' . ($discharge->resolution_number ?? $discharge->consecutive) . '.pdf');
    }
}
