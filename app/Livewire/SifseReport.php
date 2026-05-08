<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\Income;
use App\Models\PaymentOrderExpenseLine;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class SifseReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterTrimester = '';
    public $activeTab = 'expenses';

    public $expenseRows = [];
    public $incomeRows = [];
    public $expenseTotals = [];
    public $incomeTotals = [];

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
        $this->filterTrimester = (string) ceil(now()->month / 3);
        $this->loadReport();
    }

    public function updatedFilterYear()
    {
        $this->loadReport();
    }

    public function updatedFilterTrimester()
    {
        $this->loadReport();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Primer día del año fiscal.
     */
    private function getYearStart(): string
    {
        return ((int) $this->filterYear) . '-01-01';
    }

    /**
     * Fecha de corte (último día del trimestre seleccionado).
     * El SIFSE es acumulado: muestra todo desde enero 1 hasta esta fecha.
     */
    private function getTrimesterCutoffDate(): string
    {
        $year = (int) $this->filterYear;
        $trimester = (int) ($this->filterTrimester ?: ceil(now()->month / 3));
        $endMonth = $trimester * 3;

        return \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();
    }

    public function loadReport()
    {
        $this->loadExpenses();
        $this->loadIncomes();
        $this->dispatch('reportLoaded');
    }

    private function loadExpenses()
    {
        $year = (int) $this->filterYear;
        $daneCode = $this->school->dane_code ?? '';
        $yearStart = $this->getYearStart();
        $cutoff = $this->getTrimesterCutoffDate();

        // Get all expense budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'fundingSource',
                'distributions.expenseCode',
            ])
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();
        $distIds   = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();
        $validDistIds = array_flip($distIds);

        // ========================================================================
        // MODIFICACIONES POR DISTRIBUCIÓN (budget_modification_lines)
        // Regla: solo las líneas impactan distribuciones. Adiciones "generales" sin
        // líneas afectan el saldo del budget pero NO aparecen acá (no están distribuidas).
        // Se ignoran líneas anteriores al año fiscal (son el histórico del inicial).
        // Se incluyen líneas dentro del rango [yearStart, cutoff].
        // ========================================================================
        $additionsByDist = [];
        $reductionsByDist = [];

        if (!empty($budgetIds)) {
            $mods = \App\Models\BudgetModification::whereIn('budget_id', $budgetIds)
                ->whereHas('lines')
                ->with('lines')
                ->get();

            foreach ($mods as $mod) {
                $isAddition = $mod->type === 'addition';
                $modDate    = $mod->document_date;

                foreach ($mod->lines as $line) {
                    if (!isset($validDistIds[$line->expense_distribution_id])) continue;

                    // Fecha fiscal efectiva: document_date propio o del mod padre.
                    // Nunca se usa created_at (es la fecha de registro, no la fiscal).
                    $lineDate = $line->document_date ?? $modDate;
                    if (!$lineDate) continue;

                    $lineDateStr = $lineDate instanceof \Carbon\Carbon
                        ? $lineDate->toDateString()
                        : (string) $lineDate;

                    // Fuera del año fiscal → es histórico del inicial, no es movimiento del año
                    if ($lineDateStr < $yearStart) continue;
                    // Acumulado hasta cutoff
                    if ($lineDateStr > $cutoff) continue;

                    $delta = abs((float) $line->amount_after - (float) $line->amount_before);
                    $did   = $line->expense_distribution_id;

                    if ($isAddition) {
                        $additionsByDist[$did] = ($additionsByDist[$did] ?? 0) + $delta;
                    } else {
                        $reductionsByDist[$did] = ($reductionsByDist[$did] ?? 0) + $delta;
                    }
                }
            }
        }

        // ========================================================================
        // TRASLADOS (créditos/contracréditos) con asignación EXACTA
        // ========================================================================
        $creditsByDist = [];
        $contracreditsByDist = [];

        if (!empty($budgetIds)) {
            $transfers = \App\Models\BudgetTransfer::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                })
                ->whereBetween('document_date', [$yearStart, $cutoff])
                ->get();

            foreach ($transfers as $t) {
                $amt = (float) $t->amount;

                // Crédito (entra al rubro destino)
                if (in_array($t->destination_budget_id, $budgetIds)
                    && !empty($t->destination_expense_distribution_id)
                    && isset($validDistIds[$t->destination_expense_distribution_id])) {
                    $creditsByDist[$t->destination_expense_distribution_id] =
                        ($creditsByDist[$t->destination_expense_distribution_id] ?? 0) + $amt;
                }

                // Contracrédito (sale del rubro origen)
                if (in_array($t->source_budget_id, $budgetIds)
                    && !empty($t->source_expense_distribution_id)
                    && isset($validDistIds[$t->source_expense_distribution_id])) {
                    $contracreditsByDist[$t->source_expense_distribution_id] =
                        ($contracreditsByDist[$t->source_expense_distribution_id] ?? 0) + $amt;
                }
            }
        }

        // ========================================================================
        // COMPROMISOS (RPs) con asignación EXACTA vía cdp.convocatoria_distribution_id
        // Con fallback legacy: si el CDP tiene convocatoria pero no distribución, y la
        // convocatoria tiene UNA sola distribución que cae en el mismo budget del rfs,
        // se asume esa.
        // Fecha efectiva del RP: COALESCE(otrosi_date, contract.start_date)
        // ========================================================================
        $commitmentsByDist = [];

        if (!empty($distIds)) {
            $rpRows = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->leftJoin('cdps as cdp', 'cdp.id', '=', 'cr.cdp_id')
                ->leftJoin('convocatoria_distributions as cvd', 'cvd.id', '=', 'cdp.convocatoria_distribution_id')
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled')
                ->whereRaw('COALESCE(cr.otrosi_date, c.start_date) BETWEEN ? AND ?', [$yearStart, $cutoff])
                ->selectRaw('rfs.budget_id, rfs.amount as rfs_amount, cvd.expense_distribution_id as exact_dist_id, cdp.convocatoria_id as conv_id')
                ->get();

            // Mapa convocatoria → distribuciones (para fallback legacy)
            $convIds = collect($rpRows)->pluck('conv_id')->filter()->unique()->all();
            $convToDists = [];
            if (!empty($convIds)) {
                $cvdRows = \Illuminate\Support\Facades\DB::table('convocatoria_distributions as cvd')
                    ->join('expense_distributions as ed', 'ed.id', '=', 'cvd.expense_distribution_id')
                    ->whereIn('cvd.convocatoria_id', $convIds)
                    ->selectRaw('cvd.convocatoria_id, cvd.expense_distribution_id, ed.budget_id')
                    ->get();
                foreach ($cvdRows as $cvdRow) {
                    $convToDists[$cvdRow->convocatoria_id][] = [
                        'dist_id'   => (int) $cvdRow->expense_distribution_id,
                        'budget_id' => (int) $cvdRow->budget_id,
                    ];
                }
            }

            foreach ($rpRows as $row) {
                $rfsAmount = (float) $row->rfs_amount;

                // Ruta A: asignación exacta
                if (!empty($row->exact_dist_id) && isset($validDistIds[$row->exact_dist_id])) {
                    $commitmentsByDist[$row->exact_dist_id] =
                        ($commitmentsByDist[$row->exact_dist_id] ?? 0) + $rfsAmount;
                    continue;
                }

                // Ruta B: legacy — convocatoria con UNA sola distribución en el budget del rfs
                if (!empty($row->conv_id) && isset($convToDists[$row->conv_id])) {
                    $candidates = array_values(array_filter(
                        $convToDists[$row->conv_id],
                        fn($c) => $c['budget_id'] === (int) $row->budget_id
                                 && isset($validDistIds[$c['dist_id']])
                    ));
                    if (count($candidates) === 1) {
                        $commitmentsByDist[$candidates[0]['dist_id']] =
                            ($commitmentsByDist[$candidates[0]['dist_id']] ?? 0) + $rfsAmount;
                        continue;
                    }
                }

                // Sin resolución exacta: data incompleta, no se agrega.
            }

            // Pagos directos CON expense_lines: son compromisos ya ejecutados.
            // Fecha fiscal: payment_date del PaymentOrder.
            $directWithLinesByDist = \Illuminate\Support\Facades\DB::table('payment_order_expense_lines as pol')
                ->join('payment_orders as po', 'po.id', '=', 'pol.payment_order_id')
                ->whereIn('pol.expense_distribution_id', $distIds)
                ->where('po.payment_type', 'direct')
                ->whereIn('po.status', ['approved', 'paid'])
                ->whereBetween('po.payment_date', [$yearStart, $cutoff])
                ->selectRaw('pol.expense_distribution_id, SUM(pol.total) as total')
                ->groupBy('pol.expense_distribution_id')
                ->pluck('total', 'expense_distribution_id')
                ->toArray();

            foreach ($directWithLinesByDist as $dId => $total) {
                $commitmentsByDist[$dId] = ($commitmentsByDist[$dId] ?? 0) + (float) $total;
            }
        }

        // ========================================================================
        // PAGOS (obligaciones/pagos) vía expense_lines. Asignación EXACTA por distribución.
        // ========================================================================
        $paymentsByDist = [];
        if (!empty($distIds)) {
            $paymentsByDist = PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distIds)
                ->whereHas('paymentOrder', fn($q) => $q
                    ->whereIn('status', ['approved', 'paid'])
                    ->whereBetween('payment_date', [$yearStart, $cutoff])
                )
                ->selectRaw('expense_distribution_id, SUM(total) as total_paid')
                ->groupBy('expense_distribution_id')
                ->pluck('total_paid', 'expense_distribution_id')
                ->toArray();
        }

        // ========================================================================
        // CONSTRUCCIÓN DE FILAS: agrupadas por funding_source_code + sifse_code
        // Cada distribución contribuye a su fila única.
        // Initial se deriva del budget.initial_amount prorrateado por peso del rubro.
        // ========================================================================
        $rawRows = [];

        foreach ($budgets as $budget) {
            $distributions = $budget->distributions;
            if ($distributions->isEmpty()) continue;

            $budgetInitial     = (float) $budget->initial_amount;
            $totalDistInitial  = (float) $distributions->sum('initial_amount');
            $fundingCode       = $budget->fundingSource?->code ?? '';

            foreach ($distributions as $dist) {
                $sifseCode = $dist->expenseCode?->sifse_code ?? '';

                // Apropiación inicial del rubro: si budget nació con monto > 0, se reparte
                // según initial_amount del rubro (peso histórico). Si budget nació en 0
                // (superávit/adición), el rubro también nace en 0.
                $distInitial = 0;
                if ($budgetInitial > 0 && $totalDistInitial > 0) {
                    $distInitial = round($budgetInitial * ((float) $dist->initial_amount / $totalDistInitial), 2);
                }

                $distAdditions     = (float) ($additionsByDist[$dist->id] ?? 0);
                $distReductions    = (float) ($reductionsByDist[$dist->id] ?? 0);
                $distCredits       = (float) ($creditsByDist[$dist->id] ?? 0);
                $distContracredits = (float) ($contracreditsByDist[$dist->id] ?? 0);
                $distDefinitive    = $distInitial + $distAdditions - $distReductions
                                   + $distCredits - $distContracredits;

                $distCommitments   = (float) ($commitmentsByDist[$dist->id] ?? 0);
                $distPayments      = (float) ($paymentsByDist[$dist->id] ?? 0);

                $key = "{$fundingCode}|{$sifseCode}";
                if (!isset($rawRows[$key])) {
                    $rawRows[$key] = [
                        'funding_source_code' => $fundingCode,
                        'sifse_code'          => $sifseCode,
                        'initial'             => 0,
                        'definitive'          => 0,
                        'commitments'         => 0,
                        'obligations'         => 0,
                        'payments'            => 0,
                    ];
                }

                $rawRows[$key]['initial']     += $distInitial;
                $rawRows[$key]['definitive']  += $distDefinitive;
                $rawRows[$key]['commitments'] += $distCommitments;
                $rawRows[$key]['obligations'] += $distPayments;
                $rawRows[$key]['payments']    += $distPayments;
            }
        }

        // Ordenar por fuente, luego por código SIFSE
        $sorted = collect($rawRows)->sortBy([
            ['funding_source_code', 'asc'],
            ['sifse_code', 'asc'],
        ])->values();

        $trimester = $this->filterTrimester ?: (string) ceil(now()->month / 3);

        $this->expenseRows = $sorted->map(fn($row) => [
            'dane_code'           => $daneCode,
            'year'                => $year,
            'trimester'           => $trimester,
            'funding_source_code' => $row['funding_source_code'],
            'sifse_code'          => $row['sifse_code'],
            'initial'             => $row['initial'],
            'definitive'          => $row['definitive'],
            'commitments'         => $row['commitments'],
            'obligations'         => $row['obligations'],
            'payments'            => $row['payments'],
        ])->toArray();

        $c = collect($this->expenseRows);
        $this->expenseTotals = [
            'initial'     => $c->sum('initial'),
            'definitive'  => $c->sum('definitive'),
            'commitments' => $c->sum('commitments'),
            'obligations' => $c->sum('obligations'),
            'payments'    => $c->sum('payments'),
        ];
    }

    private function loadIncomes()
    {
        $year = (int) $this->filterYear;
        $daneCode = $this->school->dane_code ?? '';
        $trimester = $this->filterTrimester ?: (string) ceil(now()->month / 3);
        $yearStart = $this->getYearStart();
        $cutoff = $this->getTrimesterCutoffDate();

        // Get all income budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with(['fundingSource'])
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();

        // --- Modificaciones de ingresos acumuladas hasta cutoff ---
        // Los ingresos se modifican a nivel budget (no por distribución).
        // Fecha fiscal efectiva: document_date del mod (NO created_at).
        $additionsByBudget  = [];
        $reductionsByBudget = [];
        if (!empty($budgetIds)) {
            $mods = \App\Models\BudgetModification::whereIn('budget_id', $budgetIds)
                ->whereNotNull('document_date')
                ->whereBetween('document_date', [$yearStart, $cutoff])
                ->get();

            foreach ($mods as $mod) {
                if ($mod->type === 'addition') {
                    $additionsByBudget[$mod->budget_id] =
                        ($additionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                } else {
                    $reductionsByBudget[$mod->budget_id] =
                        ($reductionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                }
            }
        }

        // --- Traslados de ingresos hasta cutoff ---
        $creditsByBudget = [];
        $contracreditsByBudget = [];
        if (!empty($budgetIds)) {
            $transfers = \App\Models\BudgetTransfer::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                })
                ->whereBetween('document_date', [$yearStart, $cutoff])
                ->get();

            foreach ($transfers as $t) {
                if (in_array($t->destination_budget_id, $budgetIds)) {
                    $creditsByBudget[$t->destination_budget_id] =
                        ($creditsByBudget[$t->destination_budget_id] ?? 0) + (float) $t->amount;
                }
                if (in_array($t->source_budget_id, $budgetIds)) {
                    $contracreditsByBudget[$t->source_budget_id] =
                        ($contracreditsByBudget[$t->source_budget_id] ?? 0) + (float) $t->amount;
                }
            }
        }

        // --- Ingresos recaudados hasta cutoff agrupados por funding_source_id ---
        $incomesByFunding = Income::forSchool($this->schoolId)
            ->forYear($year)
            ->whereBetween('date', [$yearStart, $cutoff])
            ->selectRaw('funding_source_id, SUM(amount) as total_collected')
            ->groupBy('funding_source_id')
            ->pluck('total_collected', 'funding_source_id')
            ->toArray();

        // Agrupar budgets por código de fuente
        $grouped = [];

        foreach ($budgets as $budget) {
            $fundingCode     = $budget->fundingSource?->code ?? '';
            $fundingSourceId = $budget->funding_source_id;

            if (!isset($grouped[$fundingCode])) {
                $grouped[$fundingCode] = [
                    'funding_source_code' => $fundingCode,
                    'initial'             => 0,
                    'definitive'          => 0,
                    'collected'           => 0,
                    '_fs_counted'         => [],
                ];
            }

            $initial       = (float) $budget->initial_amount;
            $additions     = (float) ($additionsByBudget[$budget->id] ?? 0);
            $reductions    = (float) ($reductionsByBudget[$budget->id] ?? 0);
            $credits       = (float) ($creditsByBudget[$budget->id] ?? 0);
            $contracredits = (float) ($contracreditsByBudget[$budget->id] ?? 0);
            $definitive    = $initial + $additions - $reductions + $credits - $contracredits;

            $grouped[$fundingCode]['initial']    += $initial;
            $grouped[$fundingCode]['definitive'] += $definitive;

            // Evitar doble conteo si hay varios budgets sobre la misma fuente
            if (!isset($grouped[$fundingCode]['_fs_counted'][$fundingSourceId])) {
                $grouped[$fundingCode]['collected'] += (float) ($incomesByFunding[$fundingSourceId] ?? 0);
                $grouped[$fundingCode]['_fs_counted'][$fundingSourceId] = true;
            }
        }

        // Fuentes con ingresos pero sin budget (casos borde)
        foreach ($incomesByFunding as $fsId => $amount) {
            $fs = \App\Models\FundingSource::find($fsId);
            if (!$fs) continue;
            $code = $fs->code;
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'funding_source_code' => $code,
                    'initial'             => 0,
                    'definitive'          => 0,
                    'collected'           => (float) $amount,
                    '_fs_counted'         => [$fsId => true],
                ];
            }
        }

        $sorted = collect($grouped)
            ->map(function ($row) {
                unset($row['_fs_counted']);
                return $row;
            })
            ->sortBy('funding_source_code')
            ->values();

        $this->incomeRows = $sorted->map(fn($row) => [
            'dane_code'           => $daneCode,
            'year'                => $year,
            'trimester'           => $trimester,
            'funding_source_code' => $row['funding_source_code'],
            'initial'             => $row['initial'],
            'definitive'          => $row['definitive'],
            'collected'           => $row['collected'],
        ])->toArray();

        $c = collect($this->incomeRows);
        $this->incomeTotals = [
            'initial'    => $c->sum('initial'),
            'definitive' => $c->sum('definitive'),
            'collected'  => $c->sum('collected'),
        ];
    }

    public function getPeriodLabelProperty(): string
    {
        $trimesters = [
            '1' => 'PRIMER TRIMESTRE (Ene-Mar)',
            '2' => 'SEGUNDO TRIMESTRE (Abr-Jun)',
            '3' => 'TERCER TRIMESTRE (Jul-Sep)',
            '4' => 'CUARTO TRIMESTRE (Oct-Dic)',
        ];

        $t = $trimesters[$this->filterTrimester] ?? 'CONSOLIDADO';
        return "{$t} - {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.sifse-report');
    }
}
