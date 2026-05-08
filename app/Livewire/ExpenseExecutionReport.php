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

        // --- Traslados filtrados por corte, con asignación EXACTA por distribución ---
        // Los traslados siempre especifican source_expense_distribution_id y
        // destination_expense_distribution_id. Sin prorrateo.
        $creditsByDist = [];      // crédito exacto a expense_distribution
        $contracreditsByDist = []; // contracrédito exacto a expense_distribution
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
            // Asignación exacta SIEMPRE. Dos rutas:
            //   A) CDP.convocatoria_distribution_id → distribución exacta (caso estándar actual)
            //   B) Data legacy sin convocatoria_distribution_id: si la convocatoria tiene UNA
            //      sola distribución que caiga en el budget del rfs, se asume esa.
            // Si ninguna ruta aplica, el compromiso NO se muestra (el sistema exige datos
            // completos; si falta el link es data incompleta que el usuario debe corregir).
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
                //   - RPs normales: start_date del contrato (fecha de firma del RP)
                $rpQuery->whereRaw('COALESCE(cr.otrosi_date, c.start_date) BETWEEN ? AND ?', [$dateFrom, $dateTo]);
            }
            $rpRows = $rpQuery
                ->selectRaw('rfs.id as rfs_id, rfs.budget_id, rfs.amount as rfs_amount, cvd.expense_distribution_id as exact_dist_id, cdp.convocatoria_id as conv_id')
                ->get();

            // Mapa convocatoria → distribuciones (para recuperar CDPs legacy con
            // convocatoria_id pero sin convocatoria_distribution_id)
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

            foreach ($rpRows as $row) {
                $rfsAmount = (float) $row->rfs_amount;

                // Ruta A: asignación exacta vía convocatoria_distribution_id del CDP
                if (!empty($row->exact_dist_id) && isset($validDistIds[$row->exact_dist_id])) {
                    $commitmentsByDist[$row->exact_dist_id] =
                        ($commitmentsByDist[$row->exact_dist_id] ?? 0) + $rfsAmount;
                    continue;
                }

                // Ruta B: CDP legacy con convocatoria_id pero sin convocatoria_distribution_id.
                // Buscamos entre las distribuciones de la convocatoria UNA que caiga en el
                // mismo budget del rfs. Si hay exactamente una, asignación exacta a esa.
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

                // Si no se pudo resolver exactamente, NO se agrega nada.
                // La data está incompleta y el usuario debe corregir el link del CDP.
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

        // Pagos directos (pagos sin contract_rp_id y sin expense_lines) ya no se incluyen
        // en este reporte: ese tipo de pago no pertenece a un rubro específico y ya no se
        // prorratea. Si son pagos válidos de gasto deben registrarse con su expense_line
        // apuntando a la distribución correcta, o con contract/CDP/RP que los respalde.

        $this->rows = [];

        foreach ($budgets as $budget) {
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingName = $budget->fundingSource?->name ?? '';
            $budgetInitial = (float) $budget->initial_amount;

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                // Presupuesto sin distribuciones: no se muestra porque el dinero
                // aún no está asignado a ningún rubro de gasto.
                continue;
            }

            // Suma de initial_amount de todas las distribuciones, para calcular
            // la proporción de cada rubro en la apropiación inicial del budget.
            $totalDistInitial = (float) $distributions->sum('initial_amount');

            foreach ($distributions as $dist) {
                $expCode = $dist->expenseCode;

                // Todo es asignación exacta:
                //   - Compromisos: RP → CDP → distribución
                //   - Pagos: expense_line → distribución
                //   - Créditos/contracréditos: source/destination_expense_distribution_id
                $distCommitments   = (float) ($commitmentsByDist[$dist->id] ?? 0);
                $distPayments      = (float) ($paymentsByDist[$dist->id] ?? 0);
                $distCredits       = (float) ($creditsByDist[$dist->id] ?? 0);
                $distContracredits = (float) ($contracreditsByDist[$dist->id] ?? 0);

                // Apropiación inicial del rubro: derivada del budget.
                // Si el budget nació en 0, el rubro también nace en 0 (todo es adición).
                // Si el budget tuvo apropiación inicial, se reparte proporcional a la
                // participación del rubro en el total distribuido original.
                $distInitial = 0;
                if ($budgetInitial > 0 && $totalDistInitial > 0) {
                    $distInitial = round($budgetInitial * ((float) $dist->initial_amount / $totalDistInitial), 2);
                }

                // Apropiación definitiva: valor actual del rubro (directo, sin cálculos).
                $distDefinitive = (float) $dist->amount;

                // Adiciones/reducciones del rubro:
                //   - Si hay líneas de modificación específicas, se usan esos deltas exactos.
                //   - Si no hay líneas, lo que excede o falta del inicial es adición/reducción
                //     implícita (cubre tanto adiciones generales al budget como distribuciones
                //     hechas después de una adición).
                $exactAdd = (float) ($additionsByDist[$dist->id] ?? 0);
                $exactRed = (float) ($reductionsByDist[$dist->id] ?? 0);
                if ($exactAdd > 0 || $exactRed > 0) {
                    $distAdditions  = $exactAdd;
                    $distReductions = $exactRed;
                } else {
                    // Implícitas: el delta entre definitiva y (inicial + créditos - contracréditos)
                    $netChange = $distDefinitive - $distInitial - $distCredits + $distContracredits;
                    if ($netChange >= 0) {
                        $distAdditions  = $netChange;
                        $distReductions = 0;
                    } else {
                        $distAdditions  = 0;
                        $distReductions = abs($netChange);
                    }
                }

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
