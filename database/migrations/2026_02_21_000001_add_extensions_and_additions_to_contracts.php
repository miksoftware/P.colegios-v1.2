<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Prórroga de tiempo
            $table->date('original_end_date')->nullable()->after('end_date');
            $table->integer('extension_days')->default(0)->after('original_end_date');
            $table->string('extension_document_path')->nullable()->after('extension_days');
            $table->timestamp('extension_date')->nullable()->after('extension_document_path');

            // Adición de recursos
            $table->decimal('original_total', 18, 2)->nullable()->after('total');
            $table->decimal('addition_amount', 18, 2)->default(0)->after('original_total');
            $table->string('addition_document_path')->nullable()->after('addition_amount');
            $table->timestamp('addition_date')->nullable()->after('addition_document_path');

            // Anulación
            $table->text('annulment_reason')->nullable()->after('status');
            $table->timestamp('annulment_date')->nullable()->after('annulment_reason');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'original_end_date',
                'extension_days',
                'extension_document_path',
                'extension_date',
                'original_total',
                'addition_amount',
                'addition_document_path',
                'addition_date',
                'annulment_reason',
                'annulment_date',
            ]);
        });
    }
};
