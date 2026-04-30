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
    }
}
