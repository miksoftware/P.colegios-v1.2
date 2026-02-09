<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('convocatoria_id')->constrained()->cascadeOnDelete();
            $table->integer('contract_number');
            $table->integer('fiscal_year');
            $table->string('contracting_modality'); // direct, minimum_amount, abbreviated, merit, public_tender
            $table->string('execution_place')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->default(0);
            $table->text('object'); // auto from convocatoria
            $table->text('justification')->nullable(); // auto from convocatoria
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 18, 2);
            $table->decimal('iva', 18, 2)->default(0);
            $table->decimal('total', 18, 2);
            $table->string('payment_method')->default('single'); // single, partial
            $table->string('status')->default('draft'); // draft, active, in_execution, completed, terminated, suspended
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['school_id', 'contract_number', 'fiscal_year']);
        });

        Schema::create('contract_rps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cdp_id')->constrained('cdps')->cascadeOnDelete();
            $table->integer('rp_number');
            $table->integer('fiscal_year');
            $table->decimal('total_amount', 18, 2);
            $table->string('status')->default('active'); // active, cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('rp_funding_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_rp_id')->constrained()->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained();
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rp_funding_sources');
        Schema::dropIfExists('contract_rps');
        Schema::dropIfExists('contracts');
    }
};
