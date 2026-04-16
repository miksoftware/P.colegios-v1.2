<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            // Tipo de pago: 'contract' (existente) o 'direct' (nuevo, sin contrato)
            $table->string('payment_type')->default('contract')->after('school_id');

            // Hacer contract_id nullable para pagos directos
            $table->foreignId('contract_id')->nullable()->change();

            // Proveedor directo (para pagos sin contrato)
            $table->foreignId('supplier_id')->nullable()->after('contract_id')->constrained('suppliers')->nullOnDelete();

            // CDP y RP opcionales para pagos directos
            $table->foreignId('cdp_id')->nullable()->after('supplier_id')->constrained('cdps')->nullOnDelete();
            $table->foreignId('contract_rp_id')->nullable()->after('cdp_id')->constrained('contract_rps')->nullOnDelete();

            // Descripción/concepto del pago directo
            $table->text('description')->nullable()->after('contract_rp_id');

            // Rubro presupuestal para pagos directos
            $table->foreignId('budget_item_id')->nullable()->after('description')->constrained('budget_items')->nullOnDelete();

            // Índice para búsquedas por tipo
            $table->index('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('cdp_id');
            $table->dropConstrainedForeignId('contract_rp_id');
            $table->dropConstrainedForeignId('budget_item_id');
            $table->dropColumn(['payment_type', 'description']);
        });
    }
};
