<?php

namespace Database\Seeders;

use App\Models\ExpenseCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Limpia todos los códigos de gasto viejos y los reemplaza
 * con los nuevos que incluyen el código SIFSE.
 *
 * Este seeder se ejecuta UNA VEZ en deploy vía DeploySeeder.
 */
class RefreshExpenseCodesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ExpenseCode::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command?->info('  🗑️  Códigos de gasto anteriores eliminados.');

        // Re-ejecutar el seeder actualizado con códigos SIFSE
        $this->call(ExpenseCodeSeeder::class);

        $this->command?->info('  ✅ ' . ExpenseCode::count() . ' códigos de gasto insertados con código SIFSE.');
    }
}
