<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use Illuminate\Database\Seeder;

/**
 * Crea las cuentas contables necesarias para vincular con los códigos de gasto.
 * Usa updateOrCreate para ser idempotente.
 */
class ExpenseAccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════
        // CLASE 1 - ACTIVO (ya existe id=1, code=1)
        // ═══════════════════════════════════════════════════
        $activo = AccountingAccount::where('code', '1')->first();
        if (!$activo) return;

        // Grupo 16 - PROPIEDAD PLANTA Y EQUIPO
        $g16 = AccountingAccount::updateOrCreate(
            ['code' => '16'],
            ['name' => 'PROPIEDAD PLANTA Y EQUIPO', 'level' => 2, 'parent_id' => $activo->id, 'nature' => 'D', 'allows_movement' => false, 'is_active' => true]
        );

        // Cuentas nivel 3 bajo grupo 16
        $c1610 = AccountingAccount::updateOrCreate(['code' => '1610'], ['name' => 'SEMOVIENTES Y PLANTAS', 'level' => 3, 'parent_id' => $g16->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);
        $c1655 = AccountingAccount::updateOrCreate(['code' => '1655'], ['name' => 'MAQUINARIA Y EQUIPO', 'level' => 3, 'parent_id' => $g16->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);
        $c1660 = AccountingAccount::updateOrCreate(['code' => '1660'], ['name' => 'EQUIPO MÉDICO Y CIENTÍFICO', 'level' => 3, 'parent_id' => $g16->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);
        $c1665 = AccountingAccount::updateOrCreate(['code' => '1665'], ['name' => 'MUEBLES Y ENSERES Y EQUIPO DE OFICINA', 'level' => 3, 'parent_id' => $g16->id, 'nature' => 'D', 'allows_movement' => false, 'is_active' => true]);
        $c1670 = AccountingAccount::updateOrCreate(['code' => '1670'], ['name' => 'EQUIPOS DE COMUNICACIÓN Y COMPUTACIÓN', 'level' => 3, 'parent_id' => $g16->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);

        // Subcuentas nivel 4 bajo 1665
        AccountingAccount::updateOrCreate(['code' => '165505'], ['name' => 'EQUIPO DE MÚSICA', 'level' => 4, 'parent_id' => $c1665->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);
        AccountingAccount::updateOrCreate(['code' => '165506'], ['name' => 'EQUIPO DE RECREACIÓN Y DEPORTE', 'level' => 4, 'parent_id' => $c1665->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);

        // Grupo 19 - OTROS ACTIVOS
        $g19 = AccountingAccount::updateOrCreate(
            ['code' => '19'],
            ['name' => 'OTROS ACTIVOS', 'level' => 2, 'parent_id' => $activo->id, 'nature' => 'D', 'allows_movement' => false, 'is_active' => true]
        );

        AccountingAccount::updateOrCreate(['code' => '1970'], ['name' => 'INTANGIBLES', 'level' => 3, 'parent_id' => $g19->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]);

        // ═══════════════════════════════════════════════════
        // CLASE 5 - GASTOS (ya existe id=71, code=5)
        // ═══════════════════════════════════════════════════
        $gastos = AccountingAccount::where('code', '5')->first();
        if (!$gastos) return;

        $g51 = AccountingAccount::where('code', '51')->first();
        if (!$g51) return;

        // Cuenta 5111 - GENERALES
        $c5111 = AccountingAccount::updateOrCreate(
            ['code' => '5111'],
            ['name' => 'GENERALES', 'level' => 3, 'parent_id' => $g51->id, 'nature' => 'D', 'allows_movement' => false, 'is_active' => true]
        );

        // Subcuentas nivel 4 bajo 5111
        $subcuentas5111 = [
            ['code' => '511114', 'name' => 'MATERIALES Y SUMINISTROS'],
            ['code' => '511115', 'name' => 'MANTENIMIENTO'],
            ['code' => '511117', 'name' => 'SERVICIOS PÚBLICOS'],
            ['code' => '511118', 'name' => 'ARRENDAMIENTOS'],
            ['code' => '511119', 'name' => 'VIÁTICOS Y GASTOS DE VIAJES'],
            ['code' => '511120', 'name' => 'IMPUESTOS, CONTRIBUCIONES Y TASAS'],
            ['code' => '511121', 'name' => 'IMPRESOS Y PUBLICACIONES'],
            ['code' => '511123', 'name' => 'COMUNICACIÓN Y TRANSPORTE'],
            ['code' => '511125', 'name' => 'SEGUROS GENERALES'],
            ['code' => '511137', 'name' => 'EVENTOS CULTURALES'],
            ['code' => '511141', 'name' => 'SOSTENIMIENTO DE SEMOVIENTES'],
            ['code' => '511179', 'name' => 'HONORARIOS'],
        ];

        foreach ($subcuentas5111 as $sub) {
            AccountingAccount::updateOrCreate(
                ['code' => $sub['code']],
                ['name' => $sub['name'], 'level' => 4, 'parent_id' => $c5111->id, 'nature' => 'D', 'allows_movement' => true, 'is_active' => true]
            );
        }

        $this->command?->info('  ✅ Cuentas contables para códigos de gasto creadas/actualizadas.');
    }
}
