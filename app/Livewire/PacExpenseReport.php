<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\PaymentOrderExpenseLine;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PacExpenseReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;

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

    public function loadReport()
    {
        $year = (int) $this->filterYear;
        $yearStart = "{$year}-01-01";
        $yearEnd   = "{$year}-12-31";

        // Get all expense budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'fundingSource',
                'distributions.expenseCode',
            ])
            ->orderBy('budget_item_id')
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();
        $distIds   = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();
        $validDistIds = array_flip($distIds);

        // ========================================================================
        // MODIFICACIONES POR DISTRIBUCIÓN (adiciones/reducciones por línea)
        // Solo las líneas dentro del año fiscal cuentan. Las anteriores son el inicial.
        // Cada línea se ubica en su mes según document_date (de la línea, fallback al mod).
        // ========================================================================
        $additionsByDistMonth = [];   // [dist_id => [mes => total]]
        $reductionsByDistMonth = [];
        $additionsByDistTotal = [];
        $reductionsByDistTotal = [];

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
                    $lineDate = $line->document_date ?? $modDate;
                    if (!$lineDate) continue;

                    $lineDateStr = $lineDate instanceof \Carbon\Carbon
                        ? $lineDate->toDateString()
                        : (string) $lineDate;

                    // Solo líneas dentro del año fiscal del reporte
                    if ($lineDateStr < $yearStart || $lineDateStr > $yearEnd) continue;

                    $month = (int) ($lineDate instanceof \Carbon\Carbon
                        ? $lineDate->format('n')
                        : substr($lineDateStr, 5, 2));

                    $delta = abs((float) $line->amount_after - (float) $line->amount_before);
                    $did   = $line->expense_distribution_id;

                    if ($isAddition) {
                        $additionsByDistMonth[$did][$month] = ($additionsByDistMonth[$did][$month] ?? 0) + $delta;
                        $additionsByDistTotal[$did] = ($additionsByDistTotal[$did] ?? 0) + $delta;
                    } else {
                        $reductionsByDistMonth[$did][$month] = ($reductionsByDistMonth[$did][$month] ?? 0) + $delta;
                        $reductionsByDistTotal[$did] = ($reductionsByDistTotal[$did] ?? 0) + $delta;
                    }
                }
            }
        }

        // ========================================================================
        // TRASLADOS (créditos/contracréditos) con asignación EXACTA
        // Distribuidos por mes según document_date del traslado.
        // ========================================================================
        $creditsByDistMonth = [];
        $contracreditsByDistMonth = [];
        $creditsByDistTotal = [];
        $contracreditsByDistTotal = [];

        if (!empty($budgetIds)) {
            $transferQuery = \App\Models\BudgetTransfer::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                });
            $transfers = $transferQuery->get();

            foreach ($transfers as $t) {
                $amt = (float) $t->amount;
                $tDate = $t->document_date;
                if (!$tDate) continue;
                $tDateStr = $tDate instanceof \Carbon\Carbon ? $tDate->toDateString() : (string) $tDate;
                if ($tDateStr < $yearStart || $tDateStr > $yearEnd) continue;

                $month = (int) ($tDate instanceof \Carbon\Carbon ? $tDate->format('n') : substr($tDateStr, 5, 2));

                // Crédito (entra al rubro destino)
                if (in_array($t->destination_budget_id, $budgetIds)
                    && !empty($t->destination_expense_distribution_id)
                    && isset($validDistIds[$t->destination_expense_distribution_id])) {
                    $did = $t->destination_expense_distribution_id;
                    $creditsByDistMonth[$did][$month] = ($creditsByDistMonth[$did][$month] ?? 0) + $amt;
                    $creditsByDistTotal[$did] = ($creditsByDistTotal[$did] ?? 0) + $amt;
                }

                // Contracrédito (sale del rubro origen)
                if (in_array($t->source_budget_id, $budgetIds)
                    && !empty($t->source_expense_distribution_id)
                    && isset($validDistIds[$t->source_expense_distribution_id])) {
                    $did = $t->source_expense_distribution_id;
                    $contracreditsByDistMonth[$did][$month] = ($contracreditsByDistMonth[$did][$month] ?? 0) + $amt;
                    $contracreditsByDistTotal[$did] = ($contracreditsByDistTotal[$did] ?? 0) + $amt;
                }
            }
        }

        // ========================================================================
        // COMPROMISOS (RPs) con asignación EXACTA por distribución.
        // El PAC refleja el mes del COMPROMISO, que es la fecha del RP:
        //   - Adiciones: otrosi_date
        //   - RPs normales: contract.start_date (fecha fiscal de expedición del RP)
        // ========================================================================
        $commitmentsByDistMonth = [];
        $commitmentsByDistTotal = [];

        if (!empty($distIds)) {
            $rpQuery = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->leftJoin('cdps as cdp', 'cdp.id', '=', 'cr.cdp_id')
                ->leftJoin('convocatoria_distributions as cvd', 'cvd.id', '=', 'cdp.convocatoria_distribution_id')
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled')
                ->selectRaw('rfs.id as rfs_id, rfs.budget_id, rfs.amount as rfs_amount, cvd.expense_distribution_id as exact_dist_id, cdp.convocatoria_id as conv_id, COALESCE(cr.otrosi_date, c.start_date) as effective_date');

            $rpRows = $rpQuery->get();

            // Mapa convocatoria → distribuciones (para CDPs legacy sin convocatoria_distribution_id)
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
                $effDate   = $row->effective_date;
                if (!$effDate) continue;
                if ($effDate < $yearStart || $effDate > $yearEnd) continue;
                $month = (int) substr($effDate, 5, 2);

                $targetDistId = null;

                // Ruta A: asignación exacta vía convocatoria_distribution_id
                if (!empty($row->exact_dist_id) && isset($validDistIds[$row->exact_dist_id])) {
                    $targetDistId = $row->exact_dist_id;
                }
                // Ruta B: legacy — si la convocatoria tiene UNA sola distribución en el budget
                elseif (!empty($row->conv_id) && isset($convToDists[$row->conv_id])) {
                    $candidates = array_values(array_filter(
                        $convToDists[$row->conv_id],
                        fn($c) => $c['budget_id'] === (int) $row->budget_id
                                 && isset($validDistIds[$c['dist_id']])
                    ));
                    if (count($candidates) === 1) {
                        $targetDistId = $candidates[0]['dist_id'];
                    }
                }

                if ($targetDistId) {
                    $commitmentsByDistMonth[$targetDistId][$month] = ($commitmentsByDistMonth[$targetDistId][$month] ?? 0) + $rfsAmount;
                    $commitmentsByDistTotal[$targetDistId] = ($commitmentsByDistTotal[$targetDistId] ?? 0) + $rfsAmount;
                }
            }

            // Pagos directos CON expense_line (pagos sin contrato pero con línea a un rubro):
            // se cuentan como compromiso+ejecución del mes del payment_date.
            $directWithLines = \Illuminate\Support\Facades\DB::table('payment_order_expense_lines as pol')
                ->join('payment_orders as po', 'po.id', '=', 'pol.payment_order_id')
                ->whereIn('pol.expense_distribution_id', $distIds)
                ->where('po.payment_type', 'direct')
                ->whereIn('po.status', ['approved', 'paid'])
                ->whereBetween('po.payment_date', [$yearStart, $yearEnd])
                ->selectRaw('pol.expense_distribution_id, MONTH(po.payment_date) as m, SUM(pol.total) as total')
                ->groupBy('pol.expense_distribution_id', 'm')
                ->get();

            foreach ($directWithLines as $row) {
                $did = $row->expense_distribution_id;
                $month = (int) $row->m;
                $amt = (float) $row->total;
                $commitmentsByDistMonth[$did][$month] = ($commitmentsByDistMonth[$did][$month] ?? 0) + $amt;
                $commitmentsByDistTotal[$did] = ($commitmentsByDistTotal[$did] ?? 0) + $amt;
            }
        }

        // ========================================================================
        // CONSTRUCCIÓN DE FILAS: una por cada expense_distribution
        // ========================================================================
        $this->rows = [];

        foreach ($budgets as $budget) {
            $distributions = $budget->distributions;
            if ($distributions->isEmpty()) continue;

            $budgetInitial = (float) $budget->initial_amount;
            $totalDistInitial = (float) $distributions->sum('initial_amount');

            foreach ($distributions as $dist) {
                $expCode = $dist->expenseCode;

                // Apropiación inicial del rubro: se deriva del budget.
                // Si el budget nació > 0, se reparte proporcional al initial_amount del rubro.
                // Si el budget nació en 0, el rubro nace en 0 (es superávit/adición).
                $distInitial = 0;
                if ($budgetInitial > 0 && $totalDistInitial > 0) {
                    $distInitial = round($budgetInitial * ((float) $dist->initial_amount / $totalDistInitial), 2);
                }

                $additions     = (float) ($additionsByDistTotal[$dist->id] ?? 0);
                $reductions    = (float) ($reductionsByDistTotal[$dist->id] ?? 0);
                $credits       = (float) ($creditsByDistTotal[$dist->id] ?? 0);
                $contracredits = (float) ($contracreditsByDistTotal[$dist->id] ?? 0);
                $commitTotal   = (float) ($commitmentsByDistTotal[$dist->id] ?? 0);

                // Apropiación definitiva CALCULADA: inicial + movimientos del año
                $definitive = $distInitial + $additions - $reductions + $credits - $contracredits;

                // Meses
                $months = [];
                for ($m = 1; $m <= 12; $m++) {
                    $months[$m] = (float) ($commitmentsByDistMonth[$dist->id][$m] ?? 0);
                }

                $this->rows[] = [
                    'code'          => $expCode?->code ?? '',
                    'name'          => $expCode?->name ?? '',
                    'initial'       => $distInitial,
                    'additions'     => $additions,
                    'reductions'    => $reductions,
                    'credits'       => $credits,
                    'contracredits' => $contracredits,
                    'definitive'    => $definitive,
                    'months'        => $months,
                    'executed'      => $commitTotal,
                    'pending'       => $definitive - $commitTotal,
                ];
            }
        }

        // Ordenar por código
        usort($this->rows, fn($a, $b) => strcmp($a['code'], $b['code']));

        // Totales
        $c = collect($this->rows);
        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = $c->sum(fn($r) => $r['months'][$m]);
        }

        $this->totals = [
            'initial'       => $c->sum('initial'),
            'additions'     => $c->sum('additions'),
            'reductions'    => $c->sum('reductions'),
            'credits'       => $c->sum('credits'),
            'contracredits' => $c->sum('contracredits'),
            'definitive'    => $c->sum('definitive'),
            'months'        => $monthTotals,
            'executed'      => $c->sum('executed'),
            'pending'       => $c->sum('pending'),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getApprovalDateProperty(): string
    {
        return $this->school->budget_approval_date
            ? \Carbon\Carbon::parse($this->school->budget_approval_date)->format('d/m/Y')
            : 'N/A';
    }

    public function getPeriodLabelProperty(): string
    {
        return "VIGENCIA {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pac-expense-report');
    }
}
