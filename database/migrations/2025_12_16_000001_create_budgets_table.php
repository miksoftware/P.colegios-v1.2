<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_item_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('initial_amount', 15, 2);
            $table->decimal('current_amount', 15, 2);
            $table->year('fiscal_year');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'budget_item_id', 'fiscal_year']);
            $table->index(['school_id', 'fiscal_year']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
