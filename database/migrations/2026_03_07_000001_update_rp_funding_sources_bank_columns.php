<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rp_funding_sources', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('amount')->constrained('banks')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->after('bank_id')->constrained('bank_accounts')->nullOnDelete();
            $table->dropColumn(['bank_account_number', 'bank_name']);
        });
    }

    public function down(): void
    {
        Schema::table('rp_funding_sources', function (Blueprint $table) {
            $table->string('bank_account_number')->nullable()->after('amount');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['bank_id', 'bank_account_id']);
        });
    }
};
