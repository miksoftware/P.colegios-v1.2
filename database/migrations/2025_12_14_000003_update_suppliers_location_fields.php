<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Agregar nuevas columnas
            $table->foreignId('department_id')->nullable()->after('address')->constrained()->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });

        // Eliminar columnas antiguas si existen
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('suppliers', 'department')) {
                $table->dropColumn('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address');
            $table->string('department', 100)->nullable()->after('city');
            
            $table->dropForeign(['department_id']);
            $table->dropForeign(['municipality_id']);
            $table->dropColumn(['department_id', 'municipality_id']);
        });
    }
};
