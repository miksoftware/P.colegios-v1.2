<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->string('accounting_account_code', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->dropColumn('accounting_account_code');
        });
    }
};
