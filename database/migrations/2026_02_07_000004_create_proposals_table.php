<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->integer('proposal_number'); // 1, 2, 3...
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('score', 5, 2)->default(0); // Puntuación de evaluación
            $table->boolean('is_selected')->default(false); // ¿Es la propuesta ganadora?
            $table->timestamps();

            $table->unique(['convocatoria_id', 'proposal_number']);
            $table->unique(['convocatoria_id', 'supplier_id']);
            $table->index('convocatoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
