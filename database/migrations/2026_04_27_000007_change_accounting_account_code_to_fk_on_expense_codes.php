<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the FK column
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->foreignId('accounting_account_id')->nullable()->after('name')->constrained('accounting_accounts')->onDelete('set null');
        });

        // Migrate data: match accounting_account_code to accounting_accounts.code
        $expenseCodes = DB::table('expense_codes')->whereNotNull('accounting_account_code')->get();
        foreach ($expenseCodes as $ec) {
            $account = DB::table('accounting_accounts')->where('code', $ec->accounting_account_code)->first();
            if ($account) {
                DB::table('expense_codes')->where('id', $ec->id)->update(['accounting_account_id' => $account->id]);
            }
        }

        // Drop the old text column
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->dropColumn('accounting_account_code');
        });
    }

    public function down(): void
    {
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->string('accounting_account_code', 20)->nullable()->after('name');
        });

        // Migrate data back
        $expenseCodes = DB::table('expense_codes')->whereNotNull('accounting_account_id')->get();
        foreach ($expenseCodes as $ec) {
            $account = DB::table('accounting_accounts')->where('id', $ec->accounting_account_id)->first();
            if ($account) {
                DB::table('expense_codes')->where('id', $ec->id)->update(['accounting_account_code' => $account->code]);
            }
        }

        Schema::table('expense_codes', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};
