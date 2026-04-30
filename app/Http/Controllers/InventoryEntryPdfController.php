<?php

namespace App\Http\Controllers;

use App\Models\InventoryEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InventoryEntryPdfController extends Controller
{
    public function downloadPdf(InventoryEntry $entry)
    {
        if ($entry->school_id !== session('selected_school_id')) {
            abort(403, 'No autorizado para ver este comprobante.');
        }

        $entry->load('items.account', 'school', 'supplier');

        $pdf = Pdf::loadView('pdf.inventory-entry', [
            'entry' => $entry,
            'school' => $entry->school,
        ]);

        return $pdf->stream('Comprobante_Entrada_' . str_pad($entry->consecutive, 4, '0', STR_PAD_LEFT) . '.pdf');
    }
}
