<?php

namespace Database\Seeders;

use App\Models\RetentionConfig;
use App\Models\School;
use Illuminate\Database\Seeder;

class RetentionConfigSeeder extends Seeder
{
    /**
     * Carga las bases de retenciones actuales del sistema (extraídas de
     * las constantes en PaymentOrder y del cálculo en PostcontractualManagement)
     * para todos los colegios existentes y la vigencia actual.
     *
     * Es idempotente (updateOrCreate por school + fiscal_year + concept).
     */
    public function run(): void
    {
        $schools = School::all();

        if ($schools->isEmpty()) {
            $this->command?->warn('No hay colegios registrados. Se omite la carga de bases de retenciones.');
            return;
        }

        $fiscalYear = (int) now()->year;

        /**
         * Definiciones con los valores vigentes en el código.
         *
         * rate_not_declares / rate_declares:
         *   - Retefuente: tarifas para no-declara y declara renta.
         *   - Para categorías distintas a retefuente se dejan en null y se usa "rate".
         *
         * min_base: base mínima (subtotal) para aplicar la retención.
         * accounting_code: código contable asociado.
         */
        $definitions = [
            // ── Retefuente ────────────────────────────────────────
            [
                'concept'           => 'compras',
                'display_name'      => 'Retefuente Compras',
                'category'          => 'retefuente',
                'rate_not_declares' => 3.5,
                'rate_declares'     => 2.5,
                'rate'              => null,
                'min_base'          => 524000,
                'accounting_code'   => '243608 - Retención de Compras',
            ],
            [
                'concept'           => 'servicios',
                'display_name'      => 'Retefuente Servicios',
                'category'          => 'retefuente',
                'rate_not_declares' => 6.0,
                'rate_declares'     => 4.0,
                'rate'              => null,
                'min_base'          => 105000,
                'accounting_code'   => '243605 - Retenciones de Servicios y Arrendamientos',
            ],
            [
                'concept'           => 'honorarios',
                'display_name'      => 'Retefuente Honorarios',
                'category'          => 'retefuente',
                'rate_not_declares' => 10.0,
                'rate_declares'     => 11.0,
                'rate'              => null,
                'min_base'          => 1,
                'accounting_code'   => '243603 - Retención de Honorarios',
            ],
            [
                'concept'           => 'arrendamiento_sitios_web',
                'display_name'      => 'Retefuente Arrendamiento Sitios Web',
                'category'          => 'retefuente',
                'rate_not_declares' => 11.0,
                'rate_declares'     => 3.5,
                'rate'              => null,
                'min_base'          => 1,
                'accounting_code'   => '243605 - Retenciones de Servicios y Arrendamientos',
            ],
            [
                'concept'           => 'arrendamiento_inmuebles',
                'display_name'      => 'Retefuente Arrendamiento Bienes Inmuebles',
                'category'          => 'retefuente',
                'rate_not_declares' => 3.5,
                'rate_declares'     => 3.5,
                'rate'              => null,
                'min_base'          => 524000,
                'accounting_code'   => '243605 - Retenciones de Servicios y Arrendamientos',
            ],
            [
                'concept'           => 'transporte_pasajeros',
                'display_name'      => 'Retefuente Servicio de Transporte de Pasajeros',
                'category'          => 'retefuente',
                'rate_not_declares' => 3.5,
                'rate_declares'     => 3.5,
                'rate'              => null,
                'min_base'          => 524000,
                'accounting_code'   => '243605 - Retenciones de Servicios y Arrendamientos',
            ],

            // ── ReteIVA ───────────────────────────────────────────
            [
                'concept'           => 'reteiva',
                'display_name'      => 'ReteIVA',
                'category'          => 'reteiva',
                'rate_not_declares' => null,
                'rate_declares'     => null,
                'rate'              => 15.0,
                'min_base'          => 0,
                'accounting_code'   => '243625 - ReteIVA',
            ],

            // ── Estampillas (valores de Bucaramanga) ──────────────
            [
                'concept'           => 'estampilla_produlto_mayor',
                'display_name'      => 'Estampilla Produlto Mayor',
                'category'          => 'estampilla',
                'rate_not_declares' => null,
                'rate_declares'     => null,
                'rate'              => 2.0,
                'min_base'          => 1,
                'accounting_code'   => '24072204 - Estampilla Produlto Mayor',
            ],
            [
                'concept'           => 'estampilla_procultura',
                'display_name'      => 'Estampilla Procultura',
                'category'          => 'estampilla',
                'rate_not_declares' => null,
                'rate_declares'     => null,
                'rate'              => 2.0,
                'min_base'          => 35018010,
                'accounting_code'   => '24072202 - Estampilla Procultura',
            ],
            [
                'concept'           => 'estampilla_prodeporte',
                'display_name'      => 'Estampilla Prodeporte',
                'category'          => 'estampilla',
                'rate_not_declares' => null,
                'rate_declares'     => null,
                'rate'              => 2.0,
                'min_base'          => 35018010,
                'accounting_code'   => '24072203 - Estampilla Prodeporte',
            ],

            // ── ICA ───────────────────────────────────────────────
            [
                'concept'           => 'retencion_ica',
                'display_name'      => 'Retención ICA',
                'category'          => 'ica',
                'rate_not_declares' => null,
                'rate_declares'     => null,
                'rate'              => 0.0,
                'min_base'          => 0,
                'accounting_code'   => '24072209 - ReteICA',
            ],
        ];

        $created = 0;

        foreach ($schools as $school) {
            $municipality = strtolower(trim($school->municipality ?? ''));
            $isBucaramanga = str_contains($municipality, 'bucaramanga');

            foreach ($definitions as $def) {
                // Estampillas solo aplican en Bucaramanga según el comportamiento histórico del sistema.
                // Para los demás colegios se dejan creadas pero inactivas (el usuario puede activarlas
                // más adelante si el municipio empieza a cobrarlas).
                $isActive = true;
                if ($def['category'] === 'estampilla' && !$isBucaramanga) {
                    $isActive = false;
                }

                RetentionConfig::firstOrCreate(
                    [
                        'school_id'   => $school->id,
                        'fiscal_year' => $fiscalYear,
                        'concept'     => $def['concept'],
                    ],
                    [
                        'display_name'      => $def['display_name'],
                        'category'          => $def['category'],
                        'rate_not_declares' => $def['rate_not_declares'],
                        'rate_declares'     => $def['rate_declares'],
                        'rate'              => $def['rate'],
                        'min_base'          => $def['min_base'],
                        'accounting_code'   => $def['accounting_code'],
                        'is_active'         => $isActive,
                    ]
                );
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'Bases de retenciones cargadas para %d colegio(s) · vigencia %d · %d registros.',
            $schools->count(),
            $fiscalYear,
            $created
        ));
    }
}
