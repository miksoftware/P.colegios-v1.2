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
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            
            $table->string('old_location')->nullable();
            $table->string('new_location')->nullable();
            
            $table->foreignId('old_account_id')->nullable()->constrained('inventory_accounting_accounts')->nullOnDelete();
            $table->foreignId('new_account_id')->nullable()->constrained('inventory_accounting_accounts')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
    }
};
