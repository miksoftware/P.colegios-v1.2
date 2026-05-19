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

    // Modal de distribución (crear nuevo rubro O adicionar a uno existente)
    public $showDistributeModal = false;
    public $selectedBudget = null;
    public $distributeMode = 'create'; // 'create' | 'add'
    public $distributeExpenseCodeId = '';
    public $distributeExistingDistributionId = '';
    public $distributeAmount = '';
    public $distributeDescription = '';
    public $distributeDocumentDate = '';
    public $distributeDocumentNumber = '';
    public $distributeReason = '';

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

        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
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
        return Budget::with([
            'budgetItem',
            'fundingSource',
            'modifications',
            'outgoingTransfers.destinationBudget.budgetItem',
            'outgoingTransfers.destinationBudget.fundingSource',
            'incomingTransfers.sourceBudget.budgetItem',
            'incomingTransfers.sourceBudget.fundingSource',
            'distributions.expenseCode',
            'distributions.convocatoriaDistributions.convocatoria.contract.paymentOrders',
            'distributions.convocatoriaDistributions.convocatoria.contract.supplier',
            'distributions.convocatoriaDistributions.convocatoria.contract.rps.cdp',
            'distributions.convocatoriaDistributions.convocatoria.contract.rps.fundingSources',
            'distributions.paymentOrderLines.paymentOrder.contract',
        ])
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
        
        $distributions = ExpenseDistribution::forSchool($this->schoolId)
            ->whereIn('budget_id', $budgets->pluck('id'))
            ->get();

        $totalDistributed = $distributions->sum('amount');

        // Total pagado a través de órdenes de pago
        $distributionIds = $distributions->pluck('id');
        $totalPaid = \App\Models\PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distributionIds)
            ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['draft', 'approved', 'paid']))
            ->sum('total');

        return [
            'budgeted' => $totalBudgeted,
            'distributed' => $totalDistributed,
            'available' => $totalBudgeted - $totalDistributed,
            'paid' => (float) $totalPaid,
            'distribution_percentage' => $totalBudgeted > 0 ? round(($totalDistributed / $totalBudgeted) * 100, 1) : 0,
        ];
    }

    public function openDistributeModal($budgetId)
    {
        if (!auth()->user()->can('expenses.distribute')) {
            $this->dispatch('toast', message: 'No tienes permisos para distribuir gastos.', type: 'error');
            return;
        }

        $this->selectedBudget = Budget::with(['budgetItem', 'fundingSource', 'distributions.expenseCode'])->find($budgetId);
        if (!$this->selectedBudget) {
            $this->dispatch('toast', message: 'Presupuesto no encontrado.', type: 'error');
            return;
        }

        $this->resetDistributeForm();
        $this->distributeMode = 'create';
        $this->distributeDocumentDate = now()->format('Y-m-d');
        $this->showDistributeModal = true;
    }

    public function updatedDistributeMode($value)
    {
        // Al cambiar de modo limpiar los campos específicos del otro modo
        $this->distributeExpenseCodeId = '';
        $this->distributeExistingDistributionId = '';
        $this->distributeAmount = '';
        $this->distributeDescription = '';
        $this->resetValidation();
    }

    public function saveDistribution()
    {
        if (!auth()->user()->can('expenses.distribute')) {
            $this->dispatch('toast', message: 'No tienes permisos para distribuir gastos.', type: 'error');
            return;
        }

        // Reglas de validación según el modo
        $rules = [
            'distributeAmount'        => 'required|numeric|min:0.01',
            'distributeDocumentDate'  => 'required|date',
        ];
        $messages = [
            'distributeAmount.required'       => 'Ingrese el monto a distribuir.',
            'distributeAmount.min'            => 'El monto debe ser mayor a 0.',
            'distributeDocumentDate.required' => 'La fecha de realización es obligatoria.',
            'distributeDocumentDate.date'     => 'La fecha de realización no es válida.',
        ];
        if ($this->distributeMode === 'create') {
            $rules['distributeExpenseCodeId'] = 'required|exists:expense_codes,id';
            $messages['distributeExpenseCodeId.required'] = 'Seleccione un código de gasto.';
        } else {
            $rules['distributeExistingDistributionId'] = 'required|exists:expense_distributions,id';
            $messages['distributeExistingDistributionId.required'] = 'Seleccione el rubro al cual adicionar.';
        }
        $this->validate($rules, $messages);

        $currentDistributed = $this->selectedBudget->distributions->sum('amount');
        $available = round($this->selectedBudget->current_amount - $currentDistributed, 2);
        $amount = round((float) $this->distributeAmount, 2);

        if ($amount > $available + 0.01) {
            $this->dispatch('toast', message: 'El monto supera el disponible ($' . number_format($available, 2, ',', '.') . ').', type: 'error');
            return;
        }
        if ($amount > $available) {
            $amount = $available;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($this->distributeMode === 'create') {
                // Crear nueva distribución
                $exists = ExpenseDistribution::where('budget_id', $this->selectedBudget->id)
                    ->where('expense_code_id', $this->distributeExpenseCodeId)
                    ->exists();

                if ($exists) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->dispatch('toast', message: 'Este código de gasto ya está asignado. Use la opción "Adicionar a rubro existente".', type: 'error');
                    return;
                }

                $distribution = ExpenseDistribution::create([
                    'school_id'       => $this->schoolId,
                    'budget_id'       => $this->selectedBudget->id,
                    'expense_code_id' => $this->distributeExpenseCodeId,
                    'amount'          => $amount,
                    'initial_amount'  => $amount,
                    'description'     => $this->distributeDescription,
                    'created_by'      => auth()->id(),
                ]);

                // Registrar línea de adición (amount_before = 0) para trazabilidad
                $this->recordModificationLine($distribution, 0, $amount);

                $msg = 'Distribución creada exitosamente.';
            } else {
                // Adicionar a distribución existente
                $distribution = ExpenseDistribution::where('budget_id', $this->selectedBudget->id)
                    ->where('id', $this->distributeExistingDistributionId)
                    ->firstOrFail();

                $before = (float) $distribution->amount;
                $after  = $before + $amount;
                $distribution->update(['amount' => $after]);

                $this->recordModificationLine($distribution, $before, $after);

                $msg = 'Adición a rubro existente registrada exitosamente.';
            }

            \Illuminate\Support\Facades\DB::commit();
            $this->dispatch('toast', message: $msg, type: 'success');
            $this->closeDistributeModal();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Registra una línea en la última modificación de adición del budget o crea una nueva
     * modificación "implícita" para dejar traza con la fecha indicada por el usuario.
     */
    private function recordModificationLine(ExpenseDistribution $distribution, float $before, float $after): void
    {
        $delta = $after - $before;
        if ($delta <= 0) return;

        $docDate = $this->distributeDocumentDate ?: now()->toDateString();
        $reason  = $this->distributeReason ?: 'Distribución desde módulo de gastos';
        $docNum  = $this->distributeDocumentNumber ?: null;

        // Crear una BudgetModification "implícita" tipo addition por el delta,
        // para que los reportes puedan identificarla igual que las del módulo de adición/reducción.
        $budget = $this->selectedBudget;
        $prev   = (float) $budget->current_amount;
        $mod = \App\Models\BudgetModification::create([
            'budget_id'           => $budget->id,
            'modification_number' => $budget->getNextModificationNumber(),
            'type'                => 'addition',
            'amount'              => $delta,
            'previous_amount'     => $prev,
            'new_amount'          => $prev, // no cambia current_amount porque viene de distribución
            'reason'              => $reason,
            'document_number'     => $docNum,
            'document_date'       => $docDate,
            'created_by'          => auth()->id(),
        ]);

        \App\Models\BudgetModificationLine::create([
            'budget_modification_id'  => $mod->id,
            'expense_distribution_id' => $distribution->id,
            'amount_before'           => $before,
            'amount_after'            => $after,
            'document_date'           => $docDate,
        ]);
    }

    public function getAvailableForDistribution()
    {
        if (!$this->selectedBudget) return 0;
        $currentDistributed = $this->selectedBudget->distributions->sum('amount');
        return max(0, $this->selectedBudget->current_amount - $currentDistributed);
    }

    public function resetDistributeForm()
    {
        $this->distributeMode = 'create';
        $this->distributeExpenseCodeId = '';
        $this->distributeExistingDistributionId = '';
        $this->distributeAmount = '';
        $this->distributeDescription = '';
        $this->distributeDocumentDate = '';
        $this->distributeDocumentNumber = '';
        $this->distributeReason = '';
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
            'modifications',
            'outgoingTransfers.destinationBudget.budgetItem',
            'outgoingTransfers.destinationBudget.fundingSource',
            'incomingTransfers.sourceBudget.budgetItem',
            'incomingTransfers.sourceBudget.fundingSource',
            'distributions.expenseCode',
            'distributions.convocatoriaDistributions.convocatoria.contract.paymentOrders',
            'distributions.convocatoriaDistributions.convocatoria.contract.supplier',
            'distributions.convocatoriaDistributions.convocatoria.contract.rps.cdp',
            'distributions.convocatoriaDistributions.convocatoria.contract.rps.fundingSources',
            'distributions.paymentOrderLines.paymentOrder.contract',
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

        if (!$this->itemToDelete) {
            $this->closeDeleteModal();
            return;
        }

        $distribution       = $this->itemToDelete;
        $distributionAmount = (float) $distribution->amount;
        $budget             = Budget::find($distribution->budget_id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($distribution, $distributionAmount, $budget) {
            if ($budget) {
                // Cancelar las anotaciones de ExpenseManagement vinculadas a esta distribución.
                // Son BudgetModifications donde new_amount = previous_amount (nunca cambiaron
                // current_amount, solo eran trazabilidad). Al eliminar la distribución, ya no
                // tienen sentido.
                $annotationModIds = \App\Models\BudgetModificationLine::where('expense_distribution_id', $distribution->id)
                    ->whereHas('budgetModification', fn($q) =>
                        $q->where('budget_id', $budget->id)
                          ->whereNull('cancelled_at')
                          ->whereColumn('new_amount', '=', 'previous_amount'))
                    ->pluck('budget_modification_id');

                if ($annotationModIds->isNotEmpty()) {
                    \App\Models\BudgetModification::whereIn('id', $annotationModIds)
                        ->update([
                            'cancelled_at'     => now(),
                            'cancelled_by'     => auth()->id(),
                            'cancelled_reason' => 'Distribución eliminada desde módulo de gastos',
                        ]);
                }

                // Descontar el monto del presupuesto. Al eliminar la distribución,
                // ese dinero ya no hace parte del presupuesto ejecutable.
                if ($distributionAmount > 0) {
                    $budget->decrement('current_amount', $distributionAmount);
                }
            }

            $distribution->delete();
        });

        $this->dispatch('toast', message: 'Distribución eliminada.', type: 'success');
        $this->closeDeleteModal();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function clearFilters()
    {
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
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
