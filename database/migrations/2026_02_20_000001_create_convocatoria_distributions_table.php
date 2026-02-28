<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_distribution_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2); // Monto asignado de esta distribución
            $table->timestamps();

            $table->unique(['convocatoria_id', 'expense_distribution_id'], 'conv_dist_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria_distributions');
    }
};
