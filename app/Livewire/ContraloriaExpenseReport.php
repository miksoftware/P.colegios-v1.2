<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\PaymentOrderExpenseLine;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaExpenseReport extends Component
{
    public $schoolId;
    public $school;

    public $filterYear;

    public $rows  = [];
    public $totals = [];

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school     = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->loadReport();
    }

    public function updatedFilterYear(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $year = (int) $this->filterYear;

        // ── 1. Load all expense budgets with their distributions and modifications ──
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'modifications',
                'outgoingTransfers',
                'incomingTransfers',
                'distributions.expenseCode',
            ])
            ->orderBy('budget_item_id')
            ->get();

        // ── 2. Pre-load total payments by expense_code_id ──────────────────────────
        $paymentsByCode = PaymentOrderExpenseLine::whereHas('paymentOrder', fn($q) => $q
                ->where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->whereIn('status', ['approved', 'paid'])
            )
            ->selectRaw('expense_code_id, SUM(total) as total_paid')
            ->groupBy('expense_code_id')
            ->pluck('total_paid', 'expense_code_id')
            ->toArray();

        // ── 3. Pre-load direct payments by budget_item_id ─────────────────────────
        $budgetItemIds = $budgets->pluck('budget_item_id')->unique()->toArray();

        $directPaymentsByItem = [];
        if (!empty($budgetItemIds)) {
            $directPaymentsByItem = \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where('payment_type', 'direct')
                ->whereNull('cdp_id')
                ->whereIn('budget_item_id', $budgetItemIds)
                ->whereIn('status', ['approved', 'paid'])
                ->selectRaw('budget_item_id, SUM(total) as total_paid')
                ->groupBy('budget_item_id')
                ->pluck('total_paid', 'budget_item_id')
                ->toArray();
        }

        // ── 4. Aggregate rows by expense code ─────────────────────────────────────
        $codeRows = [];

        foreach ($budgets as $budget) {
            $initial       = (float) $budget->initial_amount;
            $additions     = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions    = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $definitive    = (float) $budget->current_amount;
            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                // No expense-code distribution: use budgetItem code/name
                $code = $budget->budgetItem?->code ?? '';
                $name = $budget->budgetItem?->name ?? '';
                $key  = $code ?: 'no-code-' . $budget->id;

                if (!isset($codeRows[$key])) {
                    $codeRows[$key] = $this->emptyRow($code, $name);
                }

                $codeRows[$key]['pac_periodo']  += $initial;
                $codeRows[$key]['adiciones']    += $additions;
                $codeRows[$key]['reducciones']  += $reductions;
                $codeRows[$key]['pac_situado']  += $definitive;

                // Direct payments for this budget item
                $codeRows[$key]['pago'] += (float) ($directPaymentsByItem[$budget->budget_item_id] ?? 0);
            } else {
                $totalDistAmount = (float) $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $code    = $expCode?->code ?? ($budget->budgetItem?->code ?? '');
                    $name    = $expCode?->name ?? ($budget->budgetItem?->name ?? '');
                    $key     = $code ?: 'no-code-dist-' . $dist->id;

                    if (!isset($codeRows[$key])) {
                        $codeRows[$key] = $this->emptyRow($code, $name);
                    }

                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;

                    $codeRows[$key]['pac_periodo'] += round($initial    * $ratio, 2);
                    $codeRows[$key]['adiciones']   += round($additions  * $ratio, 2);
                    $codeRows[$key]['reducciones'] += round($reductions * $ratio, 2);
                    $codeRows[$key]['pac_situado'] += round($definitive * $ratio, 2);

                    // Payments via expense lines for this expense code
                    if ($expCode) {
                        $codeRows[$key]['pago'] += (float) ($paymentsByCode[$expCode->id] ?? 0);
                    } else {
                        // Fallback: prorate direct payments for budget item
                        $codeRows[$key]['pago'] += round(
                            (float) ($directPaymentsByItem[$budget->budget_item_id] ?? 0) * $ratio,
                            2
                        );
                    }
                }
            }
        }

        ksort($codeRows);
        $this->rows = array_values($codeRows);

        $c = collect($this->rows);
        $this->totals = [
            'pac_periodo'  => $c->sum('pac_periodo'),
            'adiciones'    => $c->sum('adiciones'),
            'reducciones'  => $c->sum('reducciones'),
            'pac_situado'  => $c->sum('pac_situado'),
            'pago'         => $c->sum('pago'),
        ];
    }

    private function emptyRow(string $code, string $name): array
    {
        return [
            'codigo'       => $code,
            'nombre'       => $name,
            'pac_periodo'  => 0.0,
            'anticipos'    => 0.0,
            'adiciones'    => 0.0,
            'reducciones'  => 0.0,
            'aplazamientos'=> 0.0,
            'pac_situado'  => 0.0,
            'pago'         => 0.0,
        ];
    }

    public function render()
    {
        return view('livewire.contraloria-expense-report');
    }
}
