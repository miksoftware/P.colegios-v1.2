<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('convocatoria_id')->constrained()->onDelete('cascade');
            $table->integer('cdp_number');
            $table->integer('fiscal_year');
            $table->foreignId('budget_item_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['active', 'used', 'cancelled'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['school_id', 'cdp_number', 'fiscal_year'], 'cdp_unique_number');
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdps');
    }
};
