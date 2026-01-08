<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Agregar campo funding_source_id
            $table->foreignId('funding_source_id')
                ->after('budget_item_id')
                ->constrained()
                ->onDelete('cascade');
            
            // Eliminar el índice único anterior
            $table->dropUnique(['school_id', 'budget_item_id', 'fiscal_year']);
            
            // Crear nuevo índice único que incluye funding_source_id y type
            $table->unique(['school_id', 'budget_item_id', 'funding_source_id', 'fiscal_year', 'type'], 'budgets_unique_composite');
            
            // Índice para consultas por fuente
            $table->index(['funding_source_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['funding_source_id', 'fiscal_year']);
            $table->dropUnique('budgets_unique_composite');
            
            // Eliminar foreign key y columna
            $table->dropForeign(['funding_source_id']);
            $table->dropColumn('funding_source_id');
            
            // Restaurar índice único original
            $table->unique(['school_id', 'budget_item_id', 'fiscal_year']);
        });
    }
};
