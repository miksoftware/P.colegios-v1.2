<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            // Agregar campo code para el código del Ministerio (alfanumérico)
            $table->string('code', 10)->after('school_id');
            
            // Eliminar la relación con budget_item (ya no es necesaria)
            $table->dropForeign(['budget_item_id']);
            $table->dropColumn('budget_item_id');
            
            // Agregar índice único por colegio y código
            $table->unique(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            // Eliminar índice único
            $table->dropUnique(['school_id', 'code']);
            
            // Eliminar campo code
            $table->dropColumn('code');
            
            // Restaurar relación con budget_item
            $table->foreignId('budget_item_id')->after('school_id')->constrained()->onDelete('cascade');
        });
    }
};
