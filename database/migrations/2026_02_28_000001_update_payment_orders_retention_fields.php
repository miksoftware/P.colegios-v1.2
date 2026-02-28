<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            // Otros impuestos
            $table->decimal('estampilla_produlto_mayor', 18, 2)->default(0)->after('reteiva');
            $table->decimal('estampilla_procultura', 18, 2)->default(0)->after('estampilla_produlto_mayor');
            $table->decimal('retencion_ica', 18, 2)->default(0)->after('estampilla_procultura');
            $table->decimal('other_taxes_total', 18, 2)->default(0)->after('retencion_ica');

            // Cuenta bancaria del proveedor (snapshot)
            $table->string('supplier_bank_name')->nullable()->after('observations');
            $table->string('supplier_account_type')->nullable()->after('supplier_bank_name');
            $table->string('supplier_account_number')->nullable()->after('supplier_account_type');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn([
                'estampilla_produlto_mayor',
                'estampilla_procultura',
                'retencion_ica',
                'other_taxes_total',
                'supplier_bank_name',
                'supplier_account_type',
                'supplier_account_number',
            ]);
        });
    }
};
