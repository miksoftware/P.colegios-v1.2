<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('code', 20)->nullable()->comment('Código bancario');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'is_active']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->string('account_number', 30);
            $table->enum('account_type', ['ahorros', 'corriente'])->default('ahorros');
            $table->string('holder_name', 200)->nullable()->comment('Titular de la cuenta');
            $table->string('description', 255)->nullable()->comment('Descripción o uso de la cuenta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['bank_id', 'account_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
    }
};
