<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\PaymentOrderExpenseLine;
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
                'distributions.expenseCode',
            ])
            ->orderBy('budget_item_id')
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();
        $validDistIds = array_flip($distIds);

        // --- Modificaciones filtradas por corte ---
        // Regla: SOLO las líneas de budget_modification_lines impactan distribuciones.
        // Las adiciones/reducciones "generales" (sin líneas) afectan budget.current_amount
        // pero NO se muestran en el reporte de ejecución de gastos, porque ese monto
        // aún no está distribuido en ningún rubro (queda como saldo disponible del budget).
        //
        // Cada línea puede tener su propio document_date (fecha fiscal del movimiento)
        // que puede diferir de la fecha de la modificación padre. Se respeta la fecha
        // de la línea para el filtrado por periodo.
        $additionsByDist  = []; // asignación exacta a expense_distribution
        $reductionsByDist = [];
        if (!empty($budgetIds)) {
            // Solo necesitamos modificaciones que tengan al menos una línea
            $mods = \App\Models\BudgetModification::whereIn('budget_id', $budgetIds)
                ->whereHas('lines')
                ->with('lines')
                ->get();

            $inRange = function ($date) use ($dateFrom, $dateTo) {
                if (!$dateFrom || !$dateTo) return true;
                if (!$date) return false;
                $d = $date instanceof \Carbon\Carbon ? $date->toDateString() : (string) $date;
                return $d >= $dateFrom && $d <= $dateTo;
            };

            foreach ($mods as $mod) {
                $isAddition = $mod->type === 'addition';
                $modDate    = $mod->document_date ?? $mod->created_at;

                foreach ($mod->lines as $line) {
                    if (!isset($validDistIds[$line->expense_distribution_id])) continue;
                    $lineDate = $line->document_date ?? $modDate;
                    if (!$inRange($lineDate)) continue;

                    $delta = abs((float) $line->amount_after - (float) $line->amount_before);

                    if ($isAddition) {
                        $additionsByDist[$line->expense_distribution_id] =
                            ($additionsByDist[$line->expense_distribution_id] ?? 0) + $delta;
                    } else {
                        $reductionsByDist[$line->expense_distribution_id] =
                            ($reductionsByDist[$line->expense_distribution_id] ?? 0) + $delta;
                    }
                }
            }
        }

        // --- Traslados filtrados por corte, con asignación exacta por distribución ---
        // Si el traslado tiene source_expense_distribution_id / destination_expense_distribution_id,
        // la plata entra/sale del rubro exacto. Si no, se prorratea por amount dentro del budget.
        $creditsByBudget = [];       // fallback si no hay dist exacta
        $contracreditsByBudget = []; // fallback si no hay dist exacta
        $creditsByDist = [];         // asignación exacta a expense_distribution
        $contracreditsByDist = [];   // asignación exacta a expense_distribution
        if (!empty($budgetIds)) {
            $transferQuery = \App\Models\BudgetTransfer::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                });
            if ($dateFrom && $dateTo) {
                $transferQuery->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('document_date', [$dateFrom, $dateTo])
                      ->orWhere(function ($q2) use ($dateFrom, $dateTo) {
                          $q2->whereNull('document_date')
                             ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
                      });
                });
            }
            $transfers = $transferQuery->get();

            foreach ($transfers as $t) {
                $amt = (float) $t->amount;

                // Destino (crédito - entra al rubro destino)
                if (in_array($t->destination_budget_id, $budgetIds)) {
                    if (!empty($t->destination_expense_distribution_id)
                        && isset($validDistIds[$t->destination_expense_distribution_id])) {
                        $creditsByDist[$t->destination_expense_distribution_id] =
                            ($creditsByDist[$t->destination_expense_distribution_id] ?? 0) + $amt;
                    } else {
                        $creditsByBudget[$t->destination_budget_id] =
                            ($creditsByBudget[$t->destination_budget_id] ?? 0) + $amt;
                    }
                }

                // Origen (contracrédito - sale del rubro origen)
                if (in_array($t->source_budget_id, $budgetIds)) {
                    if (!empty($t->source_expense_distribution_id)
                        && isset($validDistIds[$t->source_expense_distribution_id])) {
                        $contracreditsByDist[$t->source_expense_distribution_id] =
                            ($contracreditsByDist[$t->source_expense_distribution_id] ?? 0) + $amt;
                    } else {
                        $contracreditsByBudget[$t->source_budget_id] =
                            ($contracreditsByBudget[$t->source_budget_id] ?? 0) + $amt;
                    }
                }
            }
        }

        // Pre-load commitments per expense_distribution:
        // Los compromisos son los RPs (Registros Presupuestales) que se hacen por contrato.
        // La cadena es: rp_funding_sources → contract_rps → contracts → convocatorias →
        // convocatoria_distributions → expense_distributions.
        // El JOIN a expense_distributions garantiza que el budget_id del RP coincide
        // con el de la distribución (evita duplicados cuando hay varias distribuciones
        // por convocatoria).

        $commitmentsByDist = [];
        if (!empty($distIds)) {
            // --- Compromisos reales: RPs vía CDP → convocatoria_distribution → expense_distribution.
            // Estrategia de asignación:
            //   1) Si el CDP tiene convocatoria_distribution_id → se conoce la distribución EXACTA.
            //   2) Si el CDP tiene convocatoria_id pero NO convocatoria_distribution_id:
            //      - Si la convocatoria tiene UNA sola distribución en el mismo budget del RP
            //        (caso común en contratos simples) → usar esa distribución como exacta.
            //      - Si tiene varias → prorratear entre ESAS (no entre todas las del budget).
            //   3) Si el CDP no tiene convocatoria → prorratear entre todas las distribuciones
            //      del budget (legacy).
            $rpQuery = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->leftJoin('cdps as cdp', 'cdp.id', '=', 'cr.cdp_id')
                ->leftJoin('convocatoria_distributions as cvd', 'cvd.id', '=', 'cdp.convocatoria_distribution_id')
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled');
            if ($dateFrom && $dateTo) {
                // Fecha efectiva del RP:
                //   - Adiciones: otrosi_date (fecha del otrosí)
                //   - RPs normales: start_date del contrato (fecha de firma y expedición del RP)
                $rpQuery->whereRaw('COALESCE(cr.otrosi_date, c.start_date) BETWEEN ? AND ?', [$dateFrom, $dateTo]);
            }
            $rpRows = $rpQuery
                ->selectRaw('rfs.id as rfs_id, rfs.budget_id, rfs.amount as rfs_amount, cvd.expense_distribution_id as exact_dist_id, cdp.convocatoria_id as conv_id')
                ->get();

            // Mapa convocatoria → distribuciones (fallback cuando el CDP tiene convocatoria
            // pero no convocatoria_distribution_id)
            $convIds = $rpRows->pluck('conv_id')->filter()->unique()->all();
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

            // Mapa budget_id → colección de distribuciones (id + amount)
            $distsByBudget = [];
            foreach ($budgets as $b) {
                $distsByBudget[$b->id] = $b->distributions->map(fn($d) => [
                    'id'     => $d->id,
                    'amount' => (float) $d->amount,
                ])->values()->all();
            }

            foreach ($rpRows as $row) {
                $rfsAmount = (float) $row->rfs_amount;

                // Caso 1: el CDP indica la distribución exacta → asignación 1:1
                if (!empty($row->exact_dist_id) && isset($validDistIds[$row->exact_dist_id])) {
                    $commitmentsByDist[$row->exact_dist_id] =
                        ($commitmentsByDist[$row->exact_dist_id] ?? 0) + $rfsAmount;
                    continue;
                }

                // Caso 2: CDP tiene convocatoria_id pero no convocatoria_distribution_id.
                if (!empty($row->conv_id) && isset($convToDists[$row->conv_id])) {
                    // Filtrar las distribuciones de la convocatoria que caen en el mismo budget
                    // del rfs y que están dentro del reporte.
                    $candidates = array_values(array_filter(
                        $convToDists[$row->conv_id],
                        fn($c) => $c['budget_id'] === (int) $row->budget_id
                                 && isset($validDistIds[$c['dist_id']])
                    ));

                    if (count($candidates) === 1) {
                        // Una sola distribución → asignación exacta a esa
                        $commitmentsByDist[$candidates[0]['dist_id']] =
                            ($commitmentsByDist[$candidates[0]['dist_id']] ?? 0) + $rfsAmount;
                        continue;
                    }
                    if (count($candidates) > 1) {
                        // Varias → prorratear entre ESAS (no entre todas las del budget)
                        $candDistAmts = [];
                        foreach ($candidates as $c) {
                            foreach ($distsByBudget[$row->budget_id] ?? [] as $d) {
                                if ($d['id'] === $c['dist_id']) {
                                    $candDistAmts[$c['dist_id']] = $d['amount'];
                                    break;
                                }
                            }
                        }
                        $total = array_sum($candDistAmts);
                        if ($total > 0) {
                            foreach ($candDistAmts as $did => $amt) {
                                $ratio = $amt / $total;
                                $commitmentsByDist[$did] =
                                    ($commitmentsByDist[$did] ?? 0) + $rfsAmount * $ratio;
                            }
                            continue;
                        }
                    }
                }

                // Caso 3: CDP sin convocatoria o sin candidatos válidos → prorratear entre
                // las distribuciones del budget según su amount (legacy).
                $dists = $distsByBudget[$row->budget_id] ?? [];
                if (empty($dists)) continue;

                $totalDist = array_sum(array_column($dists, 'amount'));
                foreach ($dists as $d) {
                    if (!isset($validDistIds[$d['id']])) continue;
                    $ratio = $totalDist > 0 ? $d['amount'] / $totalDist : 0;
                    $commitmentsByDist[$d['id']] =
                        ($commitmentsByDist[$d['id']] ?? 0) + $rfsAmount * $ratio;
                }
            }

            // --- Pagos directos CON expense_lines por distribución (pagos sin contrato) ---
            // Estos sí son compromisos ya ejecutados y se suman a su distribución exacta.
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
            $q2 = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->whereIn('rfs.budget_id', $emptyBudgetIds)
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled');
            if ($dateFrom && $dateTo) {
                // Misma lógica: fecha efectiva = otrosi_date (adiciones) o start_date del contrato
                $q2->whereRaw('COALESCE(cr.otrosi_date, c.start_date) BETWEEN ? AND ?', [$dateFrom, $dateTo]);
            }
            $commitmentsByBudget = $q2->selectRaw('rfs.budget_id, SUM(rfs.amount) as total')
                ->groupBy('rfs.budget_id')
                ->pluck('total', 'rfs.budget_id')
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
            // Nota: las adiciones/reducciones generales (sin líneas por rubro) ya NO se
            // muestran en este reporte. Solo impactan budget.current_amount y quedan
            // como saldo disponible hasta que el usuario las distribuya.
            $additions = 0;
            $reductions = 0;
            // Traslados que no tienen distribución exacta (fallback prorrateable)
            $credits = (float) ($creditsByBudget[$budget->id] ?? 0);
            $contracredits = (float) ($contracreditsByBudget[$budget->id] ?? 0);
            $totalBudgetCommitments = (float) ($commitmentsByBudget[$budget->id] ?? 0); // solo para presupuestos sin distribuciones
            $directPayments = (float) ($directPaymentsByBudgetItem[$budget->budget_item_id] ?? 0);
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingName = $budget->fundingSource?->name ?? '';

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                // Presupuesto sin distribuciones: no se muestra porque el dinero
                // aún no está asignado a ningún rubro de gasto.
                continue;
            } else {
                $totalDistAmount = $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $distPayments = (float) ($paymentsByDist[$dist->id] ?? 0);
                    $distCommitments = (float) ($commitmentsByDist[$dist->id] ?? 0);
                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;

                    // Créditos/contracréditos de esta distribución:
                    //   1) Los traslados con destination/source_expense_distribution_id === dist.id → valor exacto
                    //   2) Más la parte prorrateada de traslados que no especificaron distribución exacta
                    $exactCred = (float) ($creditsByDist[$dist->id] ?? 0);
                    $exactCont = (float) ($contracreditsByDist[$dist->id] ?? 0);
                    $distCredits       = $exactCred + round($credits * $ratio, 2);
                    $distContracredits = $exactCont + round($contracredits * $ratio, 2);

                    // Adiciones/reducciones SOLO las que tienen línea por rubro
                    $distAdditions  = (float) ($additionsByDist[$dist->id] ?? 0);
                    $distReductions = (float) ($reductionsByDist[$dist->id] ?? 0);

                    // Apropiación inicial: valor histórico del rubro al momento de crearlo
                    // (initial_amount). Si por alguna razón está en 0 (registros muy viejos
                    // antes del backfill) usamos el amount actual como fallback.
                    $distInitial = (float) $dist->initial_amount;
                    if ($distInitial <= 0) {
                        $distInitial = (float) $dist->amount;
                    }

                    // Apropiación definitiva: valor actual del rubro (refleja todas
                    // las adiciones/reducciones por línea y los traslados).
                    $distDefinitive = (float) $dist->amount;

                    $this->rows[] = [
                        'budget_id' => $budget->id,
                        'rubro_code' => $expCode?->code ?? '',
                        'rubro_name' => $expCode?->name ?? '',
                        'funding_source_code' => $fundingCode,
                        'funding_source_name' => $fundingName,
                        'initial' => $distInitial,
                        'additions' => $distAdditions,
                        'reductions' => $distReductions,
                        'credits' => $distCredits,
                        'contracredits' => $distContracredits,
                        'definitive' => $distDefinitive,
                        'commitments' => $distCommitments,
                        'obligations' => $distPayments,
                        'payments' => $distPayments,
                        'pending' => $distDefinitive - $distCommitments,
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
