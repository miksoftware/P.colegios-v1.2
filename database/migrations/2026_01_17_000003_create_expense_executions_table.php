<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_distribution_id')->constrained()->onDelete('restrict');
            $table->foreignId('accounting_account_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->date('execution_date');
            $table->string('document_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'expense_distribution_id']);
            $table->index(['school_id', 'execution_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_executions');
    }
};
