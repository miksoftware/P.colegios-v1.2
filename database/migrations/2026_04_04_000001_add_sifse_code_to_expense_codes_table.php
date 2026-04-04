<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->string('sifse_code', 10)->default('')->after('id');
        });

        // Drop old unique index on code alone
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // Add new composite unique
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->unique(['sifse_code', 'code'], 'unique_sifse_expense_code');
            $table->index('sifse_code');
        });
    }

    public function down(): void
    {
        Schema::table('expense_codes', function (Blueprint $table) {
            $table->dropIndex('expense_codes_sifse_code_index');
            $table->dropUnique('unique_sifse_expense_code');
        });

        Schema::table('expense_codes', function (Blueprint $table) {
            $table->unique('code');
            $table->dropColumn('sifse_code');
        });
    }
};
