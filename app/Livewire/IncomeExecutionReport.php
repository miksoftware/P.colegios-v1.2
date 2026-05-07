<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class IncomeExecutionReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterQuarter = '';
    public $filterSemester = '';

    public $rows = [];
    public $totals = [];
    public $incomeDetails = [];

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

        // Rango de fechas ACUMULADO: siempre desde enero 1 hasta el fin del período.
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

        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with([
                'budgetItem',
                'fundingSource',
                'modifications',
            ])
            ->orderBy('budget_item_id')
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();

        // Recaudos por fuente de financiación, sin limitar a las fuentes con presupuesto
        // (así aparecen también los ingresos cuya fuente no tiene presupuesto registrado).
        $query = Income::forSchool($this->schoolId);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
        } else {
            $query->forYear($year);
        }

        $incomesByFundingSource = $query
            ->selectRaw('funding_source_id, SUM(amount) as total')
            ->groupBy('funding_source_id')
            ->pluck('total', 'funding_source_id')
            ->toArray();

        $this->rows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $definitive = (float) $budget->current_amount;

            $fundingSourceId = $budget->funding_source_id;
            $recaudos = (float) ($incomesByFundingSource[$fundingSourceId] ?? 0);
            $pending = $definitive - $recaudos;

            $this->rows[] = [
                'budget_id' => $budget->id,
                'rubro_code' => $budget->budgetItem?->code ?? '',
                'rubro_name' => $budget->budgetItem?->name ?? '',
                'funding_source_code' => $budget->fundingSource?->code ?? '',
                'funding_source_name' => $budget->fundingSource?->name ?? '',
                'initial' => $initial,
                'additions' => $additions,
                'reductions' => $reductions,
                'definitive' => $definitive,
                'recaudos' => $recaudos,
                'pending' => $pending,
            ];
        }

        // Incluir ingresos cuya fuente NO tiene presupuesto registrado del año,
        // para que el total de recaudos refleje TODOS los ingresos del colegio.
        $budgetedFsIds = $budgets->pluck('funding_source_id')->unique()->toArray();
        $orphanFsIds = array_diff(array_keys($incomesByFundingSource), $budgetedFsIds);
        if (!empty($orphanFsIds)) {
            $orphanFsList = \App\Models\FundingSource::with('budgetItem')->whereIn('id', $orphanFsIds)->get();
            foreach ($orphanFsList as $fs) {
                $recaudos = (float) ($incomesByFundingSource[$fs->id] ?? 0);
                if ($recaudos <= 0) continue;
                $this->rows[] = [
                    'budget_id'           => null,
                    'rubro_code'          => $fs->budgetItem?->code ?? '',
                    'rubro_name'          => $fs->budgetItem?->name ?? $fs->name,
                    'funding_source_code' => $fs->code ?? '',
                    'funding_source_name' => $fs->name ?? '',
                    'initial'             => 0,
                    'additions'           => 0,
                    'reductions'          => 0,
                    'definitive'          => 0,
                    'recaudos'            => $recaudos,
                    'pending'             => -$recaudos,
                ];
            }
        }

        $c = collect($this->rows);
        $this->totals = [
            'initial' => $c->sum('initial'),
            'additions' => $c->sum('additions'),
            'reductions' => $c->sum('reductions'),
            'definitive' => $c->sum('definitive'),
            'recaudos' => $c->sum('recaudos'),
            'pending' => $c->sum('pending'),
        ];

        // Detalle de cada ingreso (fila por fila) para que se vea cada recaudo individual.
        $detailsQuery = Income::forSchool($this->schoolId)
            ->with(['fundingSource.budgetItem', 'bankAccounts.bank', 'bankAccounts.bankAccount']);

        if ($dateFrom && $dateTo) {
            $detailsQuery->whereBetween('date', [$dateFrom, $dateTo]);
        } else {
            $detailsQuery->forYear($year);
        }

        $this->incomeDetails = $detailsQuery
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(function ($income) {
                $bankInfo = $income->bankAccounts->map(function ($ba) {
                    $bankName = $ba->bank?->name ?? '';
                    $acctNum  = $ba->bankAccount?->account_number ?? '';
                    return trim($bankName . ($acctNum ? ' - ' . $acctNum : ''));
                })->filter()->join(' | ');

                return [
                    'id'                  => $income->id,
                    'date'                => $income->date?->format('Y/m/d') ?? '',
                    'name'                => $income->name,
                    'description'         => $income->description,
                    'rubro_code'          => $income->fundingSource?->budgetItem?->code ?? '',
                    'rubro_name'          => $income->fundingSource?->budgetItem?->name ?? '',
                    'funding_source_code' => $income->fundingSource?->code ?? '',
                    'funding_source_name' => $income->fundingSource?->name ?? '',
                    'bank_info'           => $bankInfo,
                    'payment_method'      => $income->payment_method ?? '',
                    'reference'           => $income->transaction_reference ?? '',
                    'amount'              => (float) $income->amount,
                ];
            })
            ->toArray();

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        if ($this->filterQuarter) {
            $q = (int) $this->filterQuarter;
            $endMonths = [1 => 'MARZO', 2 => 'JUNIO', 3 => 'SEPTIEMBRE', 4 => 'DICIEMBRE'];
            $endDays   = [1 => '31', 2 => '30', 3 => '30', 4 => '31'];
            return "DE ENERO 01 AL {$endDays[$q]} DE {$endMonths[$q]} DE {$this->filterYear} (ACUMULADO AL {$q}\u00b0 TRIMESTRE)";
        }
        if ($this->filterSemester) {
            $s = (int) $this->filterSemester;
            $label = $s === 1 ? 'DE ENERO 01 AL 30 DE JUNIO' : 'DE ENERO 01 AL 31 DE DICIEMBRE';
            $sem   = $s === 1 ? 'PRIMER' : 'SEGUNDO';
            return "{$label} DE {$this->filterYear} (ACUMULADO AL {$sem} SEMESTRE)";
        }
        return "A DICIEMBRE 31 DE {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-execution-report');
    }
}
