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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Usuario que realizó la acción
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Colegio donde se realizó la acción
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
            
            // Tipo de acción: created, updated, deleted
            $table->string('action', 50);
            
            // Modelo afectado (polimórfico)
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            
            // Nombre del módulo para filtros amigables
            $table->string('module', 100)->nullable();
            
            // Descripción legible de la acción
            $table->string('description')->nullable();
            
            // Valores anteriores (JSON)
            $table->json('old_values')->nullable();
            
            // Valores nuevos (JSON)
            $table->json('new_values')->nullable();
            
            // Información del cliente
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['user_id', 'created_at']);
            $table->index(['school_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['module', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
