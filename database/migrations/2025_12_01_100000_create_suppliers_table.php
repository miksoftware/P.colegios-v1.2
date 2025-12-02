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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            
            // Relación con el colegio
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // Tipo de documento
            $table->enum('document_type', ['CC', 'CE', 'NIT', 'TI', 'PA', 'RC', 'NUIP'])->default('CC');
            
            // Número de documento
            $table->string('document_number', 20);
            
            // Dígito de verificación (solo para NIT)
            $table->char('dv', 1)->nullable();
            
            // Nombres (para personas naturales)
            $table->string('first_name', 100)->nullable();
            $table->string('second_name', 100)->nullable();
            
            // Apellidos o Razón Social
            $table->string('first_surname', 150); // Obligatorio - Para NIT es la razón social
            $table->string('second_surname', 100)->nullable();
            
            // Tipo de persona
            $table->enum('person_type', ['natural', 'juridica'])->default('natural');
            
            // Régimen tributario
            $table->enum('tax_regime', [
                'simplificado',      // Régimen Simplificado
                'comun',             // Régimen Común
                'gran_contribuyente', // Gran Contribuyente
                'no_responsable'     // No Responsable de IVA
            ])->default('simplificado');
            
            // Información de contacto
            $table->string('address', 255);
            $table->string('city', 100);
            $table->string('department', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 150)->nullable();
            
            // Información bancaria (opcional)
            $table->string('bank_name', 100)->nullable();
            $table->enum('account_type', ['ahorros', 'corriente'])->nullable();
            $table->string('account_number', 30)->nullable();
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Notas adicionales
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['school_id', 'document_type', 'document_number']);
            $table->index(['school_id', 'is_active']);
            $table->index(['school_id', 'first_surname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
