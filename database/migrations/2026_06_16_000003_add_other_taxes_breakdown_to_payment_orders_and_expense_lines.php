<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->json('other_taxes_breakdown')
                ->nullable()
                ->after('other_taxes_total');
        });

        Schema::table('payment_order_expense_lines', function (Blueprint $table) {
            $table->json('other_taxes_breakdown')
                ->nullable()
                ->after('retencion_ica');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('other_taxes_breakdown');
        });

        Schema::table('payment_order_expense_lines', function (Blueprint $table) {
            $table->dropColumn('other_taxes_breakdown');
        });
    }
};
