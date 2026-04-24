<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->decimal('initial_balance', 15, 2)->default(0)->after('holder_name');
            $table->integer('initial_balance_year')->nullable()->after('initial_balance');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['initial_balance', 'initial_balance_year']);
        });
    }
};
