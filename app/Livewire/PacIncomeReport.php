<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PacIncomeReport extends Component
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

        // Get all income budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with(['budgetItem', 'fundingSource'])
            ->orderBy('budget_item_id')
            ->get();

        // Pre-load incomes grouped by funding_source_id and month
        $fundingSourceIds = $budgets->pluck('funding_source_id')->unique()->filter()->toArray();

        $incomesByFsMonth = [];
        $incomeObservations = [];

        if (!empty($fundingSourceIds)) {
            $incomes = Income::forSchool($this->schoolId)
                ->forYear($year)
                ->whereIn('funding_source_id', $fundingSourceIds)
                ->get();

            foreach ($incomes as $income) {
                $fsId = $income->funding_source_id;
                $month = (int) $income->date->format('m');

                if (!isset($incomesByFsMonth[$fsId])) {
                    $incomesByFsMonth[$fsId] = [];
                }
                if (!isset($incomesByFsMonth[$fsId][$month])) {
                    $incomesByFsMonth[$fsId][$month] = 0;
                }
                $incomesByFsMonth[$fsId][$month] += (float) $income->amount;

                // Collect observations (description) for non-empty descriptions
                if (!empty($income->description)) {
                    $incomeObservations[$fsId][] = $income->description;
                }
            }
        }

        // Build rows: one per budget item (concept)
        $conceptRows = [];

        foreach ($budgets as $budget) {
            $budgetItem = $budget->budgetItem;
            $code = $budgetItem?->code ?? '';
            $name = $budgetItem?->name ?? '';
            $key = $code ?: 'no-code-' . $budget->id;

            $fsId = $budget->funding_source_id;
            $monthlyData = $incomesByFsMonth[$fsId] ?? [];

            if (!isset($conceptRows[$key])) {
                $months = [];
                for ($m = 1; $m <= 12; $m++) {
                    $months[$m] = 0;
                }
                $conceptRows[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'months' => $months,
                    'total' => 0,
                    'observations' => '',
                ];
            }

            for ($m = 1; $m <= 12; $m++) {
                $conceptRows[$key]['months'][$m] += (float) ($monthlyData[$m] ?? 0);
            }

            // Merge observations
            if (!empty($incomeObservations[$fsId])) {
                $existing = $conceptRows[$key]['observations'];
                $new = implode('. ', array_unique($incomeObservations[$fsId]));
                $conceptRows[$key]['observations'] = $existing
                    ? $existing . '. ' . $new
                    : $new;
            }
        }

        // Calculate totals per row
        foreach ($conceptRows as &$row) {
            $row['total'] = array_sum($row['months']);
        }
        unset($row);

        // Sort by code
        ksort($conceptRows);
        $this->rows = array_values($conceptRows);

        // Totals
        $c = collect($this->rows);
        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = $c->sum(fn($r) => $r['months'][$m]);
        }

        $this->totals = [
            'months' => $monthTotals,
            'total' => $c->sum('total'),
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
        return view('livewire.pac-income-report');
    }
}
