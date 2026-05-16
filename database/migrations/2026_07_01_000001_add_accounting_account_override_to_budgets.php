<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega un campo opcional de cuenta contable a los presupuestos.
     * Permite que cada colegio sobreescriba la cuenta contable del rubro
     * para casos donde distintos colegios usan cuentas diferentes para
     * el mismo rubro (ej.: gratuidad = 442805 para Departamento/Piedecuesta,
     * 470508 para Bucaramanga).
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('accounting_account_id')
                ->nullable()
                ->after('funding_source_id')
                ->constrained('accounting_accounts')
                ->nullOnDelete();

            $table->index(['school_id', 'accounting_account_id'], 'budgets_school_account_index');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex('budgets_school_account_index');
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};
