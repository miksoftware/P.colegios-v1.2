<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\BudgetItem;
use App\Models\FundingSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RubrosFuentesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpiar toda la data presupuestal existente
        $this->cleanDatabase();

        // 2. Crear cuentas contables necesarias (si no existen)
        $accounts = $this->createAccountingAccounts();

        // 3. Crear rubros con sus fuentes de financiación
        $this->createBudgetItemsWithSources($accounts);

        $this->command->info('✅ Rubros y fuentes de financiación del FSE creados exitosamente.');
    }

    /**
     * Limpiar toda la data presupuestal y precontractual existente
     */
    private function cleanDatabase(): void
    {
        $this->command->info('Limpiando datos existentes...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Precontractual
        if (Schema::hasTable('cdp_funding_sources')) DB::table('cdp_funding_sources')->truncate();
        if (Schema::hasTable('proposals'))           DB::table('proposals')->truncate();
        if (Schema::hasTable('cdps'))                DB::table('cdps')->truncate();
        if (Schema::hasTable('convocatorias'))        DB::table('convocatorias')->truncate();

        // Ejecución
        if (Schema::hasTable('expense_executions'))    DB::table('expense_executions')->truncate();
        if (Schema::hasTable('expense_distributions')) DB::table('expense_distributions')->truncate();

        // Presupuesto
        if (Schema::hasTable('budget_modifications')) DB::table('budget_modifications')->truncate();
        if (Schema::hasTable('budget_transfers'))     DB::table('budget_transfers')->truncate();
        if (Schema::hasTable('incomes'))              DB::table('incomes')->truncate();
        if (Schema::hasTable('budgets'))              DB::table('budgets')->truncate();

        // Rubros y fuentes
        if (Schema::hasTable('funding_sources')) DB::table('funding_sources')->truncate();
        if (Schema::hasTable('budget_items'))    DB::table('budget_items')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('  ✓ Base de datos presupuestal limpia.');
    }

    /**
     * Crear las cuentas contables necesarias para los rubros del FSE
     * Devuelve un array asociativo ['alias' => id]
     */
    private function createAccountingAccounts(): array
    {
        $this->command->info('Creando cuentas contables...');

        $map = [];

        // ── Buscar o crear cuentas padre existentes ──
        $class4  = AccountingAccount::where('code', '4')->first();  // INGRESOS
        $group41 = AccountingAccount::where('code', '41')->first(); // OPERACIONALES
        $group42 = AccountingAccount::where('code', '42')->first(); // NO OPERACIONALES

        if (!$class4 || !$group41 || !$group42) {
            $this->command->error('No se encontraron las cuentas contables base (4, 41, 42). Ejecute AccountingAccountSeeder primero.');
            return $map;
        }

        // ═══════════════════════════════════════════════════
        // INGRESOS OPERACIONALES (41) - Venta de servicios
        // ═══════════════════════════════════════════════════

        // 4110 - VENTA DE SERVICIOS
        $c4110 = AccountingAccount::firstOrCreate(
            ['code' => '4110'],
            ['name' => 'VENTA DE SERVICIOS', 'level' => 3, 'parent_id' => $group41->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $c411005 = AccountingAccount::firstOrCreate(
            ['code' => '411005'],
            ['name' => 'SERVICIOS PARA LA COMUNIDAD', 'level' => 4, 'parent_id' => $c4110->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['servicios_comunidad'] = AccountingAccount::firstOrCreate(
            ['code' => '41100501'],
            ['name' => 'SERVICIOS COMUNIDAD GENERALES', 'level' => 5, 'parent_id' => $c411005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $map['servicios_ciclo'] = AccountingAccount::firstOrCreate(
            ['code' => '41100502'],
            ['name' => 'SERVICIOS CICLO COMPLEMENTARIO', 'level' => 5, 'parent_id' => $c411005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // INGRESOS NO OPERACIONALES (42) - Transferencias
        // ═══════════════════════════════════════════════════

        // 4220 - TRANSFERENCIAS RECIBIDAS
        $c4220 = AccountingAccount::firstOrCreate(
            ['code' => '4220'],
            ['name' => 'TRANSFERENCIAS RECIBIDAS', 'level' => 3, 'parent_id' => $group42->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        // 422005 - TRANSFERENCIAS SGP
        $c422005 = AccountingAccount::firstOrCreate(
            ['code' => '422005'],
            ['name' => 'TRANSFERENCIAS SGP', 'level' => 4, 'parent_id' => $c4220->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['sgp_gratuidad'] = AccountingAccount::firstOrCreate(
            ['code' => '42200501'],
            ['name' => 'SGP GRATUIDAD', 'level' => 5, 'parent_id' => $c422005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $map['sgp_calidad'] = AccountingAccount::firstOrCreate(
            ['code' => '42200502'],
            ['name' => 'SGP CALIDAD', 'level' => 5, 'parent_id' => $c422005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $map['sgp_participaciones'] = AccountingAccount::firstOrCreate(
            ['code' => '42200503'],
            ['name' => 'SGP PARTICIPACIONES Y COMPENSACIONES', 'level' => 5, 'parent_id' => $c422005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // 422010 - TRANSFERENCIAS DE OTRAS ENTIDADES
        $c422010 = AccountingAccount::firstOrCreate(
            ['code' => '422010'],
            ['name' => 'TRANSFERENCIAS DE OTRAS ENTIDADES', 'level' => 4, 'parent_id' => $c4220->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['transf_alcaldia_rp'] = AccountingAccount::firstOrCreate(
            ['code' => '42201001'],
            ['name' => 'TRANSFERENCIAS ALCALDÍA R.P.', 'level' => 5, 'parent_id' => $c422010->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $map['transf_alcaldia_calidad'] = AccountingAccount::firstOrCreate(
            ['code' => '42201002'],
            ['name' => 'TRANSFERENCIAS ALCALDÍA CALIDAD', 'level' => 5, 'parent_id' => $c422010->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // RENDIMIENTOS FINANCIEROS - Usar 42100501 existente
        // ═══════════════════════════════════════════════════

        $rendimientos = AccountingAccount::where('code', '42100501')->first();
        $map['rendimientos'] = $rendimientos ? $rendimientos->id : ($map['servicios_comunidad'] ?? 1);

        // ═══════════════════════════════════════════════════
        // DISPOSICIÓN DE ACTIVOS
        // ═══════════════════════════════════════════════════

        $c4230 = AccountingAccount::firstOrCreate(
            ['code' => '4230'],
            ['name' => 'OTROS INGRESOS NO OPERACIONALES', 'level' => 3, 'parent_id' => $group42->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $c423005 = AccountingAccount::firstOrCreate(
            ['code' => '423005'],
            ['name' => 'DISPOSICIÓN DE ACTIVOS', 'level' => 4, 'parent_id' => $c4230->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['venta_activos'] = AccountingAccount::firstOrCreate(
            ['code' => '42300501'],
            ['name' => 'VENTA DE ACTIVOS FIJOS', 'level' => 5, 'parent_id' => $c423005->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // DONACIONES
        // ═══════════════════════════════════════════════════

        $c423010 = AccountingAccount::firstOrCreate(
            ['code' => '423010'],
            ['name' => 'DONACIONES', 'level' => 4, 'parent_id' => $c4230->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['donaciones_ext'] = AccountingAccount::firstOrCreate(
            ['code' => '42301001'],
            ['name' => 'DONACIONES GOBIERNOS EXTRANJEROS', 'level' => 5, 'parent_id' => $c423010->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $map['donaciones_privado'] = AccountingAccount::firstOrCreate(
            ['code' => '42301002'],
            ['name' => 'DONACIONES SECTOR PRIVADO', 'level' => 5, 'parent_id' => $c423010->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // INDEMNIZACIONES
        // ═══════════════════════════════════════════════════

        $c423015 = AccountingAccount::firstOrCreate(
            ['code' => '423015'],
            ['name' => 'INDEMNIZACIONES', 'level' => 4, 'parent_id' => $c4230->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['indemnizaciones'] = AccountingAccount::firstOrCreate(
            ['code' => '42301501'],
            ['name' => 'INDEMNIZACIONES SEGUROS NO DE VIDA', 'level' => 5, 'parent_id' => $c423015->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // RECURSOS DEL BALANCE (SUPERÁVIT)
        // ═══════════════════════════════════════════════════

        $c423020 = AccountingAccount::firstOrCreate(
            ['code' => '423020'],
            ['name' => 'RECURSOS DEL BALANCE', 'level' => 4, 'parent_id' => $c4230->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['superavit'] = AccountingAccount::firstOrCreate(
            ['code' => '42302001'],
            ['name' => 'SUPERÁVIT FISCAL', 'level' => 5, 'parent_id' => $c423020->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        // ═══════════════════════════════════════════════════
        // TRANSFERENCIAS DE CAPITAL
        // ═══════════════════════════════════════════════════

        $c423025 = AccountingAccount::firstOrCreate(
            ['code' => '423025'],
            ['name' => 'TRANSFERENCIAS DE CAPITAL', 'level' => 4, 'parent_id' => $c4230->id, 'nature' => 'C', 'allows_movement' => false, 'is_active' => true]
        );

        $map['transf_capital'] = AccountingAccount::firstOrCreate(
            ['code' => '42302501'],
            ['name' => 'TRANSFERENCIAS DE CAPITAL RECIBIDAS', 'level' => 5, 'parent_id' => $c423025->id, 'nature' => 'C', 'allows_movement' => true, 'is_active' => true]
        )->id;

        $this->command->info('  ✓ Cuentas contables creadas/verificadas.');

        return $map;
    }

    /**
     * Crear todos los rubros presupuestales del FSE con sus fuentes de financiación
     */
    private function createBudgetItemsWithSources(array $accounts): void
    {
        $this->command->info('Creando rubros y fuentes de financiación...');

        /*
         * Estructura de cada rubro:
         * 'code' => Código del rubro
         * 'name' => Nombre
         * 'description' => Descripción
         * 'account' => Alias de la cuenta contable en $accounts
         * 'sources' => Array de fuentes de financiación [['code', 'name', 'type']]
         */
        $rubros = [

            // ═══════════════════════════════════════════════════════════════
            // INGRESOS CORRIENTES - INGRESOS NO TRIBUTARIOS
            // Venta de Bienes y Servicios
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'IC.1.1',
                'name' => 'Servicios para la comunidad, sociales y personales',
                'description' => 'Ventas incidentales de establecimientos no de mercado - Servicios para la comunidad, sociales y personales',
                'account' => 'servicios_comunidad',
                'sources' => [
                    ['code' => '1', 'name' => 'Recursos Operacionales', 'type' => 'rp'],
                ],
            ],
            [
                'code' => 'IC.1.2',
                'name' => 'Servicios para la comunidad - Ciclo Complementario (Escuelas Normales)',
                'description' => 'Ventas incidentales de establecimientos no de mercado - Servicios para la comunidad, sociales y personales - Ciclo Complementario (Escuelas Normales)',
                'account' => 'servicios_ciclo',
                'sources' => [
                    ['code' => '4', 'name' => 'Ciclo Complementario', 'type' => 'rp'],
                ],
            ],

            // ═══════════════════════════════════════════════════════════════
            // INGRESOS CORRIENTES - TRANSFERENCIAS CORRIENTES
            // Sistema General de Participaciones
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'IC.2.1',
                'name' => 'Calidad por gratuidad',
                'description' => 'Transferencias corrientes - SGP - Participación para educación - Calidad - Calidad por gratuidad',
                'account' => 'sgp_gratuidad',
                'sources' => [
                    ['code' => '2', 'name' => 'Gratuidad - SGP', 'type' => 'sgp'],
                ],
            ],
            [
                'code' => 'IC.2.2',
                'name' => 'A entidades territoriales distintas de participaciones y compensaciones',
                'description' => 'Transferencias corrientes - SGP - Participación para educación - Calidad - A entidades territoriales distintas de participaciones y compensaciones',
                'account' => 'sgp_participaciones',
                'sources' => [],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Transferencias de otras entidades del gobierno general
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'IC.3.1',
                'name' => 'Otras unidades de gobierno (R.P. Alcaldía)',
                'description' => 'Transferencias de otras entidades del gobierno general - Otras unidades de gobierno - Recursos Propios Alcaldía',
                'account' => 'transf_alcaldia_rp',
                'sources' => [
                    ['code' => '3', 'name' => 'Otras Transferencias', 'type' => 'other'],
                ],
            ],
            [
                'code' => 'IC.3.2',
                'name' => 'Otras unidades de gobierno (CALIDAD Alcaldía)',
                'description' => 'Transferencias de otras entidades del gobierno general - Otras unidades de gobierno - Calidad Alcaldía',
                'account' => 'transf_alcaldia_calidad',
                'sources' => [
                    ['code' => '6', 'name' => 'Calidad - SGP', 'type' => 'sgp'],
                ],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DE CAPITAL - DISPOSICIÓN DE ACTIVOS
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RC.1.1',
                'name' => 'Disposición de maquinaria y equipo',
                'description' => 'Recursos de capital - Disposición de activos no financieros - Disposición de activos fijos - Maquinaria y equipo',
                'account' => 'venta_activos',
                'sources' => [],
            ],
            [
                'code' => 'RC.1.2',
                'name' => 'Disposición de otros activos fijos',
                'description' => 'Recursos de capital - Disposición de activos no financieros - Disposición de activos fijos - Otros activos fijos',
                'account' => 'venta_activos',
                'sources' => [],
            ],
            [
                'code' => 'RC.1.3',
                'name' => 'Disposición de recursos biológicos cultivados',
                'description' => 'Recursos de capital - Disposición de activos no financieros - Disposición de recursos biológicos cultivados',
                'account' => 'venta_activos',
                'sources' => [],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DE CAPITAL - RENDIMIENTOS FINANCIEROS
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RC.2.1',
                'name' => 'Depósitos (Gratuidad) - Rendimientos Financieros',
                'description' => 'Recursos de capital - Rendimientos financieros - Depósitos - Gratuidad',
                'account' => 'rendimientos',
                'sources' => [
                    ['code' => '35', 'name' => 'Rendimientos Financieros Gratuidad', 'type' => 'other'],
                ],
            ],
            [
                'code' => 'RC.2.2',
                'name' => 'Depósitos (Recursos Propios IE.) - Rendimientos Financieros',
                'description' => 'Recursos de capital - Rendimientos financieros - Depósitos - Recursos Propios I.E.',
                'account' => 'rendimientos',
                'sources' => [
                    ['code' => '36', 'name' => 'Rendimientos Financieros Recursos Propios', 'type' => 'other'],
                ],
            ],
            [
                'code' => 'RC.2.3',
                'name' => 'Depósitos (FOME) - Rendimientos Financieros',
                'description' => 'Recursos de capital - Rendimientos financieros - Depósitos - FOME',
                'account' => 'rendimientos',
                'sources' => [
                    ['code' => '37', 'name' => 'Rendimientos Financieros FOME', 'type' => 'other'],
                ],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DE CAPITAL - TRANSFERENCIAS DE CAPITAL
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RC.3.0',
                'name' => 'Transferencias de capital',
                'description' => 'Recursos de capital - Transferencias de capital',
                'account' => 'transf_capital',
                'sources' => [],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DE CAPITAL - DONACIONES
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RC.4.1',
                'name' => 'Donaciones de gobiernos extranjeros - No condicionadas',
                'description' => 'Recursos de capital - Donaciones - De gobiernos extranjeros - No condicionadas a la adquisición de un activo',
                'account' => 'donaciones_ext',
                'sources' => [],
            ],
            [
                'code' => 'RC.4.2',
                'name' => 'Donaciones del sector privado - No condicionadas',
                'description' => 'Recursos de capital - Donaciones - Del sector privado - No condicionadas a la adquisición de un activo',
                'account' => 'donaciones_privado',
                'sources' => [
                    ['code' => '41', 'name' => 'Otros Recursos de Capital', 'type' => 'other'],
                ],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DE CAPITAL - INDEMNIZACIONES
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RC.5.0',
                'name' => 'Indemnizaciones relacionadas con seguros no de vida',
                'description' => 'Recursos de capital - Indemnizaciones relacionadas con seguros no de vida',
                'account' => 'indemnizaciones',
                'sources' => [],
            ],

            // ═══════════════════════════════════════════════════════════════
            // RECURSOS DEL BALANCE - SUPERÁVIT FISCAL
            // ═══════════════════════════════════════════════════════════════

            [
                'code' => 'RB.1',
                'name' => 'Superávit fiscal - Gratuidad',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de recursos de Gratuidad',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '32', 'name' => 'Superávit Gratuidad', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.2',
                'name' => 'Superávit fiscal - Recursos Propios (I.E.)',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Recursos Propios de la Institución Educativa',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '33', 'name' => 'Superávit Recursos Propios', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.3',
                'name' => 'Superávit fiscal - FOME',
                'description' => 'Recursos del balance - Superávit fiscal proveniente del Fondo de Mitigación de Emergencias (FOME)',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '34', 'name' => 'Superávit FOME', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.4',
                'name' => 'Superávit fiscal - Rendimientos Financieros Gratuidad',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Rendimientos Financieros de Gratuidad',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '35', 'name' => 'Rendimientos Financieros Gratuidad', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.5',
                'name' => 'Superávit fiscal - Rendimientos Financieros Recursos Propios (IE.)',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Rendimientos Financieros de Recursos Propios',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '36', 'name' => 'Rendimientos Financieros Recursos Propios', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.6',
                'name' => 'Superávit fiscal - Rendimientos Financieros FOME',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Rendimientos Financieros FOME',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '37', 'name' => 'Rendimientos Financieros FOME', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.7',
                'name' => 'Superávit fiscal - Otros Recursos de Capital (R.P. Alcaldía)',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Otros Recursos de Capital R.P. Alcaldía',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '41', 'name' => 'Otros Recursos de Capital', 'type' => 'rb'],
                ],
            ],
            [
                'code' => 'RB.8',
                'name' => 'Superávit fiscal - Transferencias Calidad',
                'description' => 'Recursos del balance - Superávit fiscal proveniente de Transferencias de Calidad',
                'account' => 'superavit',
                'sources' => [
                    ['code' => '42', 'name' => 'Superávit Transferencias Calidad', 'type' => 'rb'],
                ],
            ],
        ];

        $rubroCount = 0;
        $fuenteCount = 0;

        foreach ($rubros as $rubro) {
            $accountId = $accounts[$rubro['account']] ?? null;

            $budgetItem = BudgetItem::create([
                'code' => $rubro['code'],
                'name' => $rubro['name'],
                'description' => $rubro['description'],
                'accounting_account_id' => $accountId,
                'is_active' => true,
            ]);
            $rubroCount++;

            foreach ($rubro['sources'] as $source) {
                FundingSource::create([
                    'budget_item_id' => $budgetItem->id,
                    'code' => $source['code'],
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'description' => "Fuente {$source['code']} - {$source['name']} para {$rubro['name']}",
                    'is_active' => true,
                ]);
                $fuenteCount++;
            }
        }

        $this->command->info("  ✓ {$rubroCount} rubros creados.");
        $this->command->info("  ✓ {$fuenteCount} fuentes de financiación creadas.");
    }
}
