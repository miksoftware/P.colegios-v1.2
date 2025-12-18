<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('accounting_account_id')->constrained()->onDelete('restrict');
            $table->string('code', 20);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Código único por colegio
            $table->unique(['school_id', 'code']);
            
            // Índices para búsquedas frecuentes
            $table->index(['school_id', 'is_active']);
            $table->index(['school_id', 'accounting_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
