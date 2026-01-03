<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_transfers', function (Blueprint $table) {
            $table->foreignId('source_funding_source_id')->nullable()->after('source_budget_id')->constrained('funding_sources')->onDelete('cascade');
            $table->foreignId('destination_funding_source_id')->nullable()->after('destination_budget_id')->constrained('funding_sources')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('budget_transfers', function (Blueprint $table) {
            $table->dropForeign(['source_funding_source_id']);
            $table->dropForeign(['destination_funding_source_id']);
            $table->dropColumn(['source_funding_source_id', 'destination_funding_source_id']);
        });
    }
};
