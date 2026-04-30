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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('consecutive');
            $table->date('transfer_date');
            
            // Quien entrega
            $table->string('from_name');
            $table->string('from_document')->nullable();
            $table->string('from_location')->nullable();
            
            // Quien recibe
            $table->string('to_name');
            $table->string('to_document')->nullable();
            $table->string('to_location')->nullable();
            
            $table->text('observations')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
