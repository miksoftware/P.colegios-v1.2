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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nit');
            $table->string('dane_code');
            $table->string('municipality');
            $table->string('rector_name');
            $table->string('rector_document');
            $table->string('pagador_name')->nullable();
            $table->string('address');
            $table->string('email');
            $table->string('phone');
            $table->string('website')->nullable();
            $table->string('budget_agreement_number');
            $table->date('budget_approval_date');
            $table->year('current_validity');
            $table->string('contracting_manual_approval_number')->nullable();
            $table->date('contracting_manual_approval_date')->nullable();
            $table->string('dian_resolution_1');
            $table->string('dian_resolution_2')->nullable();
            $table->string('dian_range_1');
            $table->string('dian_range_2')->nullable();
            $table->date('dian_expiration_1');
            $table->date('dian_expiration_2')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
