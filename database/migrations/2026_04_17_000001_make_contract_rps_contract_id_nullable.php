<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_rps', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->change();
        });

        Schema::table('cdps', function (Blueprint $table) {
            $table->foreignId('convocatoria_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contract_rps', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable(false)->change();
        });

        Schema::table('cdps', function (Blueprint $table) {
            $table->foreignId('convocatoria_id')->nullable(false)->change();
        });
    }
};
