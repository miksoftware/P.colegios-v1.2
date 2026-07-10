<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->boolean('apply_retencion_ica')
                ->default(false)
                ->after('retencion_ica');

            $table->decimal('retencion_ica_percentage', 8, 4)
                ->default(0)
                ->after('apply_retencion_ica');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn([
                'apply_retencion_ica',
                'retencion_ica_percentage',
            ]);
        });
    }
};
