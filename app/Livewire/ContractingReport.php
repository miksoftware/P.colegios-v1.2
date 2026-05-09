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
                'rps.cdp.convocatoria',
                'rps.fundingSources.fundingSource',
                'rps.fundingSources.bank',
                'rps.fundingSources.bankAccount.bank',
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

        // Precargar pagos por RP para calcular "próximo disponible" (CDP - pagos)
        $rpIds = $contracts->flatMap(fn($c) => $c->rps->pluck('id'))->filter()->unique()->values()->all();
        $paymentsByRp = [];
        if (!empty($rpIds)) {
            $paymentsByRp = \App\Models\PaymentOrder::whereIn('contract_rp_id', $rpIds)
                ->whereIn('status', ['approved', 'paid'])
                ->selectRaw('contract_rp_id, SUM(total) as total_paid')
                ->groupBy('contract_rp_id')
                ->pluck('total_paid', 'contract_rp_id')
                ->toArray();
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

            // Cuenta y banco DE DONDE SALE EL DINERO (lado del colegio, no del proveedor).
            // Se toma del primer rp_funding_source del primer RP activo; cada fila del
            // desglose puede sobreescribir estos valores con su propia fuente específica.
            $firstRpFs = $firstRp?->fundingSources?->first();
            $rpBankFirst = $firstRpFs?->bank ?? $firstRpFs?->bankAccount?->bank;
            $bankName    = $rpBankFirst?->name ?? '';
            $bankAccountNumber = $firstRpFs?->bankAccount?->account_number ?? '';

            // Fecha CDP: se usa la fecha fiscal de la convocatoria (start_date) en lugar
            // del created_at (que es cuándo se registró en el sistema).
            $convocatoriaDate = $convocatoria?->start_date;
            $fechaCdp = $convocatoriaDate
                ? (is_string($convocatoriaDate) ? $convocatoriaDate : $convocatoriaDate->format('Y-m-d'))
                : '';

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
                // CDP número específico de este RP
                $rpCdpNumber = $rp->cdp?->cdp_number ?? '';
                // Fecha del CDP = fecha fiscal de la convocatoria a la que pertenece
                $rpConvDate  = $rp->cdp?->convocatoria?->start_date;
                $rpCdpDate   = $rpConvDate
                    ? (is_string($rpConvDate) ? $rpConvDate : $rpConvDate->format('Y-m-d'))
                    : '';

                // Próximo disponible del RP = CDP total - pagos ya realizados contra este RP
                $rpPaid  = (float) ($paymentsByRp[$rp->id] ?? 0);
                $rpProxDisp = max(0, (float) ($rp->cdp?->total_amount ?? 0) - $rpPaid);

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

                    // Banco / cuenta bancaria de DONDE SALE el dinero (lado del colegio,
                    // no del proveedor). Se toma del rp_funding_source.
                    $rpBank        = $rpFs->bank ?? $rpFs->bankAccount?->bank;
                    $rpBankAccount = $rpFs->bankAccount;
                    $rpBankName    = $rpBank?->name ?? '';
                    $rpBankAcctNum = $rpBankAccount?->account_number ?? '';

                    if (!isset($rubroFuenteRows[$key])) {
                        $rubroFuenteRows[$key] = [
                            'rp_id'        => $rp->id,
                            'rp_number'    => $rp->rp_number,
                            'rp_date'      => $rp->otrosi_date?->format('Y-m-d')
                                              ?? $contract->start_date?->format('Y-m-d')
                                              ?? $rp->created_at?->format('Y-m-d')
                                              ?? '',
                            'cdp_number'   => $rpCdpNumber,
                            'cdp_date'     => $rpCdpDate,
                            'cdp_amount'   => (float) ($rp->cdp?->total_amount ?? 0),
                            'prox_disp'    => $rpProxDisp,
                            'rubro_code'   => $rubroCode,
                            'rubro_name'   => $rubroName,
                            'sifse_code'   => $sifseCode,
                            'acct_code'    => $acct?->code ?? '',
                            'acct_name'    => $acct?->name ?? '',
                            'acct_parent'  => $acct?->parent,
                            'fs_code'      => $fsCode,
                            'fs_name'      => $fsName,
                            'bank_name'    => $rpBankName,
                            'bank_account' => $rpBankAcctNum,
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
                    $cdpInfo  = $distByCdp[$cdp->id] ?? null;
                    // Fecha fiscal del CDP = fecha de la convocatoria
                    $cdpDateFiscal = $convocatoriaDate
                        ? (is_string($convocatoriaDate) ? $convocatoriaDate : $convocatoriaDate->format('Y-m-d'))
                        : '';

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
                                'cdp_number'   => $cdp->cdp_number,
                                'cdp_date'     => $cdpDateFiscal,
                                'cdp_amount'   => (float) $cdp->total_amount,
                                'prox_disp'    => (float) $cdp->total_amount,
                                'rubro_code'   => $rubroCode,
                                'rubro_name'   => $rubroName,
                                'sifse_code'   => $sifseCodeRow,
                                'acct_code'    => $acct?->code ?? '',
                                'acct_name'    => $acct?->name ?? '',
                                'acct_parent'  => $acct?->parent,
                                'fs_code'      => $fsCode,
                                'fs_name'      => $fsName,
                                'bank_name'    => '',
                                'bank_account' => '',
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
                    'cdp_number'   => $firstCdp?->cdp_number ?? '',
                    'cdp_date'     => $fechaCdp,
                    'cdp_amount'   => (float) ($firstCdp?->total_amount ?? 0),
                    'prox_disp'    => (float) ($firstCdp?->total_amount ?? 0),
                    'rubro_code'   => $rubroCode ?? '',
                    'rubro_name'   => $rubroName ?? '',
                    'sifse_code'   => $sifseCode ?? '',
                    'acct_code'    => $acctCode,
                    'acct_name'    => $acctName,
                    'acct_parent'  => $acctParent,
                    'fs_code'      => '',
                    'fs_name'      => '',
                    'bank_name'    => '',
                    'bank_account' => '',
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
                // Sobreescribir CDP con el CDP específico del RP (cada RP tiene su propio CDP)
                if (!empty($r['cdp_number'])) {
                    $row['cdp_number']     = $r['cdp_number'];
                    // Disponibilidad = monto total del CDP (reserva inicial)
                    $row['disponibilidad'] = $r['cdp_amount'] ?? 0;
                }
                if (!empty($r['cdp_date'])) {
                    $row['fecha_cdp']          = $r['cdp_date'];
                    $row['fecha_certificacion']= $r['cdp_date'];
                }
                // Próximo disponible = CDP - pagos ya realizados contra el RP
                $row['prox_disp']                 = $r['prox_disp'] ?? 0;
                // Cuenta y banco de donde sale el dinero (lado del colegio): del rp_funding_source
                if (!empty($r['bank_name']))    $row['bank_name']    = $r['bank_name'];
                if (!empty($r['bank_account'])) $row['bank_account'] = $r['bank_account'];
                $row['subtotal']                  = round((float) $contract->subtotal * $ratio, 2);
                $row['iva']                       = round((float) $contract->iva * $ratio, 2);
                $row['total']                     = round($r['amount'], 2);
                // Valor en letras del total de la fila
                $row['valor_letras']              = $r['amount'] > 0
                    ? \App\Http\Controllers\PrecontractualPdfController::amountToWords($r['amount'])
                    : '';
                $this->rows[]                     = $row;
            }
        }

        // ========================================================================
        // PAGOS DIRECTOS CON CDP/RP (servicios públicos, gastos financieros, etc.)
        // ========================================================================
        // Estos pagos no tienen contract_id pero sí tienen cdp_id y contract_rp_id,
        // por lo que deben aparecer en el informe de contratación con su propio CDP y RP.
        $directQuery = \App\Models\PaymentOrder::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->where('payment_type', 'direct')
            ->whereNull('contract_id')
            ->whereNotNull('contract_rp_id')
            ->whereIn('status', ['approved', 'paid'])
            ->with([
                'supplier.municipality',
                'supplier.bankAccounts',
                'cdp.budgetItem.accountingAccount.parent.parent',
                'cdp.fundingSources.fundingSource',
                'cdp.convocatoria',
                'contractRp.cdp.convocatoria',
                'contractRp.fundingSources.fundingSource',
                'contractRp.fundingSources.bank',
                'contractRp.fundingSources.bankAccount.bank',
                'contractRp.fundingSources.budget.budgetItem.accountingAccount.parent.parent',
                'contractRp.fundingSources.budget.distributions.expenseCode',
                'expenseLines.expenseCode',
                'expenseLines.expenseDistribution.budget.budgetItem',
            ]);

        if ($this->filterSupplier) {
            $directQuery->whereHas('supplier', function ($q) {
                $q->where('first_surname', 'like', "%{$this->filterSupplier}%")
                  ->orWhere('first_name', 'like', "%{$this->filterSupplier}%")
                  ->orWhere('document_number', 'like', "%{$this->filterSupplier}%");
            });
        }

        $directPayments = $directQuery->orderBy('payment_number')->get();

        foreach ($directPayments as $po) {
            $supplier = $po->supplier;
            $rp       = $po->contractRp;
            if (!$rp) continue;
            // El CDP correcto es el del RP. $po->cdp puede ser null si el PO no guarda cdp_id
            // directo (aunque sí tiene contract_rp_id), así que preferimos el CDP del RP.
            $cdp      = $rp->cdp ?? $po->cdp;

            // Banco/cuenta a nivel RP (primera fuente). Abajo cada fila sobreescribe con la suya.
            $firstRpFs          = $rp->fundingSources->first();
            $rpBankDefault      = $firstRpFs?->bank ?? $firstRpFs?->bankAccount?->bank;
            $rpBankNameDefault  = $rpBankDefault?->name ?? '';
            $rpBankAcctDefault  = $firstRpFs?->bankAccount?->account_number ?? '';

            // Fecha CDP: se usa la fecha fiscal de la convocatoria (start_date) si existe,
            // con fallback al payment_date del PO (no se usa created_at).
            $convDate = $cdp?->convocatoria?->start_date ?? $rp->cdp?->convocatoria?->start_date;
            $fechaCdp = $convDate
                ? (is_string($convDate) ? $convDate : $convDate->format('Y-m-d'))
                : ($po->payment_date?->format('Y-m-d') ?? '');
            $fechaRp  = $rp->otrosi_date?->format('Y-m-d')
                        ?? $po->payment_date?->format('Y-m-d')
                        ?? $rp->created_at?->format('Y-m-d')
                        ?? '';

            // Próximo disponible del RP = CDP total - pagos ya realizados contra este RP
            $rpPaid       = (float) \App\Models\PaymentOrder::where('contract_rp_id', $rp->id)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('total');
            $cdpTotalAmt  = (float) ($cdp?->total_amount ?? $rp->total_amount ?? 0);
            $rpProxDisp   = max(0, $cdpTotalAmt - $rpPaid);

            // Datos base compartidos entre filas del mismo pago directo
            $baseDirectRow = [
                'prox_disp'               => $rpProxDisp,
                'cdp_number'              => $cdp?->cdp_number ?? '',
                'disponibilidad'          => $cdpTotalAmt,
                'fecha_cdp'               => $fechaCdp,
                'supplier_name'           => $supplier?->full_name ?? '',
                'supplier_first_surname'  => $supplier?->first_surname ?? '',
                'supplier_second_surname' => $supplier?->second_surname ?? '',
                'supplier_first_name'     => $supplier?->first_name ?? '',
                'supplier_second_name'    => $supplier?->second_name ?? '',
                'supplier_document'       => $supplier?->document_number ?? '',
                'supplier_dv'             => $supplier?->dv ?? '',
                'supplier_document_type'  => $supplier?->document_type ?? '',
                'supplier_person_type'    => $supplier?->person_type ?? '',
                'objeto'                  => $po->description ?? '',
                'justificacion'           => $po->description ?? '',
                'supervisor_name'         => $this->school->rector_name ?? '',
                'supervisor_document'     => $this->school->rector_document ?? '',
                'supervisor_cargo'        => 'RECTOR',
                'necesidades'             => $po->description ?? '',
                'duracion'                => 0,
                'duracion_label'          => 'N/A',
                'riesgos'                 => 'NO GENERA RIESGOS',
                'supplier_address'        => $supplier?->address ?? '',
                'supplier_phone'          => $supplier?->phone ?? $supplier?->mobile ?? '',
                'supplier_city'           => $supplier?->city ?? '',
                'supplier_regime'         => $supplier?->tax_regime_name ?? '',
                'forma_pago'              => 'Pago directo',
                'fecha_rp'                => $fechaRp,
                'fecha_inicio'            => $po->payment_date?->format('Y-m-d') ?? '',
                'fecha_fin'               => $po->payment_date?->format('Y-m-d') ?? '',
                'contract_number'         => '',
                'contract_formatted'      => 'PAGO DIRECTO',
                'contract_date'           => $po->payment_date?->format('Y-m-d') ?? '',
                'dependencia'             => 'RECTORIA',
                'bank_account'            => $rpBankAcctDefault,
                'bank_name'               => $rpBankNameDefault,
                'fecha_liquidacion'       => $po->payment_date?->format('Y-m-d') ?? '',
                'plazo'                   => 'N/A',
                'modalidad'               => 'Pago Directo',
                'rp_number'               => $rp->rp_number,
                'criterio_evaluacion'     => 'N/A',
                'lugar_ejecucion'         => '',
                'representante_legal'     => ($supplier?->person_type === 'juridica') ? '' : 'N/A',
                'convocatoria_number'     => '',
                'convocatoria_formatted'  => '',
                'fecha_invitacion'        => '',
                'intro_manual'            => '',
                'presupuesto_asignado'    => (float) $po->total,
                'fecha_max_propuesta'     => '',
                'hora_propuesta'          => '',
                'fecha_revision'          => '',
                'hora_revision'           => '',
                'fecha_evaluacion'        => '',
                'num_propuestas'          => 0,
                'acuerdo_paa'             => $this->school->budget_agreement_number ?? '',
                'fecha_acuerdo'           => $this->school->budget_approval_date ?? '',
                'fecha_certificacion'     => $fechaCdp,
                'status'                  => $po->status,
                'status_name'             => $po->status_name ?? ucfirst($po->status),
                'status_color'            => $po->status === 'paid' ? 'green' : 'blue',
                'id'                      => 'direct-' . $po->id,
            ];

            // Construir filas: una por cada fuente de financiación del RP (y por expense_line si hay).
            // Si el PO tiene expense_lines, usamos cada línea como rubro.
            // Si no, usamos el budget_item asociado al rp_funding_source.
            $directRows = [];

            if ($po->expenseLines->isNotEmpty()) {
                // Una fila por cada expense_line
                $rpSources = $rp->fundingSources;
                foreach ($po->expenseLines as $line) {
                    $ec   = $line->expenseCode;
                    $dist = $line->expenseDistribution;
                    $bi   = $dist?->budget?->budgetItem;
                    $acct = $bi?->accountingAccount;

                    // Fuente: la del budget de la distribución, o fallback al primer RP funding source
                    $fs = $dist?->budget?->fundingSource;
                    $matchedRpFs = null;
                    if ($rpSources->isNotEmpty() && $dist?->budget_id) {
                        $matchedRpFs = $rpSources->firstWhere('budget_id', $dist->budget_id);
                        if (!$fs) $fs = $matchedRpFs?->fundingSource;
                    }
                    if (!$fs) {
                        $matchedRpFs = $rpSources->first();
                        $fs = $matchedRpFs?->fundingSource;
                    }

                    // Banco/cuenta del rp_funding_source correspondiente (lado colegio)
                    $rpBank     = $matchedRpFs?->bank ?? $matchedRpFs?->bankAccount?->bank;
                    $rpBankName = $rpBank?->name ?? $rpBankNameDefault;
                    $rpBankAcct = $matchedRpFs?->bankAccount?->account_number ?? $rpBankAcctDefault;

                    $directRows[] = [
                        'rubro_code'   => $ec?->code ?? $bi?->code ?? '',
                        'rubro_name'   => $ec?->name ?? $bi?->name ?? '',
                        'sifse_code'   => $ec?->sifse_code ?? '',
                        'acct_code'    => $acct?->code ?? '',
                        'acct_name'    => $acct?->name ?? '',
                        'acct_parent'  => $acct?->parent,
                        'fs_code'      => $fs?->code ?? '',
                        'fs_name'      => $fs?->name ?? '',
                        'bank_name'    => $rpBankName,
                        'bank_account' => $rpBankAcct,
                        'amount'       => (float) $line->total,
                        'subtotal'     => (float) $line->subtotal,
                        'iva'          => (float) $line->iva,
                    ];
                }
            } else {
                // Sin expense_lines: una fila por cada fuente del RP
                foreach ($rp->fundingSources as $rpFs) {
                    $fs   = $rpFs->fundingSource;
                    $b    = $rpFs->budget;
                    $bi   = $b?->budgetItem;
                    $acct = $bi?->accountingAccount;
                    $dist = $b?->distributions->first();
                    $ec   = $dist?->expenseCode;

                    $rpBank     = $rpFs->bank ?? $rpFs->bankAccount?->bank;
                    $rpBankName = $rpBank?->name ?? '';
                    $rpBankAcct = $rpFs->bankAccount?->account_number ?? '';

                    $directRows[] = [
                        'rubro_code'   => $ec?->code ?? $bi?->code ?? '',
                        'rubro_name'   => $ec?->name ?? $bi?->name ?? '',
                        'sifse_code'   => $ec?->sifse_code ?? '',
                        'acct_code'    => $acct?->code ?? '',
                        'acct_name'    => $acct?->name ?? '',
                        'acct_parent'  => $acct?->parent,
                        'fs_code'      => $fs?->code ?? '',
                        'fs_name'      => $fs?->name ?? '',
                        'bank_name'    => $rpBankName,
                        'bank_account' => $rpBankAcct,
                        'amount'       => (float) $rpFs->amount,
                        'subtotal'     => 0,
                        'iva'          => 0,
                    ];
                }
            }

            if (empty($directRows)) continue;

            $totalAllDirect = collect($directRows)->sum('amount');
            foreach ($directRows as $r) {
                $ratio  = $totalAllDirect > 0 ? $r['amount'] / $totalAllDirect : 1;
                $acctP  = $r['acct_parent'];
                $acctGP = $acctP?->parent;

                $row = $baseDirectRow;
                $row['rubro_code']            = $r['rubro_code'];
                $row['rubro_name']            = $r['rubro_name'];
                $row['sifse_code']            = $r['sifse_code'];
                $row['expense_code']          = $r['rubro_code'];
                $row['acct_code']             = $r['acct_code'];
                $row['acct_name']             = $r['acct_name'];
                $row['acct_parent_code']      = $acctP?->code ?? '';
                $row['acct_parent_name']      = $acctP?->name ?? '';
                $row['acct_grandparent_code'] = $acctGP?->code ?? '';
                $row['acct_grandparent_name'] = $acctGP?->name ?? '';
                $row['funding_source']        = $r['fs_name'] ? "{$r['fs_name']} ({$r['fs_code']})" : '';
                $row['funding_source_codes']  = $r['fs_code'];
                if (!empty($r['bank_name']))    $row['bank_name']    = $r['bank_name'];
                if (!empty($r['bank_account'])) $row['bank_account'] = $r['bank_account'];
                $row['subtotal']              = $r['subtotal'] > 0 ? $r['subtotal'] : round((float) $po->subtotal * $ratio, 2);
                $row['iva']                   = $r['iva'] > 0      ? $r['iva']      : round((float) $po->iva * $ratio, 2);
                $row['total']                 = round($r['amount'], 2);
                $row['valor_letras']          = $r['amount'] > 0
                    ? \App\Http\Controllers\PrecontractualPdfController::amountToWords($r['amount'])
                    : '';
                $this->rows[] = $row;
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
