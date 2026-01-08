<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el tipo de ENUM a los nuevos valores
        DB::statement("ALTER TABLE funding_sources MODIFY COLUMN type ENUM('sgp', 'rp', 'rb', 'other') NOT NULL DEFAULT 'rp'");
    }

    public function down(): void
    {
        // Restaurar al ENUM original
        DB::statement("ALTER TABLE funding_sources MODIFY COLUMN type ENUM('internal', 'external') NOT NULL");
    }
};
