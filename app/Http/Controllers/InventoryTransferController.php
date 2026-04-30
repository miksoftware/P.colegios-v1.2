<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InventoryTransferController extends Controller
{
    public function downloadPdf(InventoryTransfer $transfer)
    {
        // Verificar que pertenezca al colegio actual
        if ($transfer->school_id !== session('selected_school_id')) {
            abort(403, 'No autorizado para ver esta acta.');
        }

        $transfer->load('items.item.account', 'school', 'creator');

        $pdf = Pdf::loadView('pdf.inventory-transfer', [
            'transfer' => $transfer,
            'school' => $transfer->school,
        ]);

        return $pdf->stream('Acta_Reintegro_' . $transfer->consecutive . '.pdf');
    }
}
