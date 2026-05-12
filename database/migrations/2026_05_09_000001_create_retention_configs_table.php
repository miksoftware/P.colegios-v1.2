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
        Schema::create('retention_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->unsignedSmallInteger('fiscal_year');

            $table->string('concept', 50)
                ->comment('compras, servicios, honorarios, arrendamiento_sitios_web, arrendamiento_inmuebles, transporte_pasajeros, reteiva, estampilla_procultura, estampilla_produlto_mayor, retencion_ica');

            $table->string('display_name', 150);
            $table->string('category', 20)
                ->comment('retefuente | reteiva | estampilla | ica');

            // Tarifas retefuente (dos tarifas según si declara renta)
            $table->decimal('rate_not_declares', 5, 2)->nullable();
            $table->decimal('rate_declares', 5, 2)->nullable();

            // Tarifa única (reteiva, estampillas, ICA)
            $table->decimal('rate', 5, 2)->nullable();

            // Base mínima para aplicar la retención
            $table->decimal('min_base', 15, 2)->default(0);

            // Código contable asociado
            $table->string('accounting_code', 150)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year', 'concept'], 'retention_configs_unique');
            $table->index(['school_id', 'fiscal_year']);
            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_configs');
    }
};
