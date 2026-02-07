<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_distribution_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('convocatoria_number');
            $table->integer('fiscal_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('object'); // Objeto a contratar
            $table->text('justification'); // Necesidad a satisfacer
            $table->decimal('assigned_budget', 15, 2); // Presupuesto asignado
            $table->boolean('requires_multiple_cdps')->default(false);
            $table->enum('status', ['draft', 'open', 'evaluation', 'awarded', 'cancelled'])->default('draft');
            $table->date('evaluation_date')->nullable();
            $table->integer('proposals_count')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['school_id', 'convocatoria_number', 'fiscal_year'], 'conv_unique_number');
            $table->index(['school_id', 'fiscal_year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
