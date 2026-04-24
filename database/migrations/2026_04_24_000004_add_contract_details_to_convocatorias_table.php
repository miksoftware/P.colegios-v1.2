<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->integer('estimated_duration_days')->nullable()->after('assigned_budget');
            $table->string('contracting_modality', 100)->default('especial')->after('estimated_duration_days');
            $table->string('requester_name', 255)->nullable()->after('contracting_modality');
            $table->string('requester_position', 255)->nullable()->after('requester_name');
        });
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn(['estimated_duration_days', 'contracting_modality', 'requester_name', 'requester_position']);
        });
    }
};
