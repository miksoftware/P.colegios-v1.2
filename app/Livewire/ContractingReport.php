<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ContractingReport extends Component
{
    public $schoolId;
    public $school;

    // Filtros
    public $filterYear;
    public $filterSupplier = '';
    public $filterStatus = '';

    // Datos
    public $rows = [];
    public $summary = [];

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

    public function updatedFilterSupplier()
    {
        $this->loadReport();
    }

    public function updatedFilterStatus()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $query = Contract::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->with([
                'supplier.municipality',
                'supplier.bankAccounts',
                'supervisor',
                'convocatoria.cdps.fundingSources.fundingSource',
                'convocatoria.cdps.budgetItem.accountingAccount',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'convocatoria.distributionDetails.expenseDistribution.budget.budgetItem.accountingAccount.parent.parent',
                'convocatoria.distributionDetails.expenseDistribution.budget.fundingSource',
                'convocatoria.selectedProposal',
                'convocatoria.proposals',
                'rps.cdp',
                'rps.fundingSources.fundingSource',
                'rps.fundingSources.budget.budgetItem.accountingAccount.parent.parent',
                'rps.fundingSources.budget.distributions.expenseCode',
                'paymentOrders' => fn($q) => $q->whereIn('status', ['approved', 'paid']),
            ]);

        if ($this->filterStatus) {
            $query->byStatus($this->filterStatus);
        }

        if ($this->filterSupplier) {
            $query->whereHas('supplier', function ($q) {
                $q->where('first_surname', 'like', "%{$this->filterSupplier}%")
                  ->orWhere('first_name', 'like', "%{$this->filterSupplier}%")
                  ->orWhere('document_number', 'like', "%{$this->filterSupplier}%");
            });
        }

        $contracts = $query->orderBy('contract_number')->get();

        // Precargar CDP → convocatoria_distribution → expense_distribution para asignación exacta
        $cdpIds = $contracts
            ->flatMap(fn($c) => $c->rps->pluck('cdp_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $distByCdp = [];
        if (!empty($cdpIds)) {
            $rows = \Illuminate\Support\Facades\DB::table('cdps as cdp')
                ->leftJoin('convocatoria_distributions as cvd', 'cvd.id', '=', 'cdp.convocatoria_distribution_id')
                ->leftJoin('expense_distributions as ed', 'ed.id', '=', 'cvd.expense_distribution_id')
                ->leftJoin('expense_codes as ec', 'ec.id', '=', 'ed.expense_code_id')
                ->whereIn('cdp.id', $cdpIds)
                ->selectRaw('cdp.id cdp_id, ed.id ed_id, ed.budget_id, ec.code ec_code, ec.name ec_name, ec.sifse_code')
                ->get();
            foreach ($rows as $row) {
                $distByCdp[$row->cdp_id] = [
                    'ed_id'     => $row->ed_id,
                    'budget_id' => $row->budget_id,
                    'ec_code'   => $row->ec_code,
                    'ec_name'   => $row->ec_name,
                    'sifse'     => $row->sifse_code,
                ];
            }
        }

        $this->rows = [];

        foreach ($contracts as $contract) {
            $supplier = $contract->supplier;
            $convocatoria = $contract->convocatoria;
            $supervisor = $contract->supervisor;

            // CDPs del contrato
            $cdps = $convocatoria?->cdps ?? collect();
            $firstCdp = $cdps->first();

            // RPs del contrato
            $rps = $contract->rps;
            $firstRp = $rps->first();

            // Distribuciones de la convocatoria (para obtener expense codes)
            $distributions = $convocatoria?->distributionDetails ?? collect();
            $firstDist = $distributions->first();
            $expenseCode = $firstDist?->expenseDistribution?->expenseCode;
            $budget = $firstDist?->expenseDistribution?->budget;
            $budgetItem = $budget?->budgetItem;
            $accountingAccount = $budgetItem?->accountingAccount;

            // Rubro presupuestal info
            $rubroCode = $expenseCode?->code ?? $budgetItem?->code ?? '';
            $rubroName = $expenseCode?->name ?? $budgetItem?->name ?? '';
            $sifseCode = $expenseCode?->sifse_code ?? '';

            // Cuenta contable info
            $acctCode = $accountingAccount?->code ?? '';
            $acctName = $accountingAccount?->name ?? '';
            $acctParent = $accountingAccount?->parent;
            $acctGrandParent = $acctParent?->parent;

            // Propuesta seleccionada
            $selectedProposal = $convocatoria?->selectedProposal;

            // Banco del proveedor
            $supplierBank = $supplier?->bankAccounts?->first();
            $bankName = $supplierBank?->bank_name ?? '';
            $bankAccountNumber = $supplierBank?->account_number ?? '';

            // Fecha CDP
            $fechaCdp = $firstCdp?->created_at?->format('Y-m-d') ?? '';

            // Fecha RP
            $fechaRp = $firstRp?->created_at?->format('Y-m-d') ?? '';

            // Número RP
            $rpNumber = $firstRp?->rp_number ?? '';

            // Liquidación
            $liquidationDate = '';
            $lastPayment = $contract->paymentOrders->sortByDesc('payment_date')->first();
            if ($lastPayment) {
                $liquidationDate = $lastPayment->payment_date?->format('Y-m-d');
            }

            // Construir filas por cada combinación RP + fuente de financiación.
            // Cada RP del contrato tiene un CDP que apunta a UNA expense_distribution exacta
            // (via convocatoria_distribution_id). Si no la tiene, caemos a la primera
            // distribución del budget como antes (CDPs legacy).
            // La clave se agranda con el rp_id para que RPs al mismo rubro+fuente
            // queden en filas separadas (no se fusionen).
            $rubroFuenteRows = []; // key único por RP+FS
            foreach ($rps->where('status', 'active') as $rp) {
                $exactInfo = $distByCdp[$rp->cdp_id] ?? null;

                foreach ($rp->fundingSources as $rpFs) {
                    $fs = $rpFs->fundingSource;
                    $b  = $rpFs->budget;
                    $bi = $b?->budgetItem;
                    $acct = $bi?->accountingAccount;

                    // Asignación exacta: si el CDP del RP tiene convocatoria_distribution_id
                    // y esa distribución cae en el mismo budget del rpFs, úsala.
                    $ec = null;
                    $sifseCode = '';
                    $rubroCode = '';
                    $rubroName = '';
                    if ($exactInfo && (int) $exactInfo['budget_id'] === (int) $rpFs->budget_id) {
                        $rubroCode = $exactInfo['ec_code'] ?? '';
                        $rubroName = $exactInfo['ec_name'] ?? '';
                        $sifseCode = $exactInfo['sifse'] ?? '';
                    } else {
                        // Fallback legacy: primera distribución del budget
                        $dist = $b?->distributions->first();
                        $ec   = $dist?->expenseCode;
                        $rubroCode = $ec?->code ?? $bi?->code ?? '';
                        $rubroName = $ec?->name ?? $bi?->name ?? '';
                        $sifseCode = $ec?->sifse_code ?? '';
                    }

                    $fsCode = $fs?->code ?? '';
                    $fsName = $fs?->name ?? '';
                    // Key = rp_id + rubro + fs para que cada combinación quede en fila propia.
                    $key    = "{$rp->id}|{$rubroCode}|{$fsCode}";

                    if (!isset($rubroFuenteRows[$key])) {
                        $rubroFuenteRows[$key] = [
                            'rp_id'        => $rp->id,
                            'rp_number'    => $rp->rp_number,
                            'rp_date'      => $rp->otrosi_date?->format('Y-m-d')
                                              ?? $contract->start_date?->format('Y-m-d')
                                              ?? $rp->created_at?->format('Y-m-d')
                                              ?? '',
                            'rubro_code'   => $rubroCode,
                            'rubro_name'   => $rubroName,
                            'sifse_code'   => $sifseCode,
                            'acct_code'    => $acct?->code ?? '',
                            'acct_name'    => $acct?->name ?? '',
                            'acct_parent'  => $acct?->parent,
                            'fs_code'      => $fsCode,
                            'fs_name'      => $fsName,
                            'amount'       => 0,
                        ];
                    }
                    $rubroFuenteRows[$key]['amount'] += (float) $rpFs->amount;
                }
            }

            // Si no hay RPs activos, intentar por CDPs (cada CDP en su propia fila)
            if (empty($rubroFuenteRows)) {
                foreach ($cdps as $cdp) {
                    $bi = $cdp->budgetItem;
                    $acct = $bi?->accountingAccount;

                    // Para CDP también intentamos asignación exacta si tiene convocatoria_distribution_id
                    $cdpInfo = $distByCdp[$cdp->id] ?? null;

                    foreach ($cdp->fundingSources as $cdpFs) {
                        $fs = $cdpFs->fundingSource;
                        if ($cdpInfo && $cdpInfo['budget_id'] === $cdpFs->budget_id) {
                            $rubroCode = $cdpInfo['ec_code'] ?? '';
                            $rubroName = $cdpInfo['ec_name'] ?? '';
                            $sifseCodeRow = $cdpInfo['sifse'] ?? '';
                        } else {
                            $rubroCode = $bi?->code ?? '';
                            $rubroName = $bi?->name ?? '';
                            $sifseCodeRow = '';
                        }
                        $fsCode    = $fs?->code ?? '';
                        $fsName    = $fs?->name ?? '';
                        $key       = "cdp{$cdp->id}|{$rubroCode}|{$fsCode}";
                        if (!isset($rubroFuenteRows[$key])) {
                            $rubroFuenteRows[$key] = [
                                'rp_id'        => null,
                                'rp_number'    => '',
                                'rp_date'      => '',
                                'rubro_code'   => $rubroCode,
                                'rubro_name'   => $rubroName,
                                'sifse_code'   => $sifseCodeRow,
                                'acct_code'    => $acct?->code ?? '',
                                'acct_name'    => $acct?->name ?? '',
                                'acct_parent'  => $acct?->parent,
                                'fs_code'      => $fsCode,
                                'fs_name'      => $fsName,
                                'amount'       => 0,
                            ];
                        }
                        $rubroFuenteRows[$key]['amount'] += (float) $cdpFs->amount;
                    }
                }
            }

            // Fuente de financiación consolidada (para compatibilidad con mapeos viejos)
            $fundingSources = [];
            foreach ($rubroFuenteRows as $r) {
                $fsKey = $r['fs_code'] ?: uniqid('fs_');
                if (!isset($fundingSources[$fsKey])) {
                    $fundingSources[$fsKey] = [
                        'name'   => $r['fs_name'] ? "{$r['fs_name']} ({$r['fs_code']})" : '',
                        'code'   => $r['fs_code'],
                        'amount' => 0,
                    ];
                }
                $fundingSources[$fsKey]['amount'] += $r['amount'];
            }

            // Si no hay fuentes ni rubros, usar valor del contrato como fallback
            if (empty($rubroFuenteRows)) {
                $rubroFuenteRows['_default'] = [
                    'rp_id'        => null,
                    'rp_number'    => $rpNumber,
                    'rp_date'      => $fechaRp,
                    'rubro_code'   => $rubroCode ?? '',
                    'rubro_name'   => $rubroName ?? '',
                    'sifse_code'   => $sifseCode ?? '',
                    'acct_code'    => $acctCode,
                    'acct_name'    => $acctName,
                    'acct_parent'  => $acctParent,
                    'fs_code'      => '',
                    'fs_name'      => '',
                    'amount'       => (float) $contract->total,
                ];
            }

            // Datos base del contrato (compartidos entre filas)
            $baseRow = [
                'rubro_name' => $rubroName,
                'prox_disp' => 0,
                'rubro_code' => $rubroCode,
                'cdp_number' => $firstCdp?->cdp_number ?? '',
                'disponibilidad' => (float) ($firstCdp?->total_amount ?? 0),
                'fecha_cdp' => $fechaCdp,
                'supplier_name' => $supplier?->full_name ?? '',
                'supplier_first_surname' => $supplier?->first_surname ?? '',
                'supplier_second_surname' => $supplier?->second_surname ?? '',
                'supplier_first_name' => $supplier?->first_name ?? '',
                'supplier_second_name' => $supplier?->second_name ?? '',
                'supplier_document' => $supplier?->document_number ?? '',
                'supplier_dv' => $supplier?->dv ?? '',
                'supplier_document_type' => $supplier?->document_type ?? '',
                'supplier_person_type' => $supplier?->person_type ?? '',
                'objeto' => $contract->object ?? '',
                'justificacion' => $contract->justification ?? $convocatoria?->justification ?? '',
                'expense_code' => $rubroCode,
                'sifse_code' => $sifseCode,
                'supervisor_name' => $supervisor ? ($supervisor->name . ' ' . $supervisor->surname) : '',
                'supervisor_document' => $supervisor?->identification_number ?? '',
                'supervisor_cargo' => 'RECTOR',
                'acct_code' => $acctCode,
                'acct_name' => $acctName,
                'acct_parent_code' => $acctParent?->code ?? '',
                'acct_parent_name' => $acctParent?->name ?? '',
                'acct_grandparent_code' => $acctGrandParent?->code ?? '',
                'acct_grandparent_name' => $acctGrandParent?->name ?? '',
                'necesidades' => $convocatoria?->justification ?? '',
                'duracion' => $contract->duration_days ?? 0,
                'duracion_label' => ($contract->duration_days ?? 0) . ' DÍAS',
                'riesgos' => 'NO GENERA RIESGOS',
                'supplier_address' => $supplier?->address ?? '',
                'supplier_phone' => $supplier?->phone ?? $supplier?->mobile ?? '',
                'supplier_city' => $supplier?->city ?? '',
                'supplier_regime' => $supplier?->tax_regime_name ?? '',
                'forma_pago' => $contract->payment_method_name ?? '',
                'fecha_rp' => $fechaRp,
                'fecha_inicio' => $contract->start_date?->format('Y-m-d') ?? '',
                'fecha_fin' => $contract->end_date?->format('Y-m-d') ?? '',
                'contract_number' => $contract->contract_number ?? '',
                'contract_formatted' => 'CONTRATO No. ' . $contract->formatted_number,
                'contract_date' => $contract->start_date?->format('Y-m-d') ?? '',
                'dependencia' => 'RECTORIA',
                'bank_account' => $bankAccountNumber,
                'bank_name' => $bankName,
                'fecha_liquidacion' => $liquidationDate,
                'plazo' => ($contract->duration_days ?? 0) . ' DÍAS',
                'modalidad' => $contract->modality_name ?? '',
                'rp_number' => $rpNumber,
                'criterio_evaluacion' => 'MENOR PRECIO',
                'lugar_ejecucion' => $contract->execution_place ?? '',
                'representante_legal' => ($supplier?->person_type === 'juridica') ? '' : 'N/A',
                'convocatoria_number' => $convocatoria?->convocatoria_number ?? '',
                'convocatoria_formatted' => $convocatoria ? str_pad($convocatoria->convocatoria_number, 3, '0', STR_PAD_LEFT) : '',
                'fecha_invitacion' => $convocatoria?->start_date?->format('Y-m-d') ?? '',
                'intro_manual' => $this->school->contracting_manual_approval_number
                    ? "En cumplimiento a lo establecido en el CAPÍTULO 2, Numeral 1 del Manual de contratación institucional aprobado mediante acuerdo No. " . $this->school->contracting_manual_approval_number . " de fecha " . ($this->school->contracting_manual_approval_date ?? '') . " por el Consejo Directivo."
                    : '',
                'presupuesto_asignado' => (float) ($convocatoria?->assigned_budget ?? 0),
                'fecha_max_propuesta' => $convocatoria?->start_date?->format('Y-m-d') ?? '',
                'hora_propuesta' => '',
                'fecha_revision' => $convocatoria?->evaluation_date?->format('Y-m-d') ?? '',
                'hora_revision' => '',
                'fecha_evaluacion' => $convocatoria?->evaluation_date?->format('Y-m-d') ?? '',
                'num_propuestas' => $convocatoria?->proposals_count ?? $convocatoria?->proposals?->count() ?? 0,
                'acuerdo_paa' => $this->school->budget_agreement_number ?? '',
                'fecha_acuerdo' => $this->school->budget_approval_date ?? '',
                'fecha_certificacion' => $fechaCdp,
                'status' => $contract->status,
                'status_name' => $contract->status_name,
                'status_color' => $contract->status_color,
                'id' => $contract->id,
            ];

            // Generar una fila por cada combinación RP + rubro + fuente de financiación.
            // El monto de cada fila es el amount del RPFS (valor real comprometido en esa
            // combinación). Subtotal e IVA se prorratean proporcional al monto de la fila
            // respecto al total del contrato, ya que el contrato no los guarda desglosados.
            $totalContract = (float) $contract->total;
            $totalAllRows  = collect($rubroFuenteRows)->sum('amount');

            foreach ($rubroFuenteRows as $r) {
                $ratio    = $totalAllRows > 0 ? $r['amount'] / $totalAllRows : 1;
                $acctP    = $r['acct_parent'];
                $acctGP   = $acctP?->parent;
                $row                              = $baseRow;
                $row['rubro_code']                = $r['rubro_code'];
                $row['rubro_name']                = $r['rubro_name'];
                $row['sifse_code']                = $r['sifse_code'];
                $row['expense_code']              = $r['rubro_code'];
                $row['acct_code']                 = $r['acct_code'];
                $row['acct_name']                 = $r['acct_name'];
                $row['acct_parent_code']          = $acctP?->code ?? '';
                $row['acct_parent_name']          = $acctP?->name ?? '';
                $row['acct_grandparent_code']     = $acctGP?->code ?? '';
                $row['acct_grandparent_name']     = $acctGP?->name ?? '';
                $row['funding_source']            = $r['fs_name'] ? "{$r['fs_name']} ({$r['fs_code']})" : '';
                $row['funding_source_codes']      = $r['fs_code'];
                // Si hay info específica del RP en esta fila, sobreescribir los campos del baseRow
                if (!empty($r['rp_number'])) {
                    $row['rp_number'] = $r['rp_number'];
                }
                if (!empty($r['rp_date'])) {
                    $row['fecha_rp'] = $r['rp_date'];
                }
                $row['subtotal']                  = round((float) $contract->subtotal * $ratio, 2);
                $row['iva']                       = round((float) $contract->iva * $ratio, 2);
                $row['total']                     = round($r['amount'], 2);
                $row['valor_letras']              = '';
                $this->rows[]                     = $row;
            }
        }

        // Resumen
        $collection = collect($this->rows);
        $this->summary = [
            // Contratos únicos (no filas), para que el conteo no se infle por los desgloses
            'total_contracts' => $collection->pluck('id')->unique()->count(),
            'total_amount' => $collection->sum('total'),
            'total_subtotal' => $collection->sum('subtotal'),
            'total_iva' => $collection->sum('iva'),
            'by_status' => $collection
                ->unique('id')
                ->groupBy('status_name')
                ->map(fn($g, $k) => [
                    'name' => $k ?: 'Sin estado',
                    'count' => $g->count(),
                    'total' => $g->sum('total'),
                ])->values()->toArray(),
            'by_funding' => $collection->groupBy('funding_source')->map(fn($g, $k) => [
                'name' => $k ?: 'Sin fuente',
                'count' => $g->count(),
                'total' => $g->sum('total'),
            ])->values()->toArray(),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        return "VIGENCIA {$this->filterYear} CONSOLIDADO";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.contracting-report');
    }
}
