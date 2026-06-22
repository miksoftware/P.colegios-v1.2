<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Str;

class IncomeRegistrationCatalog
{   
    /**
     * Genera el catálogo visible para registrar ingresos múltiples.
     * El listado de códigos contables es independiente del rubro/fuente.
     */
    public static function buildForSchool(School $school): array
    {
        return collect(self::definitionsForSchool($school))
            ->filter(function (array $definition) use ($school) {
                $onlyMunicipality = $definition['only_municipality'] ?? null;
                if (!$onlyMunicipality) {
                    return true;
                }

                return Str::upper(trim((string) $school->municipality)) === Str::upper(trim((string) $onlyMunicipality));
            })
            ->map(function (array $definition) {
                return [
                    'key' => $definition['key'],
                    'accounting_code' => $definition['accounting_code'],
                    'label' => $definition['label'],
                    'display_name' => $definition['accounting_code'] . ' -> ' . $definition['label'],
                    'selected' => false,
                    'amount' => '',
                    'bank_id' => '',
                    'bank_account_id' => '',
                ];
            })
            ->values()
            ->all();
    }

    private static function definitionsForSchool(School $school): array
    {
        return [
            [
                'key' => 'certificados',
                'accounting_code' => '430550',
                'label' => 'Certificados',
            ],
            [
                'key' => 'venta_productos',
                'accounting_code' => '430550',
                'label' => 'Venta de Productos',
            ],
            [
                'key' => 'ciei',
                'accounting_code' => '430550',
                'label' => 'CIEI',
            ],
            [
                'key' => 'ciclo_complementario',
                'accounting_code' => '430550',
                'label' => 'Ciclo Complementario',
            ],
            [
                'key' => 'gratuidad',
                'accounting_code' => '442805',
                'label' => 'Gratuidad',
            ],
            [
                'key' => 'otras_transferencias',
                'accounting_code' => '442890',
                'label' => 'Otras transferencias',
            ],
            [
                'key' => 'gratuidad_bucaramanga',
                'accounting_code' => '470508',
                'label' => 'Gratuidad (solo Bucaramanga)',
                'only_municipality' => 'BUCARAMANGA',
            ],
            [
                'key' => 'otras_transferencias_bucaramanga',
                'accounting_code' => '470508',
                'label' => 'Otras transferencias (solo Bucaramanga)',
                'only_municipality' => 'BUCARAMANGA',
            ],
            [
                'key' => 'intereses_gratuidad',
                'accounting_code' => '480201',
                'label' => 'Intereses Gratuidad',
            ],
            [
                'key' => 'intereses_rec_propios',
                'accounting_code' => '480201',
                'label' => 'Intereses Rec. Propios',
            ],
            [
                'key' => 'arrendamientos',
                'accounting_code' => '480817',
                'label' => 'Arrendamientos',
            ],
            [
                'key' => 'otros_ingresos',
                'accounting_code' => '480890',
                'label' => 'Otros Ingresos',
            ],
        ];
    }
}
