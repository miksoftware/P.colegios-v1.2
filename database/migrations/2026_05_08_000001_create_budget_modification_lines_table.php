<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_modification_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_modification_id')
                  ->constrained('budget_modifications')
                  ->onDelete('cascade');
            $table->foreignId('expense_distribution_id')
                  ->constrained('expense_distributions')
                  ->onDelete('cascade');
            $table->decimal('amount_before', 15, 2);
            $table->decimal('amount_after', 15, 2);
            $table->timestamps();

            $table->index('budget_modification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_modification_lines');
    }
};
