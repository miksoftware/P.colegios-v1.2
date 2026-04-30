<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('inventory_accounting_account_id')->constrained('inventory_accounting_accounts');
            $table->string('name');
            $table->decimal('initial_value', 15, 2)->default(0);
            $table->date('acquisition_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->enum('state', ['bueno', 'regular', 'malo'])->default('bueno');
            
            // Campos adicionales basados en Excel
            $table->string('current_tag')->nullable()->comment('Placa o código actual');
            $table->string('location')->nullable()->comment('Sede de ubicación');
            $table->string('funding_source')->nullable()->comment('Procedencia de recursos');
            $table->string('inventory_type')->nullable()->comment('Tipo de inventario: Devolutivo, Consumo, etc.');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
            $table->index('current_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
