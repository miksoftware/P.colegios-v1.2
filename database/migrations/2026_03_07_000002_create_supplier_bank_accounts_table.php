<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name', 100);
            $table->enum('account_type', ['ahorros', 'corriente'])->default('ahorros');
            $table->string('account_number', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['supplier_id', 'account_number']);
        });

        // Migrar datos existentes de la tabla suppliers
        $suppliers = DB::table('suppliers')
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->whereNotNull('account_number')
            ->where('account_number', '!=', '')
            ->get();

        foreach ($suppliers as $supplier) {
            DB::table('supplier_bank_accounts')->insert([
                'supplier_id'    => $supplier->id,
                'bank_name'      => $supplier->bank_name,
                'account_type'   => $supplier->account_type ?? 'ahorros',
                'account_number' => $supplier->account_number,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Eliminar columnas viejas de suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_type', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('email');
            $table->enum('account_type', ['ahorros', 'corriente'])->nullable()->after('bank_name');
            $table->string('account_number', 30)->nullable()->after('account_type');
        });

        // Restaurar datos (primera cuenta de cada proveedor)
        $accounts = DB::table('supplier_bank_accounts')
            ->orderBy('id')
            ->get()
            ->unique('supplier_id');

        foreach ($accounts as $account) {
            DB::table('suppliers')
                ->where('id', $account->supplier_id)
                ->update([
                    'bank_name'      => $account->bank_name,
                    'account_type'   => $account->account_type,
                    'account_number' => $account->account_number,
                ]);
        }

        Schema::dropIfExists('supplier_bank_accounts');
    }
};
