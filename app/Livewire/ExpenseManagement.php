<?php

namespace App\Livewire;

use App\Models\AccountingAccount;
use App\Models\Budget;
use App\Models\ExpenseCode;
use App\Models\ExpenseDistribution;
use App\Models\ExpenseExecution;
use App\Models\FundingSource;
use App\Models\Supplier;
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

    // Modal de ejecución
    public $showExecuteModal = false;
    public $selectedDistribution = null;
    public $executeAccountingAccountId = '';
    public $executeSupplierId = '';
    public $executeAmount = '';
    public $executeDate = '';
    public $executeDocumentNumber = '';
    public $executeDescription = '';

    // Modal crear proveedor rápido
    public $showSupplierModal = false;
    public $supplierDocumentType = 'CC';
    public $supplierDocumentNumber = '';
    public $supplierDv = '';
    public $supplierFirstName = '';
    public $supplierFirstSurname = '';
    public $supplierPersonType = 'natural';
    public $supplierEmail = '';
    public $supplierPhone = '';

    // Modal de detalle
    public $showDetailModal = false;
    public $detailBudget = null;

    // Modal eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;
    public $deleteType = '';

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
        $this->executeDate = date('Y-m-d');
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
        return Budget::with(['budgetItem', 'fundingSource', 'distributions.expenseCode', 'distributions.executions'])
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
        return \App\Models\BudgetItem::forSchool($this->schoolId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getExpenseCodesProperty()
    {
        return ExpenseCode::active()->orderBy('code')->get();
    }

    public function getAuxiliaryAccountsProperty()
    {
        return AccountingAccount::where('level', 5)
            ->where('allows_movement', true)
            ->active()
            ->orderBy('code')
            ->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::forSchool($this->schoolId)
            ->active()
            ->orderBy('first_surname')
            ->orderBy('first_name')
            ->get();
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

        $totalExecuted = ExpenseExecution::forSchool($this->schoolId)
            ->whereHas('expenseDistribution', fn($q) => $q->whereIn('budget_id', $budgets->pluck('id')))
            ->sum('amount');

        return [
            'budgeted' => $totalBudgeted,
            'distributed' => $totalDistributed,
            'executed' => $totalExecuted,
            'available' => $totalBudgeted - $totalDistributed,
            'distribution_percentage' => $totalBudgeted > 0 ? round(($totalDistributed / $totalBudgeted) * 100, 1) : 0,
            'execution_percentage' => $totalDistributed > 0 ? round(($totalExecuted / $totalDistributed) * 100, 1) : 0,
        ];
    }

    public function getAvailableForExecution($distribution)
    {
        $budget = $distribution->budget;
        $fundingSource = $budget->fundingSource;
        
        $totalIncome = $fundingSource->incomes()->sum('amount');
        
        $totalExecutedFromSource = ExpenseExecution::forSchool($this->schoolId)
            ->whereHas('expenseDistribution', fn($q) => $q->where('budget_id', $budget->id))
            ->sum('amount');
        
        $availableFromSource = $totalIncome - $totalExecutedFromSource;
        $availableFromDistribution = $distribution->available_balance;
        
        return max(0, min($availableFromSource, $availableFromDistribution));
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

    public function openExecuteModal($distributionId)
    {
        if (!auth()->user()->can('expenses.execute')) {
            $this->dispatch('toast', message: 'No tienes permisos para ejecutar gastos.', type: 'error');
            return;
        }

        $this->selectedDistribution = ExpenseDistribution::with(['budget.fundingSource', 'expenseCode', 'executions'])->find($distributionId);
        if (!$this->selectedDistribution) {
            $this->dispatch('toast', message: 'Distribución no encontrada.', type: 'error');
            return;
        }

        $this->resetExecuteForm();
        $this->executeDate = date('Y-m-d');
        $this->showExecuteModal = true;
    }

    public function saveExecution()
    {
        if (!auth()->user()->can('expenses.execute')) {
            $this->dispatch('toast', message: 'No tienes permisos para ejecutar gastos.', type: 'error');
            return;
        }

        $this->validate([
            'executeAccountingAccountId' => 'required|exists:accounting_accounts,id',
            'executeSupplierId' => 'required|exists:suppliers,id',
            'executeAmount' => 'required|numeric|min:0.01',
            'executeDate' => 'required|date',
        ], [
            'executeAccountingAccountId.required' => 'Seleccione una cuenta contable.',
            'executeSupplierId.required' => 'Seleccione un proveedor.',
            'executeAmount.required' => 'Ingrese el monto a ejecutar.',
            'executeAmount.min' => 'El monto debe ser mayor a 0.',
            'executeDate.required' => 'Ingrese la fecha de ejecución.',
        ]);

        $available = $this->getAvailableForExecution($this->selectedDistribution);
        if ($this->executeAmount > $available) {
            $this->dispatch('toast', message: 'El monto supera el disponible ($' . number_format($available, 2) . ').', type: 'error');
            return;
        }

        ExpenseExecution::create([
            'school_id' => $this->schoolId,
            'expense_distribution_id' => $this->selectedDistribution->id,
            'accounting_account_id' => $this->executeAccountingAccountId,
            'supplier_id' => $this->executeSupplierId,
            'amount' => $this->executeAmount,
            'execution_date' => $this->executeDate,
            'document_number' => $this->executeDocumentNumber,
            'description' => $this->executeDescription,
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('toast', message: 'Gasto ejecutado exitosamente.', type: 'success');
        $this->closeExecuteModal();
    }

    public function resetExecuteForm()
    {
        $this->executeAccountingAccountId = '';
        $this->executeSupplierId = '';
        $this->executeAmount = '';
        $this->executeDate = date('Y-m-d');
        $this->executeDocumentNumber = '';
        $this->executeDescription = '';
        $this->resetValidation();
    }

    public function closeExecuteModal()
    {
        $this->showExecuteModal = false;
        $this->selectedDistribution = null;
        $this->resetExecuteForm();
    }

    public function openSupplierModal()
    {
        $this->resetSupplierForm();
        $this->showSupplierModal = true;
    }

    public function saveQuickSupplier()
    {
        $this->validate([
            'supplierDocumentType' => 'required',
            'supplierDocumentNumber' => 'required|string|max:20',
            'supplierFirstName' => 'required|string|max:100',
            'supplierFirstSurname' => 'required|string|max:100',
        ], [
            'supplierDocumentType.required' => 'Seleccione tipo de documento.',
            'supplierDocumentNumber.required' => 'Ingrese número de documento.',
            'supplierFirstName.required' => 'Ingrese el nombre.',
            'supplierFirstSurname.required' => 'Ingrese el apellido.',
        ]);

        $exists = Supplier::forSchool($this->schoolId)
            ->where('document_type', $this->supplierDocumentType)
            ->where('document_number', $this->supplierDocumentNumber)
            ->exists();

        if ($exists) {
            $this->dispatch('toast', message: 'Ya existe un proveedor con este documento.', type: 'error');
            return;
        }

        $supplier = Supplier::create([
            'school_id' => $this->schoolId,
            'document_type' => $this->supplierDocumentType,
            'document_number' => $this->supplierDocumentNumber,
            'dv' => $this->supplierDocumentType === 'NIT' ? Supplier::calculateDv($this->supplierDocumentNumber) : null,
            'first_name' => $this->supplierFirstName,
            'first_surname' => $this->supplierFirstSurname,
            'person_type' => $this->supplierPersonType,
            'email' => $this->supplierEmail ?: null,
            'phone' => $this->supplierPhone ?: null,
            'is_active' => true,
        ]);

        $this->executeSupplierId = $supplier->id;
        $this->dispatch('toast', message: 'Proveedor creado exitosamente.', type: 'success');
        $this->closeSupplierModal();
    }

    public function resetSupplierForm()
    {
        $this->supplierDocumentType = 'CC';
        $this->supplierDocumentNumber = '';
        $this->supplierDv = '';
        $this->supplierFirstName = '';
        $this->supplierFirstSurname = '';
        $this->supplierPersonType = 'natural';
        $this->supplierEmail = '';
        $this->supplierPhone = '';
    }

    public function closeSupplierModal()
    {
        $this->showSupplierModal = false;
        $this->resetSupplierForm();
    }

    public function updatedSupplierDocumentType($value)
    {
        if ($value === 'NIT') {
            $this->supplierPersonType = 'juridica';
        }
    }

    public function updatedSupplierDocumentNumber($value)
    {
        if ($this->supplierDocumentType === 'NIT' && $value) {
            $this->supplierDv = Supplier::calculateDv($value);
        }
    }

    public function openDetailModal($budgetId)
    {
        $this->detailBudget = Budget::with([
            'budgetItem', 
            'fundingSource', 
            'distributions.expenseCode', 
            'distributions.executions.supplier',
            'distributions.executions.accountingAccount'
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

        $distribution = ExpenseDistribution::with('executions')->find($id);
        if ($distribution->executions->count() > 0) {
            $this->dispatch('toast', message: 'No se puede eliminar, tiene ejecuciones asociadas.', type: 'error');
            return;
        }

        $this->itemToDelete = $distribution;
        $this->deleteType = 'distribution';
        $this->showDeleteModal = true;
    }

    public function confirmDeleteExecution($id)
    {
        if (!auth()->user()->can('expenses.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar.', type: 'error');
            return;
        }

        $this->itemToDelete = ExpenseExecution::find($id);
        $this->deleteType = 'execution';
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
            $message = $this->deleteType === 'distribution' ? 'Distribución eliminada.' : 'Ejecución eliminada.';
            $this->dispatch('toast', message: $message, type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
        $this->deleteType = '';
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
