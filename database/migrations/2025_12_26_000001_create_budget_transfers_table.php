<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('transfer_number');
            $table->foreignId('source_budget_id')->constrained('budgets')->onDelete('cascade');
            $table->foreignId('destination_budget_id')->constrained('budgets')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('source_previous_amount', 15, 2);
            $table->decimal('source_new_amount', 15, 2);
            $table->decimal('destination_previous_amount', 15, 2);
            $table->decimal('destination_new_amount', 15, 2);
            $table->text('reason');
            $table->string('document_number', 50)->nullable();
            $table->date('document_date')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['school_id', 'transfer_number', 'fiscal_year']);
            $table->index(['school_id', 'fiscal_year']);
            $table->index(['source_budget_id']);
            $table->index(['destination_budget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transfers');
    }
};
