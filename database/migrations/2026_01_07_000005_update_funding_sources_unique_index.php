<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El código de fuente de financiación debe ser único por rubro y colegio,
     * no solo por colegio. Esto permite tener el mismo código (ej: 1-RP)
     * para diferentes rubros del mismo colegio.
     */
    public function up(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            // Eliminar el índice único anterior
            $table->dropUnique('funding_sources_school_id_code_unique');
            
            // Crear nuevo índice único que incluye el budget_item_id
            $table->unique(['school_id', 'budget_item_id', 'code'], 'funding_sources_school_item_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropUnique('funding_sources_school_item_code_unique');
            $table->unique(['school_id', 'code'], 'funding_sources_school_id_code_unique');
        });
    }
};
