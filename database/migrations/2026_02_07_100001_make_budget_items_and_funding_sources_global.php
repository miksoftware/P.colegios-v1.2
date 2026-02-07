<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // 1. BUDGET ITEMS: Eliminar duplicados y school_id
        // ============================================

        // Eliminar rubros duplicados (mismo code) quedándose con el de menor id
        $duplicates = DB::table('budget_items')
            ->select('code', DB::raw('MIN(id) as keep_id'))
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            // Reasignar relaciones de los duplicados al registro que se conserva
            $duplicateIds = DB::table('budget_items')
                ->where('code', $dup->code)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id');

            foreach ($duplicateIds as $dupId) {
                // Reasignar funding_sources
                DB::table('funding_sources')
                    ->where('budget_item_id', $dupId)
                    ->update(['budget_item_id' => $dup->keep_id]);

                // Reasignar budgets
                DB::table('budgets')
                    ->where('budget_item_id', $dupId)
                    ->update(['budget_item_id' => $dup->keep_id]);

                // Reasignar cdps
                if (Schema::hasTable('cdps')) {
                    DB::table('cdps')
                        ->where('budget_item_id', $dupId)
                        ->update(['budget_item_id' => $dup->keep_id]);
                }
            }

            // Eliminar los duplicados
            DB::table('budget_items')
                ->where('code', $dup->code)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('budget_items', function (Blueprint $table) {
            // Primero eliminar FK (para poder eliminar índices que usa)
            $table->dropForeign(['school_id']);

            // Eliminar índices que incluyen school_id
            $table->dropIndex('budget_items_school_id_accounting_account_id_index');

            // Eliminar columna school_id
            $table->dropColumn('school_id');

            // Crear nuevo índice único por código (global)
            $table->unique('code');
        });

        // ============================================
        // 2. FUNDING SOURCES: Eliminar duplicados y school_id
        // ============================================

        // Eliminar fuentes duplicadas (mismo budget_item_id + code) quedándose con el de menor id
        $fsDuplicates = DB::table('funding_sources')
            ->select('budget_item_id', 'code', DB::raw('MIN(id) as keep_id'))
            ->groupBy('budget_item_id', 'code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($fsDuplicates as $dup) {
            $duplicateIds = DB::table('funding_sources')
                ->where('budget_item_id', $dup->budget_item_id)
                ->where('code', $dup->code)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id');

            foreach ($duplicateIds as $dupId) {
                // Reasignar budgets
                DB::table('budgets')
                    ->where('funding_source_id', $dupId)
                    ->update(['funding_source_id' => $dup->keep_id]);

                // Reasignar incomes
                DB::table('incomes')
                    ->where('funding_source_id', $dupId)
                    ->update(['funding_source_id' => $dup->keep_id]);

                // Reasignar budget_transfers (source)
                DB::table('budget_transfers')
                    ->where('source_funding_source_id', $dupId)
                    ->update(['source_funding_source_id' => $dup->keep_id]);

                // Reasignar budget_transfers (destination)
                DB::table('budget_transfers')
                    ->where('destination_funding_source_id', $dupId)
                    ->update(['destination_funding_source_id' => $dup->keep_id]);

                // Reasignar cdp_funding_sources
                if (Schema::hasTable('cdp_funding_sources')) {
                    DB::table('cdp_funding_sources')
                        ->where('funding_source_id', $dupId)
                        ->update(['funding_source_id' => $dup->keep_id]);
                }
            }

            // Eliminar los duplicados
            DB::table('funding_sources')
                ->where('budget_item_id', $dup->budget_item_id)
                ->where('code', $dup->code)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('funding_sources', function (Blueprint $table) {
            // Primero eliminar FK
            $table->dropForeign(['school_id']);

            // Eliminar índices que incluyen school_id
            $table->dropUnique('funding_sources_school_item_code_unique');
            $table->dropIndex('funding_sources_school_id_type_index');

            // Eliminar columna school_id
            $table->dropColumn('school_id');

            // Crear nuevo índice único: budget_item_id + code (global, sin school)
            $table->unique(['budget_item_id', 'code'], 'funding_sources_item_code_unique');
        });
    }

    public function down(): void
    {
        // Restaurar school_id en budget_items
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->foreignId('school_id')->after('id')->default(1)->constrained()->onDelete('cascade');
            $table->index(['school_id', 'accounting_account_id'], 'budget_items_school_id_accounting_account_id_index');
        });

        // Restaurar school_id en funding_sources
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropUnique('funding_sources_item_code_unique');
            $table->foreignId('school_id')->after('id')->default(1)->constrained()->onDelete('cascade');
            $table->unique(['school_id', 'budget_item_id', 'code'], 'funding_sources_school_item_code_unique');
            $table->index(['school_id', 'type'], 'funding_sources_school_id_type_index');
        });
    }
};
