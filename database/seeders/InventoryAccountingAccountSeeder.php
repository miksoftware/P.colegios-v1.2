<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryAccountingAccount;

class InventoryAccountingAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // SEMOVIENTES (5 AÑOS)
            ['code' => '1.6.10', 'name' => 'SEMOVIENTES', 'depreciation_years' => 5],
            ['code' => '1.6.10.01', 'name' => 'De trabajo', 'depreciation_years' => 5],
            ['code' => '1.6.10.03', 'name' => 'De experimentación', 'depreciation_years' => 5],
            ['code' => '1.6.10.90', 'name' => 'Otros semovientes', 'depreciation_years' => 5],

            // EDIFICACIONES (50 AÑOS)
            ['code' => '1.6.40', 'name' => 'EDIFICACIONES', 'depreciation_years' => 50],
            ['code' => '1.6.40.01', 'name' => 'Edificios y casas', 'depreciation_years' => 50],
            ['code' => '1.6.40.02', 'name' => 'Oficinas', 'depreciation_years' => 50],
            ['code' => '1.6.40.09', 'name' => 'Colegios y escuelas', 'depreciation_years' => 50],

            // MAQUINARIA Y EQUIPO (10 AÑOS)
            ['code' => '1655', 'name' => 'MAQUINARIA Y EQUIPO', 'depreciation_years' => 10],
            ['code' => '165504', 'name' => 'Maquinaria industrial', 'depreciation_years' => 10],
            ['code' => '165505', 'name' => 'Equipo de música', 'depreciation_years' => 10],
            ['code' => '165506', 'name' => 'Equipo de recreación y deporte', 'depreciation_years' => 10],
            ['code' => '165508', 'name' => 'Equipo agrícola', 'depreciation_years' => 10],
            ['code' => '165509', 'name' => 'Equipo de enseñanza', 'depreciation_years' => 10],
            ['code' => '165510', 'name' => 'Armamento y vigilancia', 'depreciation_years' => 10],
            ['code' => '165511', 'name' => 'Herramientas y accesorios', 'depreciation_years' => 10],
            ['code' => '165523', 'name' => 'Equipo de aseo', 'depreciation_years' => 10],
            ['code' => '165590', 'name' => 'Otra maquinaria y equipo', 'depreciation_years' => 10],

            // MUEBLES, ENSERES Y EQUIPO DE OFICINA (10 AÑOS)
            ['code' => '1665', 'name' => 'MUEBLES, ENSERES Y EQUIPO DE OFICINA', 'depreciation_years' => 10],
            ['code' => '166501', 'name' => 'Muebles y enseres', 'depreciation_years' => 10],
            ['code' => '166502', 'name' => 'Equipo y máquina de oficina', 'depreciation_years' => 10],
            ['code' => '166590', 'name' => 'Otros muebles, enseres y equipo de oficina', 'depreciation_years' => 10],

            // EQUIPOS DE COMUNICACIÓN Y COMPUTACIÓN
            ['code' => '1670', 'name' => 'EQUIPOS DE COMUNICACIÓN Y COMPUTACIÓN', 'depreciation_years' => 10],
            ['code' => '167001', 'name' => 'Equipo de comunicación', 'depreciation_years' => 10],
            ['code' => '167002', 'name' => 'Equipo de computación', 'depreciation_years' => 5],
            ['code' => '167090', 'name' => 'Otros equipos de comunicación y computación', 'depreciation_years' => 5],

            // EQUIPOS DE TRANSPORTE, TRACCIÓN Y ELEVACIÓN (10 AÑOS)
            ['code' => '1675', 'name' => 'EQUIPOS DE TRANSPORTE, TRACCIÓN Y ELEVACIÓN', 'depreciation_years' => 10],
            ['code' => '167502', 'name' => 'Terrestre', 'depreciation_years' => 10],
            ['code' => '167590', 'name' => 'Otros equipos de transporte, tracción y elevación', 'depreciation_years' => 10],

            // EQUIPOS DE COMEDOR, COCINA, DESPENSA Y HOTELERÍA (10 AÑOS)
            ['code' => '1680', 'name' => 'EQUIPOS DE COMEDOR, COCINA, DESPENSA Y HOTELERÍA', 'depreciation_years' => 10],
            ['code' => '168002', 'name' => 'Equipo de restaurante y cafetería', 'depreciation_years' => 10],
            ['code' => '168090', 'name' => 'Otros equipos de comedor, cocina, despensa y hotelería', 'depreciation_years' => 10],

            // INTANGIBLES (5 AÑOS)
            ['code' => '1970', 'name' => 'INTANGIBLES', 'depreciation_years' => 5],
            ['code' => '197007', 'name' => 'Licencias', 'depreciation_years' => 5],
            ['code' => '197008', 'name' => 'Software', 'depreciation_years' => 5],
            ['code' => '197090', 'name' => 'Otros intangibles', 'depreciation_years' => 5],

            // SIN DEPRECIACIÓN
            ['code' => '830617', 'name' => 'Propiedades, planta y equipo', 'depreciation_years' => 0],
            ['code' => '830618', 'name' => 'Otros activos', 'depreciation_years' => 0],
            ['code' => '830690', 'name' => 'Otros bienes entregados en custodia', 'depreciation_years' => 0],

            // ── Variantes con puntos (formato AP-AI-RG-170) ──────────────────────────
            // Semovientes (5 años)
            ['code' => '1.6.10',    'name' => 'SEMOVIENTES', 'depreciation_years' => 5],
            ['code' => '1.6.10.01', 'name' => 'De trabajo', 'depreciation_years' => 5],
            ['code' => '1.6.10.03', 'name' => 'De experimentación', 'depreciation_years' => 5],
            ['code' => '1.6.10.90', 'name' => 'Otros semovientes', 'depreciation_years' => 5],
            // Edificaciones (50 años)
            ['code' => '1.6.40',    'name' => 'EDIFICACIONES', 'depreciation_years' => 50],
            ['code' => '1.6.40.01', 'name' => 'Edificios y casas', 'depreciation_years' => 50],
            ['code' => '1.6.40.02', 'name' => 'Oficinas', 'depreciation_years' => 50],
            ['code' => '1.6.40.09', 'name' => 'Colegios y escuelas', 'depreciation_years' => 50],
            // Maquinaria y equipo (10 años)
            ['code' => '1.6.55',    'name' => 'MAQUINARIA Y EQUIPO', 'depreciation_years' => 10],
            ['code' => '1.6.55.04', 'name' => 'Maquinaria industrial', 'depreciation_years' => 10],
            ['code' => '1.6.55.05', 'name' => 'Equipo de música', 'depreciation_years' => 10],
            ['code' => '1.6.55.06', 'name' => 'Equipo de recreación y deporte', 'depreciation_years' => 10],
            ['code' => '1.6.55.08', 'name' => 'Equipo agrícola', 'depreciation_years' => 10],
            ['code' => '1.6.55.09', 'name' => 'Equipo de enseñanza', 'depreciation_years' => 10],
            ['code' => '1.6.55.10', 'name' => 'Armamento y vigilancia', 'depreciation_years' => 10],
            ['code' => '1.6.55.11', 'name' => 'Herramientas y accesorios', 'depreciation_years' => 10],
            ['code' => '1.6.55.23', 'name' => 'Equipo de aseo', 'depreciation_years' => 10],
            ['code' => '1.6.55.90', 'name' => 'Otra maquinaria y equipo', 'depreciation_years' => 10],
            // Muebles, enseres y equipo de oficina (10 años)
            ['code' => '1.6.65',    'name' => 'MUEBLES, ENSERES Y EQUIPO DE OFICINA', 'depreciation_years' => 10],
            ['code' => '1.6.65.01', 'name' => 'Muebles y enseres', 'depreciation_years' => 10],
            ['code' => '1.6.65.02', 'name' => 'Equipo y máquina de oficina', 'depreciation_years' => 10],
            ['code' => '1.6.65.90', 'name' => 'Otros muebles, enseres y equipo de oficina', 'depreciation_years' => 10],
            // Comunicación y computación (10 años, excepto 1.6.70.02 y 1.6.70.90 que son 5)
            ['code' => '1.6.70',    'name' => 'EQUIPOS DE COMUNICACIÓN Y COMPUTACIÓN', 'depreciation_years' => 10],
            ['code' => '1.6.70.01', 'name' => 'Equipo de comunicación', 'depreciation_years' => 10],
            ['code' => '1.6.70.02', 'name' => 'Equipo de computación', 'depreciation_years' => 5],
            ['code' => '1.6.70.90', 'name' => 'Otros equipos de comunicación y computación', 'depreciation_years' => 5],
            // Transporte (10 años)
            ['code' => '1.6.75',    'name' => 'EQUIPOS DE TRANSPORTE, TRACCIÓN Y ELEVACIÓN', 'depreciation_years' => 10],
            ['code' => '1.6.75.02', 'name' => 'Terrestre', 'depreciation_years' => 10],
            ['code' => '1.6.75.90', 'name' => 'Otros equipos de transporte, tracción y elevación', 'depreciation_years' => 10],
            // Comedor (10 años)
            ['code' => '1.6.80',    'name' => 'EQUIPOS DE COMEDOR, COCINA, DESPENSA Y HOTELERÍA', 'depreciation_years' => 10],
            ['code' => '1.6.80.02', 'name' => 'Equipo de restaurante y cafetería', 'depreciation_years' => 10],
            ['code' => '1.6.80.90', 'name' => 'Otros equipos de comedor, cocina, despensa y hotelería', 'depreciation_years' => 10],
            // Intangibles (5 años)
            ['code' => '1.9.70',    'name' => 'INTANGIBLES', 'depreciation_years' => 5],
            ['code' => '1.9.70.07', 'name' => 'Licencias', 'depreciation_years' => 5],
            ['code' => '1.9.70.08', 'name' => 'Software', 'depreciation_years' => 5],
            ['code' => '1.9.70.90', 'name' => 'Otros intangibles', 'depreciation_years' => 5],
        ];

        foreach ($accounts as $account) {
            InventoryAccountingAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'depreciation_years' => $account['depreciation_years'],
                    'is_active' => true,
                ]
            );
        }

        // Corregir cuentas auto-generadas que ya existan con años incorrectos.
        // Se recorren TODAS las cuentas y se aplica la misma lógica del import.
        $corrections = [
            // Prefijo (sin puntos) => años correctos
            '1610' => 5,
            '1640' => 50,
            '1970' => 5,
            '197007' => 5, '197008' => 5, '197090' => 5,
            '167002' => 5, '167090' => 5,
        ];

        InventoryAccountingAccount::all()->each(function ($acct) use ($corrections) {
            $n = str_replace('.', '', $acct->code);
            foreach ($corrections as $prefix => $years) {
                if (str_starts_with($n, $prefix) && $acct->depreciation_years !== $years) {
                    $acct->update(['depreciation_years' => $years]);
                    break;
                }
            }
        });

        // Renombrar cualquier "Cuenta Autogenerada X" que ahora tenga un nombre correcto
        // definido en el array $accounts de arriba.
        $nameMap = [];
        foreach ($accounts as $a) {
            $nameMap[$a['code']] = $a['name'];
        }
        InventoryAccountingAccount::where('name', 'like', 'Cuenta Autogenerada %')->get()
            ->each(function ($acct) use ($nameMap) {
                if (isset($nameMap[$acct->code])) {
                    $acct->update(['name' => $nameMap[$acct->code]]);
                }
            });
    }
}
