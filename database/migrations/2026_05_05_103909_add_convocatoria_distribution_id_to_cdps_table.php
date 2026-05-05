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
        Schema::table('cdps', function (Blueprint $table) {
            $table->unsignedBigInteger('convocatoria_distribution_id')->nullable()->after('convocatoria_id');
            $table->foreign('convocatoria_distribution_id')
                  ->references('id')
                  ->on('convocatoria_distributions')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cdps', function (Blueprint $table) {
            $table->dropForeign(['convocatoria_distribution_id']);
            $table->dropColumn('convocatoria_distribution_id');
        });
    }
};
