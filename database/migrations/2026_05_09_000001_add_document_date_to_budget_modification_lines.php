<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega document_date a budget_modification_lines.
 *
 * Motivo: una adición "general" (a nivel budget) puede distribuirse por el usuario en
 * diferentes fechas fiscales dentro del módulo de Gastos. Cada línea necesita su
 * propia fecha para que los reportes por trimestre/semestre reflejen correctamente
 * cuándo se aplicó cada movimiento al rubro.
 *
 * Para registros existentes: se usa document_date de la modificación padre (fallback
 * que se mantiene en los reportes cuando la línea no tiene fecha).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_modification_lines', function (Blueprint $table) {
            $table->date('document_date')->nullable()->after('amount_after');
            $table->index('document_date');
        });
    }

    public function down(): void
    {
        Schema::table('budget_modification_lines', function (Blueprint $table) {
            $table->dropIndex(['document_date']);
            $table->dropColumn('document_date');
        });
    }
};
