<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('extension_justification')->nullable()->after('extension_document_path');
            $table->text('addition_justification')->nullable()->after('addition_document_path');
        });

        Schema::table('contract_rps', function (Blueprint $table) {
            $table->boolean('is_addition')->default(false)->after('status');
            $table->text('addition_justification')->nullable()->after('is_addition');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['extension_justification', 'addition_justification']);
        });

        Schema::table('contract_rps', function (Blueprint $table) {
            $table->dropColumn(['is_addition', 'addition_justification']);
        });
    }
};
