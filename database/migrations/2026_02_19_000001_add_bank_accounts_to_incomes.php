<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla pivote para distribución de ingresos a cuentas bancarias
        Schema::create('income_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')->constrained('incomes')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['income_id']);
        });

        // 2. Quitar payment_method y transaction_reference de incomes
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'transaction_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->enum('payment_method', ['transferencia', 'efectivo', 'cheque', 'consignacion', 'otro'])->nullable()->after('date');
            $table->string('transaction_reference')->nullable()->after('payment_method');
        });

        Schema::dropIfExists('income_bank_accounts');
    }
};
