<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_transfers', function (Blueprint $table) {
            $table->foreignId('source_expense_distribution_id')
                ->nullable()
                ->after('source_funding_source_id')
                ->constrained('expense_distributions')
                ->onDelete('cascade');

            $table->foreignId('destination_expense_distribution_id')
                ->nullable()
                ->after('destination_funding_source_id')
                ->constrained('expense_distributions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('budget_transfers', function (Blueprint $table) {
            $table->dropForeign(['source_expense_distribution_id']);
            $table->dropForeign(['destination_expense_distribution_id']);
            $table->dropColumn(['source_expense_distribution_id', 'destination_expense_distribution_id']);
        });
    }
};
