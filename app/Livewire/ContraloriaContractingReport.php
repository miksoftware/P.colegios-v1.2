<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Contract;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ContraloriaContractingReport extends Component
{
    public $schoolId;
    public $school;

    public $filterYear;

    public $rows = [];
    public $totalBudget = 0;

    public function mount()
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->loadReport();
    }

    public function updatedFilterYear()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $year = (int) $this->filterYear;

        // Presupuesto total del sujeto vigilado (apropiación definitiva sumada)
        $this->totalBudget = (float) Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->sum('current_amount');

        $contracts = Contract::forSchool($this->schoolId)
            ->forYear($year)
            ->with([
                'supplier',
                'supervisor',
                'convocatoria.cdps.fundingSources.fundingSource',
                'convocatoria.cdps.budgetItem',
                'rps.cdp',
                'rps.fundingSources.fundingSource',
                'rps.fundingSources.budget.budgetItem',
                'paymentOrders' => fn($q) => $q->whereIn('status', ['approved', 'paid']),
            ])
            ->orderBy('contract_number')
            ->get();

        $this->rows = [];

        foreach ($contracts as $contract) {
            $supplier   = $contract->supplier;
            $supervisor = $contract->supervisor;
            $convocatoria = $contract->convocatoria;

            // CDP
            $cdps     = $convocatoria?->cdps ?? collect();
            $firstCdp = $cdps->first();
            $cdpBudgetItem = $firstCdp?->budgetItem;

            // RP principal (primer RP activo no-adición)
            $activeRps = $contract->rps->where('status', 'active')->where('is_addition', false);
            $firstRp   = $activeRps->first() ?? $contract->rps->where('status', 'active')->first();

            $rpBudgetItem = null;
            if ($firstRp) {
                $rpFs = $firstRp->fundingSources->first();
                $rpBudgetItem = $rpFs?->budget?->budgetItem;
            }

            // Fuente del recurso (primera fuente del RP, luego del CDP)
            $fundingSourceName = '';
            if ($firstRp && $firstRp->fundingSources->isNotEmpty()) {
                $fs = $firstRp->fundingSources->first()->fundingSource;
                $fundingSourceName = strtoupper($fs?->name ?? '');
            } elseif ($firstCdp && $firstCdp->fundingSources->isNotEmpty()) {
                $fs = $firstCdp->fundingSources->first()->fundingSource;
                $fundingSourceName = strtoupper($fs?->name ?? '');
            }

            // Pagos
            $payments      = $contract->paymentOrders;
            $paymentsCount = $payments->count();
            $paymentsTotal = (float) $payments->sum('total');
            $lastPayment   = $payments->sortByDesc('payment_date')->first();
            $liquidationDate = $lastPayment?->payment_date?->format('Y/m/d') ?? 'ND';

            // Adición
            $hasAddition    = ((float) $contract->addition_amount) > 0;
            $additionAmount = (float) ($contract->addition_amount ?? 0);

            // Prórroga
            $hasExtension    = ((int) ($contract->extension_days ?? 0)) > 0;
            $extensionDays   = (int) ($contract->extension_days ?? 0);

            // Clasificación de recursos por fuente para columnas 56-59
            $allRpFundingSources = collect();
            foreach ($contract->rps->where('status', 'active') as $rp) {
                foreach ($rp->fundingSources as $rpFs) {
                    $allRpFundingSources->push($rpFs);
                }
            }
            // Si no hay RPs, usar CDPs
            if ($allRpFundingSources->isEmpty()) {
                foreach ($cdps as $cdp) {
                    foreach ($cdp->fundingSources as $cdpFs) {
                        $allRpFundingSources->push($cdpFs);
                    }
                }
            }

            $valorPropios  = 0.0;
            $valorRegalias = 0.0;
            $valorSGP      = 0.0;
            $valorFNC      = 0.0;

            foreach ($allRpFundingSources as $fsItem) {
                $fs     = $fsItem->fundingSource ?? $fsItem->fundingSource ?? null;
                $amount = (float) ($fsItem->amount ?? 0);
                $fsType = $fs?->type ?? '';
                $fsCode = (string) ($fs?->code ?? '');

                if ($fsType === 'rp' || in_array($fsCode, ['1', '33'])) {
                    $valorPropios += $amount;
                } elseif ($fsType === 'sgp' || in_array($fsCode, ['2', '34'])) {
                    $valorSGP += $amount;
                } else {
                    // Por defecto asignar a propios si no se puede clasificar
                    $valorPropios += $amount;
                }
            }

            // NIT escuela sin dígito de verificación
            $schoolNitRaw = $this->school->nit ?? '';
            $schoolNitSinDv = preg_replace('/-\d+$/', '', $schoolNitRaw);

            $this->rows[] = [
                // Col 1
                'nit_sujeto'           => $schoolNitSinDv,
                // Col 2
                'nombre_sujeto'        => strtoupper($this->school->name ?? ''),
                // Col 3
                'direccion'            => strtoupper($this->school->address ?? ''),
                // Col 4
                'presupuesto_sujeto'   => $this->totalBudget,
                // Col 5
                'numero_contrato'      => strtoupper($contract->object
                    ? substr($contract->object, 0, 40)
                    : ('CONTRATO ' . str_pad($contract->contract_number, 3, '0', STR_PAD_LEFT))),
                // Col 6
                'regimen_contratacion' => 'OTRO',
                // Col 7
                'origen_presupuesto'   => 'NACIONAL',
                // Col 8
                'fuente_recurso'       => $fundingSourceName ?: 'ND',
                // Col 9
                'modalidad_seleccion'  => strtoupper($contract->modality_name ?? 'OTRA'),
                // Col 10
                'procedimiento'        => 'ND',
                // Col 11
                'clase_contrato'       => 'OTROS',
                // Col 12
                'tipo_gasto'           => 'FUNCIONAMIENTO',
                // Col 13
                'sector'               => 'EDUCACIÓN',
                // Col 14
                'publicado_secop'      => 'SI',
                // Col 15
                'actualizado_secop'    => 'SI',
                // Col 16
                'objeto'               => strtoupper($contract->object ?? ''),
                // Col 17
                'valor_inicial'        => (float) ($contract->original_total ?? $contract->total),
                // Col 18
                'no_cdp'               => $firstCdp?->cdp_number ?? 'ND',
                // Col 19
                'fecha_cdp'            => $firstCdp?->created_at?->format('Y/m/d') ?? 'ND',
                // Col 20
                'rubro_cdp'            => strtoupper($cdpBudgetItem?->name ?? 'ND'),
                // Col 21
                'valor_cdp'            => (float) ($firstCdp?->total_amount ?? 0),
                // Col 22
                'no_rp'                => $firstRp?->rp_number ?? 'ND',
                // Col 23
                'fecha_rp'             => $firstRp?->created_at?->format('Y/m/d') ?? 'ND',
                // Col 24
                'valor_rp'             => (float) ($firstRp?->total_amount ?? 0),
                // Col 25
                'rubro_rp'             => strtoupper($rpBudgetItem?->name ?? $cdpBudgetItem?->name ?? 'ND'),
                // Col 26
                'poliza_no'            => 'ND',
                // Col 27
                'fecha_poliza'         => 'ND',
                // Col 28
                'aseguradora'          => 'ND',
                // Col 29
                'cedula_contratista'   => $supplier?->document_number ?? '',
                // Col 30
                'nombre_contratista'   => strtoupper($supplier?->first_name ?? ''),
                // Col 31
                'apellidos_contratista'=> strtoupper(trim(($supplier?->first_surname ?? '') . ' ' . ($supplier?->second_surname ?? ''))),
                // Col 32
                'persona_tipo'         => strtoupper($supplier?->person_type === 'juridica' ? 'JURÍDICA' : 'NATURAL'),
                // Col 33
                'fecha_suscripcion'    => $contract->start_date?->format('Y/m/d') ?? 'ND',
                // Col 34
                'cedula_supervisor'    => $supervisor?->identification_number ?? 'ND',
                // Col 35
                'nombre_supervisor'    => strtoupper($supervisor?->name ?? 'ND'),
                // Col 36
                'apellido_supervisor'  => strtoupper($supervisor?->surname ?? 'ND'),
                // Col 37
                'vinculacion_supervisor'=> 'INTERNO',
                // Col 38
                'plazo_unidad'         => 'DIAS',
                // Col 39
                'plazo_numero'         => (int) ($contract->duration_days ?? 0),
                // Col 40
                'anticipo'             => 'NO',
                // Col 41
                'valor_anticipo'       => 0,
                // Col 42
                'fiducia'              => 'NO',
                // Col 43
                'hubo_adicion'         => $hasAddition ? 'SI' : 'NO',
                // Col 44
                'num_adiciones'        => $hasAddition ? 1 : 0,
                // Col 45
                'valor_adiciones'      => $additionAmount,
                // Col 46
                'hubo_prorroga'        => $hasExtension ? 'SI' : 'NO',
                // Col 47
                'prorroga_unidad'      => $hasExtension ? 'DIAS' : 'ND',
                // Col 48
                'prorroga_numero'      => $extensionDays,
                // Col 49
                'fecha_inicio'         => $contract->start_date?->format('Y/m/d') ?? 'ND',
                // Col 50
                'fecha_terminacion'    => $contract->end_date?->format('Y/m/d') ?? 'ND',
                // Col 51
                'num_pagos'            => $paymentsCount,
                // Col 52
                'valor_pagos'          => $paymentsTotal,
                // Col 53
                'fecha_acta_liquidacion' => $liquidationDate,
                // Col 54
                'urgencia_numero'      => 'ND',
                // Col 55
                'urgencia_fecha'       => 'ND',
                // Col 56
                'valor_propios'        => $valorPropios,
                // Col 57
                'valor_regalias'       => $valorRegalias,
                // Col 58
                'valor_sgp'            => $valorSGP,
                // Col 59
                'valor_fnc'            => $valorFNC,
                // Col 60
                'fecha_aut_vf'         => 'ND',
                // Col 61
                'vf_anio_inicio'       => 0,
                // Col 62
                'vf_anio_fin'          => 0,
                // Col 63
                'vf_monto_total'       => 0,
                // Col 64
                'vf_apropiado'         => 0,
                // Col 65
                'vf_ejecutada'         => 0,
                // Col 66
                'vf_saldo'             => 0,
                // Col 67
                'observaciones'        => 'ND',

                // Extras para la tabla de previsualización
                'contract_id'          => $contract->id,
                'status_name'          => $contract->status_name,
                'status_color'         => $contract->status_color,
            ];
        }

        $this->dispatch('reportLoaded');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.contraloria-contracting-report');
    }
}
