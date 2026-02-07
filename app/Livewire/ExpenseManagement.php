<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseCode;
use App\Models\ExpenseDistribution;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class ExpenseManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $filterYear;
    public $filterBudgetItem = '';
    public $search = '';

    // Modal de distribución
    public $showDistributeModal = false;
    public $selectedBudget = null;
    public $distributeExpenseCodeId = '';
    public $distributeAmount = '';
    public $distributeDescription = '';

    // Modal de detalle
    public $showDetailModal = false;
    public $detailBudget = null;

    // Modal eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;

    protected $queryString = [
        'filterYear' => ['except' => ''],
        'filterBudgetItem' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('expenses.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->filterYear = date('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatingFilterBudgetItem()
    {
        $this->resetPage();
    }

    public function getExpenseBudgetsProperty()
    {
        return Budget::with(['budgetItem', 'fundingSource', 'distributions.expenseCode'])
            ->forSchool($this->schoolId)
            ->where('type', 'expense')
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterBudgetItem, fn($q) => $q->where('budget_item_id', $this->filterBudgetItem))
            ->when($this->search, function ($q) {
                $q->whereHas('budgetItem', fn($sub) => $sub->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('fundingSource', fn($sub) => $sub->where('name', 'like', "%{$this->search}%"));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getBudgetItemsProperty()
    {
        return \App\Models\BudgetItem::active()
            ->orderBy('name')
            ->get();
    }

    public function getExpenseCodesProperty()
    {
        return ExpenseCode::active()->orderBy('code')->get();
    }

    public function getSummaryProperty()
    {
        $budgets = Budget::forSchool($this->schoolId)
            ->where('type', 'expense')
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->get();

        $totalBudgeted = $budgets->sum('current_amount');
        
        $totalDistributed = ExpenseDistribution::forSchool($this->schoolId)
            ->whereIn('budget_id', $budgets->pluck('id'))
            ->sum('amount');

        return [
            'budgeted' => $totalBudgeted,
            'distributed' => $totalDistributed,
            'available' => $totalBudgeted - $totalDistributed,
            'distribution_percentage' => $totalBudgeted > 0 ? round(($totalDistributed / $totalBudgeted) * 100, 1) : 0,
        ];
    }

    public function openDistributeModal($budgetId)
    {
        if (!auth()->user()->can('expenses.distribute')) {
            $this->dispatch('toast', message: 'No tienes permisos para distribuir gastos.', type: 'error');
            return;
        }

        $this->selectedBudget = Budget::with(['budgetItem', 'fundingSource', 'distributions'])->find($budgetId);
        if (!$this->selectedBudget) {
            $this->dispatch('toast', message: 'Presupuesto no encontrado.', type: 'error');
            return;
        }

        $this->resetDistributeForm();
        $this->showDistributeModal = true;
    }

    public function saveDistribution()
    {
        if (!auth()->user()->can('expenses.distribute')) {
            $this->dispatch('toast', message: 'No tienes permisos para distribuir gastos.', type: 'error');
            return;
        }

        $this->validate([
            'distributeExpenseCodeId' => 'required|exists:expense_codes,id',
            'distributeAmount' => 'required|numeric|min:0.01',
        ], [
            'distributeExpenseCodeId.required' => 'Seleccione un código de gasto.',
            'distributeAmount.required' => 'Ingrese el monto a distribuir.',
            'distributeAmount.min' => 'El monto debe ser mayor a 0.',
        ]);

        $exists = ExpenseDistribution::where('budget_id', $this->selectedBudget->id)
            ->where('expense_code_id', $this->distributeExpenseCodeId)
            ->exists();

        if ($exists) {
            $this->dispatch('toast', message: 'Este código de gasto ya está asignado a este presupuesto.', type: 'error');
            return;
        }

        $currentDistributed = $this->selectedBudget->distributions->sum('amount');
        $available = $this->selectedBudget->current_amount - $currentDistributed;

        if ($this->distributeAmount > $available) {
            $this->dispatch('toast', message: 'El monto supera el disponible ($' . number_format($available, 2) . ').', type: 'error');
            return;
        }

        ExpenseDistribution::create([
            'school_id' => $this->schoolId,
            'budget_id' => $this->selectedBudget->id,
            'expense_code_id' => $this->distributeExpenseCodeId,
            'amount' => $this->distributeAmount,
            'description' => $this->distributeDescription,
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('toast', message: 'Distribución creada exitosamente.', type: 'success');
        $this->closeDistributeModal();
    }

    public function getAvailableForDistribution()
    {
        if (!$this->selectedBudget) return 0;
        $currentDistributed = $this->selectedBudget->distributions->sum('amount');
        return max(0, $this->selectedBudget->current_amount - $currentDistributed);
    }

    public function resetDistributeForm()
    {
        $this->distributeExpenseCodeId = '';
        $this->distributeAmount = '';
        $this->distributeDescription = '';
        $this->resetValidation();
    }

    public function closeDistributeModal()
    {
        $this->showDistributeModal = false;
        $this->selectedBudget = null;
        $this->resetDistributeForm();
    }

    public function openDetailModal($budgetId)
    {
        $this->detailBudget = Budget::with([
            'budgetItem', 
            'fundingSource', 
            'distributions.expenseCode',
        ])->find($budgetId);
        
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailBudget = null;
    }

    public function confirmDeleteDistribution($id)
    {
        if (!auth()->user()->can('expenses.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar.', type: 'error');
            return;
        }

        $distribution = ExpenseDistribution::with('convocatorias')->find($id);
        if ($distribution && $distribution->convocatorias->count() > 0) {
            $this->dispatch('toast', message: 'No se puede eliminar, tiene convocatorias asociadas.', type: 'error');
            return;
        }

        $this->itemToDelete = $distribution;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('expenses.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar.', type: 'error');
            return;
        }

        if ($this->itemToDelete) {
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: 'Distribución eliminada.', type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function clearFilters()
    {
        $this->filterYear = date('Y');
        $this->filterBudgetItem = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.expense-management');
    }
}
