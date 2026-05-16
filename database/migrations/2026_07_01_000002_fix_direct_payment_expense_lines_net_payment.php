<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige las líneas de gasto de pagos directos con CDP/RP donde
     * net_payment quedó en 0 por no haberse asignado al momento de crearlas.
     *
     * Para pagos directos no hay retenciones, por lo que net_payment = total.
     */
    public function up(): void
    {
        // Identificar expense_lines de pagos directos con net_payment = 0
        // pero total > 0 (líneas mal guardadas).
        DB::table('payment_order_expense_lines as el')
            ->join('payment_orders as po', 'po.id', '=', 'el.payment_order_id')
            ->where('po.payment_type', 'direct')
            ->where('el.net_payment', 0)
            ->where('el.total', '>', 0)
            ->update([
                'el.net_payment'       => DB::raw('el.total'),
                'el.total_retentions'  => 0,
                'el.retefuente'        => 0,
                'el.reteiva'           => 0,
                'el.estampilla_produlto_mayor' => 0,
                'el.estampilla_procultura'     => 0,
                'el.retencion_ica'     => 0,
            ]);
    }

    public function down(): void
    {
        // No se puede revertir un fix de datos sin saber los valores originales.
    }
};
