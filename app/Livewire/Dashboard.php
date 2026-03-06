<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use App\Models\Cdp;
use App\Models\Contract;
use App\Models\Convocatoria;
use App\Models\ExpenseDistribution;
use App\Models\Income;
use App\Models\PaymentOrder;
use App\Models\School;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $schoolId;
    public $school;
    public $year;

    // Estadísticas presupuestales
    public $totalIncomeBudget = 0;
    public $totalExpenseBudget = 0;
    public $totalIncomeReceived = 0;
    public $totalExpenseExecuted = 0;
    public $totalExpenseCommitted = 0;
    public $incomeExecutionPercent = 0;
    public $expenseExecutionPercent = 0;

    // Contadores
    public $totalCdps = 0;
    public $totalConvocatorias = 0;
    public $totalContracts = 0;
    public $totalPaymentOrders = 0;
    public $totalSuppliers = 0;
    public $totalTransfers = 0;

    // Datos para gráficos
    public $budgetBySource = [];
    public $recentContracts = [];
    public $recentPayments = [];
    public $expenseByItem = [];
    public $monthlyIncome = [];

    public function mount()
    {
        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            return;
        }

        $this->school = School::find($this->schoolId);
        $this->year = $this->school?->current_validity ?? date('Y');

        $this->loadStats();
    }

    public function loadStats()
    {
        $schoolId = $this->schoolId;
        $year = $this->year;

        // ── Presupuesto de ingresos y gastos ──
        $budgets = Budget::where('school_id', $schoolId)
            ->where('fiscal_year', $year)
            ->where('is_active', true)
            ->get();

        $this->totalIncomeBudget = $budgets->where('type', 'income')->sum('current_amount');
        $this->totalExpenseBudget = $budgets->where('type', 'expense')->sum('current_amount');

        // ── Ingresos recaudados ──
        $this->totalIncomeReceived = Income::where('school_id', $schoolId)
            ->whereYear('date', $year)
            ->sum('amount');

        $this->incomeExecutionPercent = $this->totalIncomeBudget > 0
            ? round(($this->totalIncomeReceived / $this->totalIncomeBudget) * 100, 1)
            : 0;

        // ── Gastos ejecutados y comprometidos ──
        $distributions = ExpenseDistribution::where('school_id', $schoolId)
            ->whereHas('budget', fn($q) => $q->where('fiscal_year', $year))
            ->where('is_active', true)
            ->get();

        $this->totalExpenseExecuted = $distributions->sum(fn($d) => $d->total_executed);
        $this->totalExpenseCommitted = $distributions->sum(fn($d) => $d->total_committed);

        $this->expenseExecutionPercent = $this->totalExpenseBudget > 0
            ? round(($this->totalExpenseExecuted / $this->totalExpenseBudget) * 100, 1)
            : 0;

        // ── Contadores ──
        $this->totalCdps = Cdp::where('school_id', $schoolId)
            ->where('fiscal_year', $year)->count();

        $this->totalConvocatorias = Convocatoria::where('school_id', $schoolId)
            ->where('fiscal_year', $year)->count();

        $this->totalContracts = Contract::where('school_id', $schoolId)
            ->where('fiscal_year', $year)->count();

        $this->totalPaymentOrders = PaymentOrder::where('school_id', $schoolId)
            ->where('fiscal_year', $year)->count();

        $this->totalSuppliers = Supplier::where('school_id', $schoolId)
            ->where('is_active', true)->count();

        $this->totalTransfers = BudgetTransfer::where('school_id', $schoolId)
            ->where('fiscal_year', $year)->count();

        // ── Presupuesto por fuente de financiación ──
        $this->budgetBySource = Budget::where('budgets.school_id', $schoolId)
            ->where('budgets.fiscal_year', $year)
            ->where('budgets.type', 'expense')
            ->where('budgets.is_active', true)
            ->join('funding_sources', 'budgets.funding_source_id', '=', 'funding_sources.id')
            ->select('funding_sources.type as source_type', DB::raw('SUM(budgets.current_amount) as total'))
            ->groupBy('funding_sources.type')
            ->get()
            ->map(fn($r) => [
                'type' => match($r->source_type) {
                    'sgp' => 'SGP',
                    'rp' => 'Recursos Propios',
                    'rb' => 'Recursos Balance',
                    default => 'Otros',
                },
                'total' => $r->total,
            ])
            ->toArray();

        // ── Gastos por rubro (top 5) ──
        $this->expenseByItem = Budget::where('budgets.school_id', $schoolId)
            ->where('budgets.fiscal_year', $year)
            ->where('budgets.type', 'expense')
            ->where('budgets.is_active', true)
            ->join('budget_items', 'budgets.budget_item_id', '=', 'budget_items.id')
            ->select(
                'budget_items.name',
                'budget_items.code',
                DB::raw('SUM(budgets.current_amount) as presupuestado')
            )
            ->groupBy('budget_items.id', 'budget_items.name', 'budget_items.code')
            ->orderByDesc('presupuestado')
            ->limit(5)
            ->get()
            ->toArray();

        // ── Ingresos mensuales ──
        $this->monthlyIncome = Income::where('school_id', $schoolId)
            ->whereYear('date', $year)
            ->select(
                DB::raw('MONTH(date) as mes'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('mes')
            ->get()
            ->toArray();

        // ── Contratos recientes ──
        $this->recentContracts = Contract::where('school_id', $schoolId)
            ->where('fiscal_year', $year)
            ->with('supplier')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'number' => $c->contract_number,
                'supplier' => $c->supplier?->full_name ?? 'N/A',
                'total' => $c->total,
                'status' => $c->status,
                'object' => \Illuminate\Support\Str::limit($c->object, 50),
            ])
            ->toArray();

        // ── Pagos recientes ──
        $this->recentPayments = PaymentOrder::where('school_id', $schoolId)
            ->where('fiscal_year', $year)
            ->with('contract.supplier')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'number' => $p->payment_number,
                'supplier' => $p->contract?->supplier?->full_name ?? 'N/A',
                'net_payment' => $p->net_payment,
                'status' => $p->status,
                'date' => $p->payment_date?->format('d/m/Y') ?? '',
            ])
            ->toArray();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dashboard');
    }
}
