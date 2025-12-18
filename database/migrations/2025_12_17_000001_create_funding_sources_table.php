<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_item_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['internal', 'external']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'type']);
            $table->index(['budget_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_sources');
    }
};
