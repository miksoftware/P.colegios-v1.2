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
                'rps.fundingSources.budget',
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

            // Fuentes de financiación (del CDP)
            $fundingSourceNames = [];
            foreach ($cdps as $cdp) {
                foreach ($cdp->fundingSources as $cdpFs) {
                    $fs = $cdpFs->fundingSource;
                    if ($fs) {
                        $fundingSourceNames[] = "{$fs->name} ({$fs->code})";
                    }
                }
            }
            $fundingSourceLabel = implode(', ', array_unique($fundingSourceNames));

            // Fuente código
            $fundingSourceCodes = [];
            foreach ($cdps as $cdp) {
                foreach ($cdp->fundingSources as $cdpFs) {
                    $fs = $cdpFs->fundingSource;
                    if ($fs) {
                        $fundingSourceCodes[] = $fs->code;
                    }
                }
            }

            // Distribuciones de la convocatoria (para obtener expense codes)
            $distributions = $convocatoria?->distributionDetails ?? collect();
            $firstDist = $distributions->first();
            $expenseCode = $firstDist?->expenseDistribution?->expenseCode;
            $budget = $firstDist?->expenseDistribution?->budget;
            $budgetItem = $budget?->budgetItem;
            $accountingAccount = $budgetItem?->accountingAccount;
            $fundingSource = $budget?->fundingSource;

            // Rubro presupuestal info
            $rubroCode = $expenseCode?->code ?? $budgetItem?->code ?? '';
            $rubroName = $expenseCode?->name ?? $budgetItem?->name ?? '';
            $sifseCode = $expenseCode?->sifse_code ?? '';

            // Cuenta contable info
            $acctCode = $accountingAccount?->code ?? '';
            $acctName = $accountingAccount?->name ?? '';
            $acctLevel = $accountingAccount?->level ?? 0;

            // Obtener jerarquía de cuenta contable
            $acctParent = $accountingAccount?->parent;
            $acctGrandParent = $acctParent?->parent;

            // Propuesta seleccionada
            $selectedProposal = $convocatoria?->selectedProposal;

            // Banco del proveedor
            $supplierBank = $supplier?->bankAccounts?->first();
            $bankName = $supplierBank?->bank_name ?? '';
            $bankAccountNumber = $supplierBank?->account_number ?? '';

            // Disponibilidad presupuestal (saldo disponible del rubro)
            $disponibilidad = 0;
            if ($firstCdp) {
                $disponibilidad = (float) $firstCdp->total_amount;
            }

            // Saldo disponible del rubro después del CDP
            $proxDisp = 0;
            if ($budget) {
                $totalDistributed = $budget->distributions?->sum('amount') ?? 0;
                $proxDisp = (float) $budget->current_amount - $totalDistributed;
            }

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

            $this->rows[] = [
                // Identificación del rubro
                'rubro_name' => $rubroName,
                'prox_disp' => $proxDisp,
                'rubro_code' => $rubroCode,

                // CDP
                'cdp_number' => $firstCdp?->cdp_number ?? '',
                'disponibilidad' => $disponibilidad,
                'fecha_cdp' => $fechaCdp,

                // Proveedor
                'supplier_name' => $supplier?->full_name ?? '',
                'supplier_first_surname' => $supplier?->first_surname ?? '',
                'supplier_second_surname' => $supplier?->second_surname ?? '',
                'supplier_first_name' => $supplier?->first_name ?? '',
                'supplier_second_name' => $supplier?->second_name ?? '',
                'supplier_document' => $supplier?->document_number ?? '',
                'supplier_dv' => $supplier?->dv ?? '',
                'supplier_document_type' => $supplier?->document_type ?? '',
                'supplier_person_type' => $supplier?->person_type ?? '',

                // Valores del contrato
                'subtotal' => (float) $contract->subtotal,
                'iva' => (float) $contract->iva,
                'total' => (float) $contract->total,
                'valor_letras' => '', // Se calcula en JS o se deja vacío

                // Objeto
                'objeto' => $contract->object ?? '',
                'justificacion' => $contract->justification ?? $convocatoria?->justification ?? '',

                // Código de gasto
                'expense_code' => $rubroCode,
                'sifse_code' => $sifseCode,

                // Supervisor
                'supervisor_name' => $supervisor ? ($supervisor->name . ' ' . $supervisor->surname) : '',
                'supervisor_document' => $supervisor?->identification_number ?? '',
                'supervisor_cargo' => 'RECTOR',

                // Cuenta contable
                'acct_code' => $acctCode,
                'acct_name' => $acctName,
                'acct_parent_code' => $acctParent?->code ?? '',
                'acct_parent_name' => $acctParent?->name ?? '',
                'acct_grandparent_code' => $acctGrandParent?->code ?? '',
                'acct_grandparent_name' => $acctGrandParent?->name ?? '',

                // Necesidades
                'necesidades' => $convocatoria?->justification ?? '',

                // Duración
                'duracion' => $contract->duration_days ?? 0,
                'duracion_label' => ($contract->duration_days ?? 0) . ' DÍAS',

                // Riesgos
                'riesgos' => 'NO GENERA RIESGOS',

                // Dirección proveedor
                'supplier_address' => $supplier?->address ?? '',
                'supplier_phone' => $supplier?->phone ?? $supplier?->mobile ?? '',
                'supplier_city' => $supplier?->city ?? '',
                'supplier_regime' => $supplier?->tax_regime_name ?? '',

                // Forma de pago
                'forma_pago' => $contract->payment_method_name ?? '',

                // Fuente de financiación
                'funding_source' => $fundingSourceLabel,
                'funding_source_codes' => implode(', ', array_unique($fundingSourceCodes)),

                // Fechas
                'fecha_rp' => $fechaRp,
                'fecha_inicio' => $contract->start_date?->format('Y-m-d') ?? '',
                'fecha_fin' => $contract->end_date?->format('Y-m-d') ?? '',

                // Contrato
                'contract_number' => $contract->contract_number ?? '',
                'contract_formatted' => 'CONTRATO No. ' . $contract->formatted_number,
                'contract_date' => $contract->start_date?->format('Y-m-d') ?? '',

                // Dependencia
                'dependencia' => 'RECTORIA',

                // Banco
                'bank_account' => $bankAccountNumber,
                'bank_name' => $bankName,

                // Liquidación
                'fecha_liquidacion' => $liquidationDate,

                // Plazo
                'plazo' => ($contract->duration_days ?? 0) . ' DÍAS',

                // Modalidad
                'modalidad' => $contract->modality_name ?? '',

                // RP
                'rp_number' => $rpNumber,

                // Evaluación
                'criterio_evaluacion' => 'MENOR PRECIO',
                'lugar_ejecucion' => $contract->execution_place ?? '',

                // Representante legal
                'representante_legal' => ($supplier?->person_type === 'juridica') ? '' : 'N/A',

                // Convocatoria
                'convocatoria_number' => $convocatoria?->convocatoria_number ?? '',
                'convocatoria_formatted' => $convocatoria ? str_pad($convocatoria->convocatoria_number, 3, '0', STR_PAD_LEFT) : '',
                'fecha_invitacion' => $convocatoria?->start_date?->format('Y-m-d') ?? '',

                // Introducción manual
                'intro_manual' => $this->school->contracting_manual_approval_number
                    ? "En cumplimiento a lo establecido en el CAPÍTULO 2, Numeral 1 del Manual de contratación institucional aprobado mediante acuerdo No. " . $this->school->contracting_manual_approval_number . " de fecha " . ($this->school->contracting_manual_approval_date ?? '') . " por el Consejo Directivo, en la cual se establecen los parámetros para garantizar la selección objetiva del proveedor, se publica la presente convocatoria para la comparación de cotizaciones."
                    : '',

                // Presupuesto asignado
                'presupuesto_asignado' => (float) ($convocatoria?->assigned_budget ?? 0),

                // Fechas de propuesta
                'fecha_max_propuesta' => $convocatoria?->start_date?->format('Y-m-d') ?? '',
                'hora_propuesta' => '',
                'fecha_revision' => $convocatoria?->evaluation_date?->format('Y-m-d') ?? '',
                'hora_revision' => '',
                'fecha_evaluacion' => $convocatoria?->evaluation_date?->format('Y-m-d') ?? '',

                // Propuestas
                'num_propuestas' => $convocatoria?->proposals_count ?? $convocatoria?->proposals?->count() ?? 0,

                // Acuerdo PAA
                'acuerdo_paa' => $this->school->budget_agreement_number ?? '',
                'fecha_acuerdo' => $this->school->budget_approval_date ?? '',

                // Certificación
                'fecha_certificacion' => $fechaCdp,

                // Status
                'status' => $contract->status,
                'status_name' => $contract->status_name,
                'status_color' => $contract->status_color,

                // ID
                'id' => $contract->id,
            ];
        }

        // Resumen
        $collection = collect($this->rows);
        $this->summary = [
            'total_contracts' => $collection->count(),
            'total_amount' => $collection->sum('total'),
            'total_subtotal' => $collection->sum('subtotal'),
            'total_iva' => $collection->sum('iva'),
            'by_status' => $collection->groupBy('status_name')->map(fn($g, $k) => [
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
