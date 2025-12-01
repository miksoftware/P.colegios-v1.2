<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Convierte las cuentas contables de "por colegio" a "globales"
     * El catálogo PUC es único y se comparte entre todos los colegios.
     */
    public function up(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            // Primero eliminar la foreign key de school_id
            $table->dropForeign(['school_id']);
        });

        Schema::table('accounting_accounts', function (Blueprint $table) {
            // Eliminar índices existentes que incluyen school_id
            $table->dropUnique(['school_id', 'code']);
            $table->dropIndex(['school_id', 'level']);
            $table->dropIndex(['school_id', 'parent_id']);
            
            // Eliminar la columna school_id
            $table->dropColumn('school_id');
        });

        Schema::table('accounting_accounts', function (Blueprint $table) {
            // Crear nuevos índices sin school_id
            $table->unique('code');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            // Eliminar índices globales
            $table->dropUnique(['code']);
            $table->dropIndex(['level']);
        });

        Schema::table('accounting_accounts', function (Blueprint $table) {
            // Restaurar la columna school_id
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            
            // Restaurar índices por colegio
            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'level']);
            $table->index(['school_id', 'parent_id']);
        });
    }
};
