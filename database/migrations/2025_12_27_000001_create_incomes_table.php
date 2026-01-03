<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('funding_source_id')->constrained('funding_sources')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->enum('payment_method', ['transferencia', 'efectivo', 'cheque', 'consignacion', 'otro'])->nullable();
            $table->string('transaction_reference')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['school_id', 'date']);
            $table->index(['funding_source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
