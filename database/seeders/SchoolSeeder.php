<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        School::create([
            'name' => 'SAN JUAN NEPOMUCENO',
            'nit' => '800011385-1',
            'dane_code' => '168867000087',
            'municipality' => 'VETAS',
            'rector_name' => 'EDGAR MAURICIO GALVIS',
            'rector_document' => '91283017',
            'pagador_name' => 'CLAUDIA YANETH MESA Q',
            'address' => 'CRA. 1 NO. 4 - 50',
            'email' => 'colegiosanjuannepomucenove@gmail.com',
            'phone' => '3112315944',
            'website' => null,
            'budget_agreement_number' => '3',
            'budget_approval_date' => '2024-10-30',
            'current_validity' => 2025,
            'contracting_manual_approval_number' => '188',
            'contracting_manual_approval_date' => '2024-10-30',
            'dian_resolution_1' => '18764084543372',
            'dian_resolution_2' => null,
            'dian_range_1' => '602-700',
            'dian_range_2' => null,
            'dian_expiration_1' => '2025-12-02',
            'dian_expiration_2' => null,
        ]);

        School::create([
            'name' => 'COLEGIO SANTA MARÍA',
            'nit' => '900123456-7',
            'dane_code' => '168867000088',
            'municipality' => 'BUCARAMANGA',
            'rector_name' => 'MARÍA JOSE RODRÍGUEZ',
            'rector_document' => '63012345',
            'pagador_name' => 'CARLOS PÉREZ',
            'address' => 'CALLE 45 # 12-34',
            'email' => 'contacto@santamaria.edu.co',
            'phone' => '6071234567',
            'website' => 'https://santamaria.edu.co',
            'budget_agreement_number' => '5',
            'budget_approval_date' => '2025-01-15',
            'current_validity' => 2025,
            'contracting_manual_approval_number' => '201',
            'contracting_manual_approval_date' => '2025-01-15',
            'dian_resolution_1' => '98765432109876',
            'dian_resolution_2' => '11111222233334',
            'dian_range_1' => '1000-2000',
            'dian_range_2' => '2001-3000',
            'dian_expiration_1' => '2026-01-15',
            'dian_expiration_2' => '2026-06-30',
        ]);

        School::create([
            'name' => 'INSTITUTO TÉCNICO INDUSTRIAL',
            'nit' => '800999888-2',
            'dane_code' => '168867000089',
            'municipality' => 'FLORIDABLANCA',
            'rector_name' => 'JUAN CARLOS MARTÍNEZ',
            'rector_document' => '88012345',
            'pagador_name' => 'ANA LÓPEZ',
            'address' => 'CARRERA 27 # 50-12',
            'email' => 'iti@educacion.gov.co',
            'phone' => '6079876543',
            'website' => null,
            'budget_agreement_number' => '12',
            'budget_approval_date' => '2024-12-01',
            'current_validity' => 2025,
            'contracting_manual_approval_number' => '150',
            'contracting_manual_approval_date' => '2024-12-01',
            'dian_resolution_1' => '55544433322211',
            'dian_resolution_2' => null,
            'dian_range_1' => '500-1500',
            'dian_range_2' => null,
            'dian_expiration_1' => '2025-11-30',
            'dian_expiration_2' => null,
        ]);
    }
}
