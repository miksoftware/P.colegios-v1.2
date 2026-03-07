<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use App\Models\ExpenseDistribution;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class BudgetTransferManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterYear = '';
    public $perPage = 15;

    // Modal crear traslado
    public $showModal = false;

    // Contracrédito (origen - sale dinero)
    public $source_budget_id = '';
    public $source_expense_distribution_id = '';

    // Crédito (destino - entra dinero)
    public $destination_budget_id = '';

    // Datos del traslado
    public $amount = '';
    public $reason = '';
    public $document_number = '';

    // Datos dinámicos para selects
    public $sourceExpenseBudgets = [];
    public $sourceDistributions = [];
    public $destinationExpenseBudgets = [];

    // Info de selección actual
    public $selectedSourceInfo = [];
    public $selectedDestinationInfo = [];

    // Modal de detalle
    public $showDetailModal = false;
    public $detailTransfer = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'source_budget_id' => 'required|exists:budgets,id',
            'source_expense_distribution_id' => 'required|exists:expense_distributions,id',
            'destination_budget_id' => 'required|exists:budgets,id|different:source_budget_id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
        ];
    }

    protected $messages = [
        'source_budget_id.required' => 'Debe seleccionar el rubro de gasto origen.',
        'source_expense_distribution_id.required' => 'Debe seleccionar el código de gasto origen.',
        'destination_budget_id.required' => 'Debe seleccionar el rubro de gasto destino.',
        'destination_budget_id.different' => 'El rubro destino debe ser diferente al origen.',
        'amount.required' => 'El monto es obligatorio.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'reason.required' => 'La justificación es obligatoria.',
        'reason.min' => 'La justificación debe tener al menos 10 caracteres.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budget_transfers.view'), 403, 'No tienes permisos para ver traslados presupuestales.');

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            $school = auth()->user()->hasRole('Admin')
                ? \App\Models\School::first()
                : auth()->user()->schools()->first();

            if ($school) {
                session(['selected_school_id' => $school->id]);
                $this->schoolId = $school->id;
            } else {
                session()->flash('error', 'Debes seleccionar un colegio primero.');
                $this->redirect(route('dashboard'));
                return;
            }
        }

        $this->schoolId = (int) $this->schoolId;
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

    /**
     * Cargar rubros de gasto que tengan distribuciones activas
     */
    public function loadSourceExpenseBudgets()
    {
        $this->sourceExpenseBudgets = Budget::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->byType('expense')
            ->whereHas('distributions', fn($q) => $q->active()->where('amount', '>', 0))
            ->with('budgetItem', 'fundingSource')
            ->orderBy('budget_item_id')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'name' => ($b->budgetItem->code ?? '') . ' - ' . ($b->budgetItem->name ?? ''),
                'funding_source' => $b->fundingSource->name ?? 'N/A',
                'current_amount' => (float) $b->current_amount,
            ])
            ->toArray();
    }

    /**
     * Al seleccionar rubro origen, cargar sus distribuciones (códigos de gasto)
     */
    public function updatedSourceBudgetId($value)
    {
        $this->source_expense_distribution_id = '';
        $this->sourceDistributions = [];
        $this->selectedSourceInfo = [];
        $this->destination_budget_id = '';
        $this->destinationExpenseBudgets = [];
        $this->selectedDestinationInfo = [];

        if ($value) {
            $this->loadSourceDistributions($value);
        }
    }

    public function loadSourceDistributions($budgetId)
    {
        $distributions = ExpenseDistribution::where('budget_id', $budgetId)
            ->active()
            ->where('amount', '>', 0)
            ->with('expenseCode')
            ->get();

        $this->sourceDistributions = $distributions->map(function ($dist) {
            return [
                'id' => $dist->id,
                'expense_code_id' => $dist->expense_code_id,
                'expense_code' => ($dist->expenseCode->code ?? '') . ' - ' . ($dist->expenseCode->name ?? ''),
                'amount' => (float) $dist->amount,
                'available_balance' => $dist->available_balance,
            ];
        })->toArray();
    }

    /**
     * Al seleccionar código de gasto origen, cargar rubros destino con mismo código
     */
    public function updatedSourceExpenseDistributionId($value)
    {
        $this->destination_budget_id = '';
        $this->destinationExpenseBudgets = [];
        $this->selectedSourceInfo = [];
        $this->selectedDestinationInfo = [];

        if ($value) {
            $dist = ExpenseDistribution::with(['expenseCode', 'budget.budgetItem', 'budget.fundingSource'])->find($value);
            if ($dist) {
                $this->selectedSourceInfo = [
                    'distribution_id' => $dist->id,
                    'expense_code' => ($dist->expenseCode->code ?? '') . ' - ' . ($dist->expenseCode->name ?? ''),
                    'expense_code_id' => $dist->expense_code_id,
                    'budget_name' => ($dist->budget->budgetItem->code ?? '') . ' - ' . ($dist->budget->budgetItem->name ?? ''),
                    'funding_source' => $dist->budget->fundingSource->name ?? 'N/A',
                    'amount' => (float) $dist->amount,
                    'available_balance' => $dist->available_balance,
                ];

                $this->loadDestinationBudgets($dist->expense_code_id, (int) $this->source_budget_id);
            }
        }
    }

    /**
     * Cargar rubros destino que tengan distribución con el mismo código de gasto
     * excluyendo el rubro origen
     */
    public function loadDestinationBudgets($expenseCodeId, $excludeBudgetId)
    {
        $this->destinationExpenseBudgets = Budget::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->byType('expense')
            ->where('id', '!=', $excludeBudgetId)
            ->whereHas('distributions', fn($q) => $q->active()->where('expense_code_id', $expenseCodeId))
            ->with(['budgetItem', 'fundingSource', 'distributions' => fn($q) => $q->where('expense_code_id', $expenseCodeId)])
            ->orderBy('budget_item_id')
            ->get()
            ->map(function ($b) use ($expenseCodeId) {
                $dist = $b->distributions->first();
                return [
                    'id' => $b->id,
                    'name' => ($b->budgetItem->code ?? '') . ' - ' . ($b->budgetItem->name ?? ''),
                    'funding_source' => $b->fundingSource->name ?? 'N/A',
                    'current_amount' => (float) $b->current_amount,
                    'distribution_id' => $dist->id ?? null,
                    'distribution_amount' => (float) ($dist->amount ?? 0),
                ];
            })
            ->toArray();
    }

    /**
     * Al seleccionar rubro destino, mostrar su info
     */
    public function updatedDestinationBudgetId($value)
    {
        $this->selectedDestinationInfo = [];

        if ($value) {
            $found = collect($this->destinationExpenseBudgets)->firstWhere('id', (int) $value);
            if ($found) {
                $this->selectedDestinationInfo = $found;
            }
        }
    }

    public function getTransfersProperty()
    {
        return BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
                'creator'
            ])
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function getAvailableYearsProperty()
    {
        $years = BudgetTransfer::forSchool($this->schoolId)
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        rsort($years);
        return $years;
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->loadSourceExpenseBudgets();
        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        $this->validate();

        $amount = (float) $this->amount;

        // Cargar distribuciones
        $sourceDist = ExpenseDistribution::with('budget')->findOrFail($this->source_expense_distribution_id);
        $sourceBudget = Budget::forSchool($this->schoolId)->findOrFail($this->source_budget_id);

        // Encontrar la distribución destino con el mismo expense_code_id
        $destBudget = Budget::forSchool($this->schoolId)->findOrFail($this->destination_budget_id);
        $destDist = ExpenseDistribution::where('budget_id', $destBudget->id)
            ->where('expense_code_id', $sourceDist->expense_code_id)
            ->active()
            ->firstOrFail();

        // Validar saldo disponible en la distribución origen
        if ($amount > $sourceDist->available_balance) {
            $this->addError('amount', 'El monto no puede ser mayor al saldo disponible de la distribución origen ($' . number_format($sourceDist->available_balance, 0, ',', '.') . ').');
            return;
        }

        DB::beginTransaction();
        try {
            // Guardar monto anterior de las distribuciones
            $sourcePrevAmount = (float) $sourceDist->amount;
            $destPrevAmount = (float) $destDist->amount;

            // Actualizar distribución origen (sale dinero)
            $sourceDist->update(['amount' => $sourcePrevAmount - $amount]);

            // Actualizar distribución destino (entra dinero)
            $destDist->update(['amount' => $destPrevAmount + $amount]);

            // Actualizar presupuestos (current_amount)
            $sourceBudgetPrev = (float) $sourceBudget->current_amount;
            $destBudgetPrev = (float) $destBudget->current_amount;

            $sourceBudget->update(['current_amount' => $sourceBudgetPrev - $amount]);
            $destBudget->update(['current_amount' => $destBudgetPrev + $amount]);

            // Crear registro del traslado
            BudgetTransfer::create([
                'school_id' => $this->schoolId,
                'transfer_number' => BudgetTransfer::getNextTransferNumber($this->schoolId, (int) $this->filterYear),
                'source_budget_id' => $sourceBudget->id,
                'source_funding_source_id' => $sourceBudget->funding_source_id,
                'source_expense_distribution_id' => $sourceDist->id,
                'destination_budget_id' => $destBudget->id,
                'destination_funding_source_id' => $destBudget->funding_source_id,
                'destination_expense_distribution_id' => $destDist->id,
                'amount' => $amount,
                'source_previous_amount' => $sourcePrevAmount,
                'source_new_amount' => $sourcePrevAmount - $amount,
                'destination_previous_amount' => $destPrevAmount,
                'destination_new_amount' => $destPrevAmount + $amount,
                'reason' => $this->reason,
                'document_number' => $this->document_number ?: null,
                'document_date' => now(),
                'fiscal_year' => (int) $this->filterYear,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $this->dispatch('toast', message: 'Traslado (crédito/contracrédito) registrado exitosamente.', type: 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function showDetail($id)
    {
        $this->detailTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
                'creator'
            ])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailTransfer = null;
    }

    public function resetForm()
    {
        $this->source_budget_id = '';
        $this->source_expense_distribution_id = '';
        $this->destination_budget_id = '';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->sourceExpenseBudgets = [];
        $this->sourceDistributions = [];
        $this->destinationExpenseBudgets = [];
        $this->selectedSourceInfo = [];
        $this->selectedDestinationInfo = [];
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search']);
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-transfer-management');
    }
}
