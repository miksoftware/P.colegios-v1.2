<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_order_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_distribution_id')->constrained()->onDelete('restrict');
            $table->foreignId('expense_code_id')->constrained()->onDelete('restrict');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('retention_concept')->nullable();
            $table->boolean('supplier_declares_rent')->default(false);
            $table->decimal('retention_percentage', 5, 2)->default(0);
            $table->decimal('retefuente', 15, 2)->default(0);
            $table->decimal('reteiva', 15, 2)->default(0);
            $table->decimal('estampilla_produlto_mayor', 15, 2)->default(0);
            $table->decimal('estampilla_procultura', 15, 2)->default(0);
            $table->decimal('retencion_ica', 15, 2)->default(0);
            $table->decimal('total_retentions', 15, 2)->default(0);
            $table->decimal('net_payment', 15, 2)->default(0);
            $table->timestamps();

            $table->index('payment_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_order_expense_lines');
    }
};
