<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY COLUMN tax_regime ENUM('simplificado','simple','comun','gran_contribuyente','no_responsable') DEFAULT 'simplificado'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY COLUMN tax_regime ENUM('simplificado','comun','gran_contribuyente','no_responsable') DEFAULT 'simplificado'");
    }
};
