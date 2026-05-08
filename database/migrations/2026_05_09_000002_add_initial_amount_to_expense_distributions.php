<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega initial_amount a expense_distributions.
 *
 * Propósito: conservar el monto histórico de apropiación inicial del rubro
 * (el amount con el que se creó la distribución). Las adiciones/reducciones
 * posteriores cambian `amount` pero NO tocan `initial_amount`. Esto permite
 * al reporte de ejecución de gastos mostrar con precisión:
 *   apropiación_inicial = dist.initial_amount
 *   apropiación_definitiva = dist.amount
 *   adiciones/reducciones = suma exacta por budget_modification_lines
 *
 * Backfill: para registros existentes se copia `amount` → `initial_amount`.
 * Esto implica que cualquier modificación histórica previa queda considerada
 * como parte del inicial (no se puede reconstruir retroactivamente el monto
 * original sin data de creación). A partir de ahora el sistema registra bien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_distributions', function (Blueprint $table) {
            $table->decimal('initial_amount', 15, 2)->default(0)->after('amount');
        });

        // Backfill: initial_amount = amount para todos los registros existentes
        DB::statement('UPDATE expense_distributions SET initial_amount = amount');
    }

    public function down(): void
    {
        Schema::table('expense_distributions', function (Blueprint $table) {
            $table->dropColumn('initial_amount');
        });
    }
};
