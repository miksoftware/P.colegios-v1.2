<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use App\Models\ExpenseCode;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
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

    // Paso 1: Fuente de financiación común
    public $selected_funding_source_id = '';

    // Contracrédito (origen - sale dinero)
    public $source_distribution_id = '';

    // Crédito (destino - entra dinero)
    public $destination_expense_code_id = '';

    // Datos del traslado
    public $amount = '';
    public $reason = '';
    public $document_number = '';

    // Datos dinámicos para selects
    public $availableFundingSources = [];
    public $sourceDistributions = [];
    public $destinationExpenseCodes = [];

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
            'selected_funding_source_id' => 'required|exists:funding_sources,id',
            'source_distribution_id' => 'required|exists:expense_distributions,id',
            'destination_expense_code_id' => 'required|exists:expense_codes,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
        ];
    }

    protected $messages = [
        'selected_funding_source_id.required' => 'Debe seleccionar una fuente de financiación.',
        'source_distribution_id.required' => 'Debe seleccionar el gasto origen (contracrédito).',
        'destination_expense_code_id.required' => 'Debe seleccionar el gasto destino (crédito).',
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
     * Cargar fuentes de financiación que tengan distribuciones de gasto con monto > 0
     */
    public function loadAvailableFundingSources()
    {
        $this->availableFundingSources = FundingSource::whereHas('budgets', function ($q) {
            $q->forSchool($this->schoolId)
              ->forYear((int) $this->filterYear)
              ->byType('expense')
              ->whereHas('distributions', fn($dq) => $dq->where('amount', '>', 0));
        })
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($fs) => [
                'id' => $fs->id,
                'name' => $fs->code . ' - ' . $fs->name,
            ])
            ->toArray();
    }

    /**
     * Al seleccionar fuente de financiación, cargar distribuciones origen y códigos destino
     */
    public function updatedSelectedFundingSourceId($value)
    {
        $this->source_distribution_id = '';
        $this->destination_expense_code_id = '';
        $this->sourceDistributions = [];
        $this->destinationExpenseCodes = [];
        $this->selectedSourceInfo = [];
        $this->selectedDestinationInfo = [];

        if ($value) {
            $this->loadSourceDistributions();
            $this->loadDestinationExpenseCodes();
        }
    }

    /**
     * Cargar distribuciones de gasto con monto > 0 para la fuente seleccionada
     */
    public function loadSourceDistributions()
    {
        $this->sourceDistributions = ExpenseDistribution::forSchool($this->schoolId)
            ->where('amount', '>', 0)
            ->whereHas('budget', function ($q) {
                $q->forYear((int) $this->filterYear)
                  ->byType('expense')
                  ->byFundingSource((int) $this->selected_funding_source_id);
            })
            ->with(['expenseCode', 'budget.budgetItem'])
            ->get()
            ->filter(fn($d) => $d->available_balance > 0)
            ->map(fn($d) => [
                'id' => $d->id,
                'expense_code_id' => $d->expense_code_id,
                'name' => ($d->expenseCode->code ?? '') . ' - ' . ($d->expenseCode->name ?? ''),
                'rubro' => ($d->budget->budgetItem->code ?? '') . ' - ' . ($d->budget->budgetItem->name ?? ''),
                'amount' => (float) $d->amount,
                'available_balance' => $d->available_balance,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Cargar TODOS los códigos de gasto activos para el destino (excluyendo el origen)
     */
    public function loadDestinationExpenseCodes()
    {
        $excludeExpenseCodeId = null;
        if ($this->source_distribution_id) {
            $source = collect($this->sourceDistributions)->firstWhere('id', (int) $this->source_distribution_id);
            $excludeExpenseCodeId = $source['expense_code_id'] ?? null;
        }

        $this->destinationExpenseCodes = ExpenseCode::active()
            ->when($excludeExpenseCodeId, fn($q) => $q->where('id', '!=', $excludeExpenseCodeId))
            ->orderBy('code')
            ->get()
            ->map(fn($ec) => [
                'id' => $ec->id,
                'name' => $ec->code . ' - ' . $ec->name,
            ])
            ->toArray();
    }

    /**
     * Al seleccionar distribución origen, mostrar su info y actualizar destinos
     */
    public function updatedSourceDistributionId($value)
    {
        $this->selectedSourceInfo = [];
        $this->destination_expense_code_id = '';
        $this->selectedDestinationInfo = [];

        if ($value) {
            $found = collect($this->sourceDistributions)->firstWhere('id', (int) $value);
            if ($found) {
                $this->selectedSourceInfo = $found;
            }
            $this->loadDestinationExpenseCodes();
        }
    }

    /**
     * Al seleccionar código de gasto destino, mostrar su info
     */
    public function updatedDestinationExpenseCodeId($value)
    {
        $this->selectedDestinationInfo = [];

        if ($value) {
            $ec = ExpenseCode::find($value);

            // Buscar distribución existente para este código en presupuestos con la misma fuente
            $existingDist = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', (int) $value)
                ->whereHas('budget', function ($q) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource((int) $this->selected_funding_source_id);
                })
                ->first();

            if ($existingDist) {
                $this->selectedDestinationInfo = [
                    'distribution_id' => $existingDist->id,
                    'name' => ($ec->code ?? '') . ' - ' . ($ec->name ?? ''),
                    'current_amount' => (float) $existingDist->amount,
                    'is_new' => false,
                ];
            } else {
                $this->selectedDestinationInfo = [
                    'distribution_id' => null,
                    'name' => $ec ? ($ec->code . ' - ' . $ec->name) : 'N/A',
                    'current_amount' => 0,
                    'is_new' => true,
                ];
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
        $this->loadAvailableFundingSources();
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
        $fundingSourceId = (int) $this->selected_funding_source_id;

        // Cargar distribución origen con presupuesto
        $sourceDistribution = ExpenseDistribution::forSchool($this->schoolId)
            ->with('budget')
            ->findOrFail($this->source_distribution_id);

        // Validar que el código de gasto destino sea diferente al origen
        if ($sourceDistribution->expense_code_id === (int) $this->destination_expense_code_id) {
            $this->addError('destination_expense_code_id', 'El gasto destino debe ser diferente al origen.');
            return;
        }

        // Validar saldo disponible en la distribución origen
        $availableBalance = $sourceDistribution->available_balance;
        if ($amount > $availableBalance) {
            $this->addError('amount', 'El monto no puede ser mayor al saldo disponible ($' . number_format($availableBalance, 2, ',', '.') . ').');
            return;
        }

        DB::beginTransaction();
        try {
            // Encontrar o crear distribución destino
            $destDistribution = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', (int) $this->destination_expense_code_id)
                ->whereHas('budget', function ($q) use ($fundingSourceId) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource($fundingSourceId);
                })
                ->first();

            if (!$destDistribution) {
                // Crear distribución en el mismo presupuesto que el origen
                $destDistribution = ExpenseDistribution::create([
                    'school_id' => $this->schoolId,
                    'budget_id' => $sourceDistribution->budget_id,
                    'expense_code_id' => (int) $this->destination_expense_code_id,
                    'amount' => 0,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            // Guardar montos anteriores a nivel de distribución
            $sourcePrev = (float) $sourceDistribution->amount;
            $destPrev = (float) $destDistribution->amount;

            // Actualizar montos de las distribuciones
            $sourceDistribution->update(['amount' => $sourcePrev - $amount]);
            $destDistribution->update(['amount' => $destPrev + $amount]);

            // Ajustar presupuestos si están en diferentes líneas presupuestales
            $sourceBudget = $sourceDistribution->budget;
            $destBudget = Budget::findOrFail($destDistribution->budget_id);

            if ($sourceBudget->id !== $destBudget->id) {
                $sourceBudget->update(['current_amount' => (float) $sourceBudget->current_amount - $amount]);
                $destBudget->update(['current_amount' => (float) $destBudget->current_amount + $amount]);
            }

            // Crear registro del traslado
            BudgetTransfer::create([
                'school_id' => $this->schoolId,
                'transfer_number' => BudgetTransfer::getNextTransferNumber($this->schoolId, (int) $this->filterYear),
                'source_budget_id' => $sourceBudget->id,
                'source_funding_source_id' => $fundingSourceId,
                'source_expense_distribution_id' => $sourceDistribution->id,
                'destination_budget_id' => $destBudget->id,
                'destination_funding_source_id' => $fundingSourceId,
                'destination_expense_distribution_id' => $destDistribution->id,
                'amount' => $amount,
                'source_previous_amount' => $sourcePrev,
                'source_new_amount' => $sourcePrev - $amount,
                'destination_previous_amount' => $destPrev,
                'destination_new_amount' => $destPrev + $amount,
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
        $this->selected_funding_source_id = '';
        $this->source_distribution_id = '';
        $this->destination_expense_code_id = '';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->availableFundingSources = [];
        $this->sourceDistributions = [];
        $this->destinationExpenseCodes = [];
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
