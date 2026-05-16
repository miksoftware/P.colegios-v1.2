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
        // Corregir expense_lines de pagos directos donde net_payment es NULL o 0
        // pero total > 0 (líneas creadas sin asignar net_payment explícitamente).
        // Para pagos directos sin retenciones: net_payment = total.
        DB::statement("
            UPDATE payment_order_expense_lines el
            JOIN payment_orders po ON po.id = el.payment_order_id
            SET
                el.net_payment             = el.total,
                el.total_retentions        = COALESCE(el.total_retentions, 0),
                el.retefuente              = COALESCE(el.retefuente, 0),
                el.reteiva                 = COALESCE(el.reteiva, 0),
                el.estampilla_produlto_mayor = COALESCE(el.estampilla_produlto_mayor, 0),
                el.estampilla_procultura   = COALESCE(el.estampilla_procultura, 0),
                el.retencion_ica           = COALESCE(el.retencion_ica, 0)
            WHERE po.payment_type = 'direct'
              AND (el.net_payment IS NULL OR el.net_payment = 0)
              AND el.total > 0
        ");
    }

    public function down(): void
    {
        // No se puede revertir un fix de datos sin saber los valores originales.
    }
};
