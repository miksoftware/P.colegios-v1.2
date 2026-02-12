<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->integer('payment_number');
            $table->integer('fiscal_year');

            // Factura
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('payment_date');

            // ¿Pago completo o parcial?
            $table->boolean('is_full_payment')->default(true);

            // Valores del pago
            $table->decimal('subtotal', 18, 2);
            $table->decimal('iva', 18, 2)->default(0);
            $table->decimal('total', 18, 2);

            // Retenciones DIAN
            $table->string('retention_concept')->nullable(); // compras, servicios, honorarios, arrendamiento
            $table->boolean('supplier_declares_rent')->default(false);
            $table->decimal('retention_percentage', 5, 2)->default(0);
            $table->decimal('retefuente', 18, 2)->default(0);
            $table->decimal('reteiva', 18, 2)->default(0);
            $table->decimal('total_retentions', 18, 2)->default(0);

            // Neto a pagar
            $table->decimal('net_payment', 18, 2);

            $table->text('observations')->nullable();
            $table->string('status')->default('draft'); // draft, approved, paid, cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['school_id', 'payment_number', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
