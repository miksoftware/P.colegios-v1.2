<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las fuentes de financiación pertenecen a un rubro específico.
     * No se puede seleccionar una fuente sin antes seleccionar un rubro.
     */
    public function up(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->foreignId('budget_item_id')
                ->nullable()
                ->after('school_id')
                ->constrained('budget_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropForeign(['budget_item_id']);
            $table->dropColumn('budget_item_id');
        });
    }
};
