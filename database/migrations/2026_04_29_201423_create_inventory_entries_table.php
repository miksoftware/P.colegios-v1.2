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
        Schema::create('inventory_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->integer('consecutive')->unsigned();
            $table->date('date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->decimal('total_value', 15, 2)->default(0);
            $table->text('observations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'consecutive']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_entries');
    }
};
