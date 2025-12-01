<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    /**
     * Plan Único de Cuentas básico para colegios (Global - para todos los colegios)
     */
    public function run(): void
    {
        // Verificar si ya existen cuentas
        if (AccountingAccount::exists()) {
            return;
        }

        $accounts = $this->getPUCStructure();

        foreach ($accounts as $accountData) {
            $this->createAccountWithChildren($accountData, null);
        }
    }

    protected function createAccountWithChildren(array $data, ?int $parentId): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $account = AccountingAccount::create([
            ...$data,
            'parent_id' => $parentId,
        ]);

        foreach ($children as $childData) {
            $this->createAccountWithChildren($childData, $account->id);
        }
    }

    protected function getPUCStructure(): array
    {
        return [
            // CLASE 1 - ACTIVO
            [
                'code' => '1',
                'name' => 'ACTIVO',
                'level' => 1,
                'nature' => 'D',
                'children' => [
                    [
                        'code' => '11',
                        'name' => 'DISPONIBLE',
                        'level' => 2,
                        'nature' => 'D',
                        'children' => [
                            [
                                'code' => '1105',
                                'name' => 'CAJA',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '110505',
                                        'name' => 'CAJA GENERAL',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '11050501', 'name' => 'CAJA GENERAL MONEDA NACIONAL', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '110510',
                                        'name' => 'CAJAS MENORES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '11051001', 'name' => 'CAJA MENOR ADMINISTRACIÓN', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '1110',
                                'name' => 'BANCOS',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '111005',
                                        'name' => 'BANCOS NACIONALES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '11100501', 'name' => 'BANCO DE BOGOTÁ', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                            ['code' => '11100502', 'name' => 'BANCOLOMBIA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                            ['code' => '11100503', 'name' => 'DAVIVIENDA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '13',
                        'name' => 'DEUDORES',
                        'level' => 2,
                        'nature' => 'D',
                        'children' => [
                            [
                                'code' => '1305',
                                'name' => 'CLIENTES',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '130505',
                                        'name' => 'CLIENTES NACIONALES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '13050501', 'name' => 'CLIENTES - MATRÍCULAS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                            ['code' => '13050502', 'name' => 'CLIENTES - PENSIONES', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '1330',
                                'name' => 'ANTICIPOS Y AVANCES',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '133005',
                                        'name' => 'A PROVEEDORES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '13300501', 'name' => 'ANTICIPOS A PROVEEDORES', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '15',
                        'name' => 'PROPIEDADES, PLANTA Y EQUIPO',
                        'level' => 2,
                        'nature' => 'D',
                        'children' => [
                            [
                                'code' => '1524',
                                'name' => 'EQUIPO DE OFICINA',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '152405',
                                        'name' => 'MUEBLES Y ENSERES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '15240501', 'name' => 'MUEBLES DE OFICINA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '1528',
                                'name' => 'EQUIPO DE COMPUTACIÓN',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '152805',
                                        'name' => 'EQUIPOS DE PROCESAMIENTO DE DATOS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '15280501', 'name' => 'COMPUTADORES', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                            ['code' => '15280502', 'name' => 'IMPRESORAS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CLASE 2 - PASIVO
            [
                'code' => '2',
                'name' => 'PASIVO',
                'level' => 1,
                'nature' => 'C',
                'children' => [
                    [
                        'code' => '21',
                        'name' => 'OBLIGACIONES FINANCIERAS',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '2105',
                                'name' => 'BANCOS NACIONALES',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '210505',
                                        'name' => 'SOBREGIROS',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '21050501', 'name' => 'SOBREGIROS BANCARIOS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '22',
                        'name' => 'PROVEEDORES',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '2205',
                                'name' => 'PROVEEDORES NACIONALES',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '220505',
                                        'name' => 'PROVEEDORES NACIONALES',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '22050501', 'name' => 'PROVEEDORES GENERALES', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '23',
                        'name' => 'CUENTAS POR PAGAR',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '2365',
                                'name' => 'RETENCIÓN EN LA FUENTE',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '236505',
                                        'name' => 'SALARIOS Y PAGOS LABORALES',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '23650501', 'name' => 'RETENCIÓN SALARIOS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '2370',
                                'name' => 'RETENCIONES Y APORTES DE NÓMINA',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '237005',
                                        'name' => 'APORTES A EPS',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '23700501', 'name' => 'APORTES EPS EMPLEADOS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '237010',
                                        'name' => 'APORTES A PENSIÓN',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '23701001', 'name' => 'APORTES PENSIÓN EMPLEADOS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CLASE 3 - PATRIMONIO
            [
                'code' => '3',
                'name' => 'PATRIMONIO',
                'level' => 1,
                'nature' => 'C',
                'children' => [
                    [
                        'code' => '31',
                        'name' => 'CAPITAL SOCIAL',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '3105',
                                'name' => 'CAPITAL SUSCRITO Y PAGADO',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '310505',
                                        'name' => 'CAPITAL AUTORIZADO',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '31050501', 'name' => 'CAPITAL AUTORIZADO', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '36',
                        'name' => 'RESULTADOS DEL EJERCICIO',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '3605',
                                'name' => 'UTILIDAD DEL EJERCICIO',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '360505',
                                        'name' => 'UTILIDAD DEL EJERCICIO',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '36050501', 'name' => 'UTILIDAD DEL EJERCICIO', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CLASE 4 - INGRESOS
            [
                'code' => '4',
                'name' => 'INGRESOS',
                'level' => 1,
                'nature' => 'C',
                'children' => [
                    [
                        'code' => '41',
                        'name' => 'OPERACIONALES',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '4105',
                                'name' => 'ACTIVIDADES EDUCATIVAS',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '410505',
                                        'name' => 'MATRÍCULAS',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '41050501', 'name' => 'MATRÍCULAS ORDINARIAS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                            ['code' => '41050502', 'name' => 'MATRÍCULAS EXTRAORDINARIAS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '410510',
                                        'name' => 'PENSIONES',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '41051001', 'name' => 'PENSIONES MENSUALES', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '410515',
                                        'name' => 'OTROS COBROS PERIÓDICOS',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '41051501', 'name' => 'TRANSPORTE ESCOLAR', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                            ['code' => '41051502', 'name' => 'ALIMENTACIÓN', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '42',
                        'name' => 'NO OPERACIONALES',
                        'level' => 2,
                        'nature' => 'C',
                        'children' => [
                            [
                                'code' => '4210',
                                'name' => 'FINANCIEROS',
                                'level' => 3,
                                'nature' => 'C',
                                'children' => [
                                    [
                                        'code' => '421005',
                                        'name' => 'INTERESES',
                                        'level' => 4,
                                        'nature' => 'C',
                                        'children' => [
                                            ['code' => '42100501', 'name' => 'INTERESES BANCARIOS', 'level' => 5, 'nature' => 'C', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CLASE 5 - GASTOS
            [
                'code' => '5',
                'name' => 'GASTOS',
                'level' => 1,
                'nature' => 'D',
                'children' => [
                    [
                        'code' => '51',
                        'name' => 'OPERACIONALES DE ADMINISTRACIÓN',
                        'level' => 2,
                        'nature' => 'D',
                        'children' => [
                            [
                                'code' => '5105',
                                'name' => 'GASTOS DE PERSONAL',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '510506',
                                        'name' => 'SUELDOS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51050601', 'name' => 'SUELDOS ADMINISTRATIVOS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                            ['code' => '51050602', 'name' => 'SUELDOS DOCENTES', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '510527',
                                        'name' => 'AUXILIO DE TRANSPORTE',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51052701', 'name' => 'AUXILIO TRANSPORTE', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '510530',
                                        'name' => 'CESANTÍAS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51053001', 'name' => 'CESANTÍAS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '510533',
                                        'name' => 'INTERESES SOBRE CESANTÍAS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51053301', 'name' => 'INTERESES CESANTÍAS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '510536',
                                        'name' => 'PRIMA DE SERVICIOS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51053601', 'name' => 'PRIMA DE SERVICIOS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '510539',
                                        'name' => 'VACACIONES',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51053901', 'name' => 'VACACIONES', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '5110',
                                'name' => 'HONORARIOS',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '511005',
                                        'name' => 'JUNTA DIRECTIVA',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51100501', 'name' => 'HONORARIOS JUNTA DIRECTIVA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '511010',
                                        'name' => 'REVISORÍA FISCAL',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51101001', 'name' => 'HONORARIOS REVISOR FISCAL', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '511015',
                                        'name' => 'ASESORÍA CONTABLE',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51101501', 'name' => 'HONORARIOS CONTADOR', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'code' => '5135',
                                'name' => 'SERVICIOS',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '513525',
                                        'name' => 'ACUEDUCTO Y ALCANTARILLADO',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51352501', 'name' => 'SERVICIO DE AGUA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '513530',
                                        'name' => 'ENERGÍA ELÉCTRICA',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51353001', 'name' => 'SERVICIO DE ENERGÍA', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '513535',
                                        'name' => 'TELÉFONO',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51353501', 'name' => 'SERVICIO TELEFÓNICO', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                    [
                                        'code' => '513595',
                                        'name' => 'OTROS SERVICIOS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '51359501', 'name' => 'INTERNET', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CLASE 6 - COSTOS DE VENTAS (simplificado para colegios)
            [
                'code' => '6',
                'name' => 'COSTOS DE OPERACIÓN',
                'level' => 1,
                'nature' => 'D',
                'children' => [
                    [
                        'code' => '61',
                        'name' => 'COSTO DE SERVICIOS EDUCATIVOS',
                        'level' => 2,
                        'nature' => 'D',
                        'children' => [
                            [
                                'code' => '6105',
                                'name' => 'MATERIALES EDUCATIVOS',
                                'level' => 3,
                                'nature' => 'D',
                                'children' => [
                                    [
                                        'code' => '610505',
                                        'name' => 'MATERIALES DIDÁCTICOS',
                                        'level' => 4,
                                        'nature' => 'D',
                                        'children' => [
                                            ['code' => '61050501', 'name' => 'MATERIALES DIDÁCTICOS', 'level' => 5, 'nature' => 'D', 'allows_movement' => true],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
