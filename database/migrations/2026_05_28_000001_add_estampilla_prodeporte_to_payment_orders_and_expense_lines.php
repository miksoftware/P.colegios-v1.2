<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->decimal('estampilla_prodeporte', 18, 2)->default(0)->after('estampilla_procultura');
        });

        Schema::table('payment_order_expense_lines', function (Blueprint $table) {
            $table->decimal('estampilla_prodeporte', 15, 2)->default(0)->after('estampilla_procultura');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('estampilla_prodeporte');
        });

        Schema::table('payment_order_expense_lines', function (Blueprint $table) {
            $table->dropColumn('estampilla_prodeporte');
        });
    }
};