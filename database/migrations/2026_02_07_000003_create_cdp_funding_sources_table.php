<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdp_funding_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cdp_id')->constrained()->onDelete('cascade');
            $table->foreignId('funding_source_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_id')->constrained()->onDelete('cascade'); // Presupuesto específico (item+fuente)
            $table->decimal('amount', 15, 2); // Monto reservado de esta fuente
            $table->decimal('available_balance_at_creation', 15, 2); // Snapshot informativo del saldo al crear
            $table->timestamps();

            $table->index('cdp_id');
            $table->index('funding_source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdp_funding_sources');
    }
};
