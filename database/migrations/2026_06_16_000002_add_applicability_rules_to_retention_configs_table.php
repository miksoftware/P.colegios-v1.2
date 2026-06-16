<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retention_configs', function (Blueprint $table) {
            $table->json('applicability_rules')
                ->nullable()
                ->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('retention_configs', function (Blueprint $table) {
            $table->dropColumn('applicability_rules');
        });
    }
};
