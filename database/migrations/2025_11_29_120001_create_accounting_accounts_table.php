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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            
            // Código contable (ej: 1, 11, 1105, 110505, 11050501)
            $table->string('code', 20);
            
            // Nombre de la cuenta
            $table->string('name', 255);
            
            // Descripción opcional
            $table->text('description')->nullable();
            
            // Nivel de la cuenta (1=Clase, 2=Grupo, 3=Cuenta, 4=Subcuenta, 5=Auxiliar)
            $table->tinyInteger('level')->unsigned();
            
            // Referencia al padre (para jerarquía)
            $table->foreignId('parent_id')->nullable()->constrained('accounting_accounts')->onDelete('cascade');
            
            // Naturaleza de la cuenta (D=Débito, C=Crédito)
            $table->char('nature', 1)->default('D');
            
            // ¿Permite movimientos? (Solo auxiliares generalmente)
            $table->boolean('allows_movement')->default(false);
            
            // Estado activo/inactivo
            $table->boolean('is_active')->default(true);
            
            // Relación con el colegio
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'level']);
            $table->index(['school_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
