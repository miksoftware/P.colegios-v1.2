<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_code_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['budget_id', 'expense_code_id'], 'unique_budget_expense_code');
            $table->index(['school_id', 'budget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_distributions');
    }
};
