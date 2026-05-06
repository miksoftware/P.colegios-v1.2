<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\PaymentOrderExpenseLine;
use App\Models\RpFundingSource;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ExpenseExecutionReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterQuarter = '';
    public $filterSemester = '';

    public $rows = [];
    public $totals = [];

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

    public function updatedFilterQuarter()
    {
        $this->filterSemester = '';
        $this->loadReport();
    }

    public function updatedFilterSemester()
    {
        $this->filterQuarter = '';
        $this->loadReport();
    }

    public function loadReport()
    {
        $year = (int) $this->filterYear;
        $quarter = $this->filterQuarter ? (int) $this->filterQuarter : null;
        $semester = $this->filterSemester ? (int) $this->filterSemester : null;

        // Rango de fechas ACUMULADO: siempre desde enero 1 hasta el fin del período seleccionado.
        // Trimestre 2 → enero–junio, Semestre 1 → enero–junio, Semestre 2 → enero–diciembre, etc.
        $dateFrom = null;
        $dateTo = null;
        if ($quarter) {
            $dateFrom = "{$year}-01-01";
            $lastMonth = $quarter * 3;
            $dateTo = \Carbon\Carbon::parse("{$year}-{$lastMonth}-01")->endOfMonth()->format('Y-m-d');
        } elseif ($semester) {
            $dateFrom = "{$year}-01-01";
            $lastMonth = $semester * 6;
            $dateTo = \Carbon\Carbon::parse("{$year}-{$lastMonth}-01")->endOfMonth()->format('Y-m-d');
        }

        // Get all expense budgets for this school/year with their funding source
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'fundingSource',
                'modifications',
                'outgoingTransfers',
                'incomingTransfers',
                'distributions.expenseCode',
            ])
            ->orderBy('budget_item_id')
            ->get();

        // Pre-load commitments per expense_distribution:
        // Los compromisos son los RPs (Registros Presupuestales) que se hacen por contrato.
        // La cadena es: rp_funding_sources → contract_rps → contracts → convocatorias →
        // convocatoria_distributions → expense_distributions.
        // El JOIN a expense_distributions garantiza que el budget_id del RP coincide
        // con el de la distribución (evita duplicados cuando hay varias distribuciones
        // por convocatoria).
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();

        $commitmentsByDist = [];
        if (!empty($distIds)) {
            // --- Compromisos reales: RPs vía budget_id ↔ expense_distribution.budget_id ---
            // NO dependemos de convocatoria_distributions porque no todos los contratos
            // tienen distribuciones (p.ej. contratos con CDP pero sin convocatoria formal).
            $rpQuery = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->join('expense_distributions as ed', 'ed.budget_id', '=', 'rfs.budget_id')
                ->whereIn('ed.id', $distIds)
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled');
            if ($dateFrom && $dateTo) {
                // Usar fecha del RP: otrosi_date si existe, de lo contrario created_at
                $rpQuery->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('cr.otrosi_date', [$dateFrom, $dateTo])
                      ->orWhere(function ($qq) use ($dateFrom, $dateTo) {
                          $qq->whereNull('cr.otrosi_date')
                             ->whereBetween('cr.created_at', [$dateFrom, $dateTo . ' 23:59:59']);
                      });
                });
            }
            // Distribuir el monto del RP proporcionalmente entre las distribuciones del mismo budget
            // (si hay varias distribuciones para el mismo budget, prorratear por amount)
            $rpCommitmentsRaw = $rpQuery
                ->selectRaw('ed.id as dist_id, ed.budget_id, ed.amount as dist_amount, rfs.amount as rfs_amount')
                ->get();

            // Sum per budget_id to get total distributions
            $totalDistAmountByBudget = [];
            foreach ($budgets as $b) {
                $totalDistAmountByBudget[$b->id] = (float) $b->distributions->sum('amount');
            }

            foreach ($rpCommitmentsRaw as $row) {
                $totalDist = (float) ($totalDistAmountByBudget[$row->budget_id] ?? 0);
                $ratio = $totalDist > 0 ? (float) $row->dist_amount / $totalDist : 0;
                $commitmentsByDist[$row->dist_id] = ($commitmentsByDist[$row->dist_id] ?? 0) + (float) $row->rfs_amount * $ratio;
            }

            // --- Pagos directos CON expense_lines por distribución ---
            $directWithLinesByDist = \Illuminate\Support\Facades\DB::table('payment_order_expense_lines as pol')
                ->join('payment_orders as po', 'po.id', '=', 'pol.payment_order_id')
                ->whereIn('pol.expense_distribution_id', $distIds)
                ->where('po.payment_type', 'direct')
                ->whereIn('po.status', ['approved', 'paid']);
            if ($dateFrom && $dateTo) {
                $directWithLinesByDist->whereBetween('po.payment_date', [$dateFrom, $dateTo]);
            }
            $directWithLinesByDist = $directWithLinesByDist
                ->selectRaw('pol.expense_distribution_id, SUM(pol.total) as total')
                ->groupBy('pol.expense_distribution_id')
                ->pluck('total', 'expense_distribution_id')
                ->toArray();

            foreach ($directWithLinesByDist as $dId => $total) {
                $commitmentsByDist[$dId] = ($commitmentsByDist[$dId] ?? 0) + (float) $total;
            }
        }

        // Compromisos a nivel de Budget para presupuestos SIN distribuciones (pagos directos puros)
        $commitmentsByBudget = [];
        $budgetsWithoutDist = $budgets->filter(fn($b) => $b->distributions->isEmpty());
        if ($budgetsWithoutDist->isNotEmpty()) {
            $emptyBudgetIds = $budgetsWithoutDist->pluck('id')->toArray();
            $q2 = RpFundingSource::whereIn('budget_id', $emptyBudgetIds)
                ->whereHas('contractRp', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', '!=', 'cancelled');
                    if ($dateFrom && $dateTo) {
                        // Usar fecha del RP (created_at del contract_rp) u otrosi_date
                        $q->where(function ($qq) use ($dateFrom, $dateTo) {
                            $qq->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                               ->orWhereBetween('otrosi_date', [$dateFrom, $dateTo]);
                        });
                    }
                });
            $commitmentsByBudget = $q2->selectRaw('budget_id, SUM(amount) as total')
                ->groupBy('budget_id')
                ->pluck('total', 'budget_id')
                ->toArray();
        }

        $paymentsByDist = [];
        if (!empty($distIds)) {
            $query = PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distIds)
                ->whereHas('paymentOrder', function ($q) use ($dateFrom, $dateTo) {
                    $q->whereIn('status', ['approved', 'paid']);
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween('payment_date', [$dateFrom, $dateTo]);
                    }
                });
            $paymentsByDist = $query->selectRaw('expense_distribution_id, SUM(total) as total_paid')
                ->groupBy('expense_distribution_id')
                ->pluck('total_paid', 'expense_distribution_id')
                ->toArray();
        }

        // Pre-load direct payments para presupuestos SIN distribuciones.
        // Excluimos:
        //   - Pagos con expense_lines: ya están en $paymentsByDist
        //   - Pagos de impuestos (con taxLines / skipCdpRp): no pertenecen a ningún rubro
        //   - Pagos sin expense_lines de presupuestos CON distribuciones: son pagos viejos
        //     o de impuestos que no deben prorratearse entre rubros.
        $directPaymentsByBudgetItem = [];
        $budgetsWithoutDistIds = $budgets->filter(fn($b) => $b->distributions->isEmpty())
            ->pluck('budget_item_id')->unique()->filter()->toArray();
        if (!empty($budgetsWithoutDistIds)) {
            $query = \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where('payment_type', 'direct')
                ->whereIn('budget_item_id', $budgetsWithoutDistIds)
                ->whereIn('status', ['approved', 'paid'])
                ->whereDoesntHave('expenseLines')
                ->whereDoesntHave('taxLines');
            if ($dateFrom && $dateTo) {
                $query->whereBetween('payment_date', [$dateFrom, $dateTo]);
            }
            $directPaymentsByBudgetItem = $query->selectRaw('budget_item_id, SUM(total) as total_paid')
                ->groupBy('budget_item_id')
                ->pluck('total_paid', 'budget_item_id')
                ->toArray();
        }

        $this->rows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $credits = (float) $budget->incomingTransfers->sum('amount');
            $contracredits = (float) $budget->outgoingTransfers->sum('amount');
            $definitive = (float) $budget->current_amount;
            $totalBudgetCommitments = (float) ($commitmentsByBudget[$budget->id] ?? 0); // solo para presupuestos sin distribuciones
            $directPayments = (float) ($directPaymentsByBudgetItem[$budget->budget_item_id] ?? 0);
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingName = $budget->fundingSource?->name ?? '';

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                $totalObligations = $directPayments;
                $this->rows[] = [
                    'budget_id' => $budget->id,
                    'rubro_code' => $budget->budgetItem?->code ?? '',
                    'rubro_name' => $budget->budgetItem?->name ?? '',
                    'funding_source_code' => $fundingCode,
                    'funding_source_name' => $fundingName,
                    'initial' => $initial,
                    'additions' => $additions,
                    'reductions' => $reductions,
                    'credits' => $credits,
                    'contracredits' => $contracredits,
                    'definitive' => $definitive,
                    'commitments' => $totalBudgetCommitments + $directPayments,
                    'obligations' => $totalObligations,
                    'payments' => $totalObligations,
                    'pending' => $definitive - $totalBudgetCommitments - $directPayments,
                ];
            } else {
                $totalDistAmount = $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $distPayments = (float) ($paymentsByDist[$dist->id] ?? 0);
                    // Compromisos reales de esta distribución (convocatoria + adiciones RP + directos con línea)
                    $distCommitments = (float) ($commitmentsByDist[$dist->id] ?? 0);
                    // Pagos directos sin expense_lines NO se prorratean: son pagos viejos
                    // sin código de gasto asignado o pagos de impuestos — no pertenecen aquí.
                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;
                    $totalObligations = $distPayments;
                    $totalCommitmentsRow = $distCommitments;

                    $this->rows[] = [
                        'budget_id' => $budget->id,
                        'rubro_code' => $expCode?->code ?? '',
                        'rubro_name' => $expCode?->name ?? '',
                        'funding_source_code' => $fundingCode,
                        'funding_source_name' => $fundingName,
                        'initial' => round($initial * $ratio, 2),
                        'additions' => round($additions * $ratio, 2),
                        'reductions' => round($reductions * $ratio, 2),
                        'credits' => round($credits * $ratio, 2),
                        'contracredits' => round($contracredits * $ratio, 2),
                        'definitive' => round($definitive * $ratio, 2),
                        'commitments' => $totalCommitmentsRow,
                        'obligations' => $totalObligations,
                        'payments' => $totalObligations,
                        'pending' => round($definitive * $ratio, 2) - $totalCommitmentsRow,
                    ];
                }
            }
        }

        // Totales
        $c = collect($this->rows);
        $this->totals = [
            'initial' => $c->sum('initial'),
            'additions' => $c->sum('additions'),
            'reductions' => $c->sum('reductions'),
            'credits' => $c->sum('credits'),
            'contracredits' => $c->sum('contracredits'),
            'definitive' => $c->sum('definitive'),
            'commitments' => $c->sum('commitments'),
            'obligations' => $c->sum('obligations'),
            'payments' => $c->sum('payments'),
            'pending' => $c->sum('pending'),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        if ($this->filterQuarter) {
            $q = (int) $this->filterQuarter;
            $endMonths = [1 => 'MARZO', 2 => 'JUNIO', 3 => 'SEPTIEMBRE', 4 => 'DICIEMBRE'];
            $endDays   = [1 => '31', 2 => '30', 3 => '30', 4 => '31'];
            return "DE ENERO 01 AL {$endDays[$q]} DE {$endMonths[$q]} DE {$this->filterYear} (ACUMULADO AL {$q}° TRIMESTRE)";
        }        if ($this->filterSemester) {
            $s = (int) $this->filterSemester;
            $label = $s === 1 ? 'DE ENERO 01 AL 30 DE JUNIO' : 'DE ENERO 01 AL 31 DE DICIEMBRE';
            $sem   = $s === 1 ? 'PRIMER' : 'SEGUNDO';
            return "{$label} DE {$this->filterYear} (ACUMULADO AL {$sem} SEMESTRE)";
        }        return "A DICIEMBRE 31 DE {$this->filterYear} CONSOLIDADO";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.expense-execution-report');
    }
}
