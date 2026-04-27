<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_rps', function (Blueprint $table) {
            $table->date('otrosi_date')->nullable()->after('addition_justification');
        });
    }

    public function down(): void
    {
        Schema::table('contract_rps', function (Blueprint $table) {
            $table->dropColumn('otrosi_date');
        });
    }
};
