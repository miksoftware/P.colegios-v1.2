<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetAdditionReductionManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Filtros
    public $search = '';
    public $filterYear = '';
    public $perPage = 15;

    // Modal de adición/reducción
    public $showModal = false;
    public $selectedIncomeBudgetId = null;
    public $selectedExpenseBudgetId = null;
    public $selectedBudgetInfo = [];
    public $operationType = 'addition';
    public $amount = '';
    public $reason = '';
    public $document_number = '';

    // Modal de historial
    public $showHistoryModal = false;
    public $historyIncomeBudget = null;
    public $historyExpenseBudget = null;

    // Info de distribuciones afectadas
    public $affectedDistributions = [];
    // Montos a reducir/adicionar por distribución
    public $distributionReductions = [];
    // Modo de adición cuando hay distribuciones: 'general' | 'distributions'
    public $additionMode = 'general';

    // Modal de adición principal (crear nueva fuente)
    public $showPrincipalAdditionModal = false;
    public $principalBudgetItemId = '';
    public $principalFundingSourceId = '';
    public $principalAmount = '';
    public $principalReason = '';
    public $principalDocumentNumber = '';
    public $principalBudgetItems = [];
    public $principalFundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
    ];

    protected function rules()
    {
        // El monto único no se usa cuando operamos por líneas de distribución
        $byDistributions = count($this->affectedDistributions) > 0
            && ($this->operationType === 'reduction'
                || ($this->operationType === 'addition' && $this->additionMode === 'distributions'));

        return [
            'amount'          => $byDistributions ? 'nullable' : 'required|numeric|min:0.01',
            'reason'          => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
        ];
    }

    protected $messages = [
        'amount.required' => 'El monto es obligatorio.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'reason.required' => 'La observación es obligatoria.',
        'reason.min' => 'La observación debe tener al menos 10 caracteres.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budget_modifications.view'), 403, 'No tienes permisos para ver adiciones y reducciones.');

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
     * Agrupa presupuestos por rubro + fuente (ingreso y gasto juntos)
     */
    public function getGroupedBudgetsProperty()
    {
        $query = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'modifications', 'distributions']);

        if ($this->filterYear) {
            $query->forYear((int) $this->filterYear);
        }

        if ($this->search) {
            $query->search($this->search);
        }

        $budgets = $query->orderBy('budget_item_id')
            ->orderBy('funding_source_id')
            ->orderBy('type')
            ->get();

        $grouped = [];
        foreach ($budgets as $budget) {
            $key = $budget->budget_item_id . '-' . $budget->funding_source_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'budget_item' => $budget->budgetItem,
                    'funding_source' => $budget->fundingSource,
                    'fiscal_year' => $budget->fiscal_year,
                    'income' => null,
                    'expense' => null,
                ];
            }
            $grouped[$key][$budget->type] = $budget;
        }

        $collection = collect($grouped)->values();

        $page = $this->getPage();
        if ($page < 1) $page = 1;

        $total = $collection->count();
        $offset = ($page - 1) * $this->perPage;
        $items = $collection->slice($offset, $this->perPage)->values();

        return new LengthAwarePaginator($items, $total, $this->perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    public function getAvailableYearsProperty()
    {
        $years = Budget::forSchool($this->schoolId)
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

    /**
     * Totales calculados solo sobre tipo ingreso para no duplicar
     */
    public function getTotalsProperty()
    {
        $year = $this->filterYear ?: date('Y');
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear((int) $year)
            ->byType('income')
            ->get();

        $totals = [
            'total_initial' => 0,
            'total_additions' => 0,
            'total_reductions' => 0,
            'total_current' => 0,
        ];

        foreach ($budgets as $budget) {
            $totals['total_initial'] += $budget->initial_amount;
            $totals['total_additions'] += $budget->total_additions;
            $totals['total_reductions'] += $budget->total_reductions;
            $totals['total_current'] += $budget->current_amount;
        }

        return $totals;
    }

    public function getModificationHistoryProperty()
    {
        $year = $this->filterYear ?: date('Y');

        return BudgetModification::whereHas('budget', function ($q) use ($year) {
                $q->where('school_id', $this->schoolId)
                  ->where('fiscal_year', (int) $year)
                  ->where('type', 'income'); // Solo mostrar una vez (ingreso), para no duplicar
            })
            ->with(['budget.budgetItem', 'budget.fundingSource', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function openModal($incomeBudgetId, $expenseBudgetId, $type)
    {
        if (!auth()->user()->can('budget_modifications.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear adiciones o reducciones.', type: 'error');
            return;
        }

        $incomeBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($incomeBudgetId);

        $expenseBudget = Budget::forSchool($this->schoolId)
            ->with(['distributions.expenseCode'])
            ->findOrFail($expenseBudgetId);

        $this->selectedIncomeBudgetId = $incomeBudgetId;
        $this->selectedExpenseBudgetId = $expenseBudgetId;
        $this->selectedBudgetInfo = [
            'budget_item_code' => $incomeBudget->budgetItem->code ?? '',
            'budget_item_name' => $incomeBudget->budgetItem->name ?? '',
            'funding_source_name' => $incomeBudget->fundingSource->name ?? 'N/A',
            'funding_source_code' => $incomeBudget->fundingSource->code ?? '',
            'initial_amount' => (float) $incomeBudget->initial_amount,
            'current_amount' => (float) $incomeBudget->current_amount,
            'expense_current_amount' => (float) $expenseBudget->current_amount,
        ];

        $this->operationType = $type;
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->distributionReductions = [];
        $this->additionMode = 'general';
        $this->resetValidation();

        // Cargar distribuciones de gasto afectadas (con relaciones para calcular saldos)
        $expenseBudgetFull = Budget::forSchool($this->schoolId)
            ->with([
                'distributions.expenseCode',
                'distributions.convocatoriaDistributions.convocatoria.contract.rps.cdp',
                'distributions.convocatoriaDistributions.convocatoria.contract.rps.fundingSources',
                'distributions.paymentOrderLines.paymentOrder.contract',
            ])
            ->findOrFail($expenseBudgetId);

        $this->affectedDistributions = $expenseBudgetFull->distributions
            ->map(fn($d) => [
                'id'                => $d->id,
                'expense_code'      => $d->expenseCode->name ?? 'N/A',
                'expense_code_code' => $d->expenseCode->code ?? '',
                'amount'            => (float) $d->amount,
                'total_locked'      => round((float) $d->total_locked, 2),
                'available_balance' => round(max(0, (float) $d->available_balance), 2),
            ])
            ->toArray();

        // Inicializar los inputs de reducción por distribución
        foreach ($this->affectedDistributions as $dist) {
            $this->distributionReductions[$dist['id']] = '';
        }

        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('budget_modifications.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        $incomeBudget  = Budget::forSchool($this->schoolId)->findOrFail($this->selectedIncomeBudgetId);
        $expenseBudget = Budget::forSchool($this->schoolId)->findOrFail($this->selectedExpenseBudgetId);

        // ── Reducción con distribuciones: usar líneas por distribución ──
        if ($this->operationType === 'reduction' && count($this->affectedDistributions) > 0) {

            $lines = [];
            foreach ($this->distributionReductions as $distId => $rawAmt) {
                $amt = (float) str_replace(',', '.', (string) $rawAmt);
                if ($amt <= 0) continue;

                $distInfo = collect($this->affectedDistributions)->firstWhere('id', (int) $distId);
                if (!$distInfo) continue;

                if ($amt > $distInfo['available_balance'] + 0.01) {
                    $this->addError(
                        'distributionReductions.' . $distId,
                        'Excede el disponible ($' . number_format($distInfo['available_balance'], 0, ',', '.') . ')'
                    );
                    return;
                }
                $lines[] = ['id' => (int) $distId, 'amount' => min($amt, $distInfo['available_balance'])];
            }

            if (empty($lines)) {
                $this->addError('amount', 'Ingrese al menos un monto a reducir en alguna distribución.');
                return;
            }

            $amount = array_sum(array_column($lines, 'amount'));

            if ($amount > $incomeBudget->current_amount) {
                $this->addError('amount', 'La reducción total ($' . number_format($amount, 0, ',', '.') . ') supera el saldo del presupuesto ($' . number_format($incomeBudget->current_amount, 0, ',', '.') . ').');
                return;
            }

            DB::beginTransaction();
            try {
                // Reducir cada distribución
                foreach ($lines as $line) {
                    ExpenseDistribution::where('id', $line['id'])->decrement('amount', $line['amount']);
                }

                // Registrar modificación en INGRESO
                $incomePrev = $incomeBudget->current_amount;
                $incomeNew  = $incomePrev - $amount;
                BudgetModification::create([
                    'budget_id'           => $incomeBudget->id,
                    'modification_number' => $incomeBudget->getNextModificationNumber(),
                    'type'                => 'reduction',
                    'amount'              => $amount,
                    'previous_amount'     => $incomePrev,
                    'new_amount'          => $incomeNew,
                    'reason'              => $this->reason,
                    'document_number'     => $this->document_number ?: null,
                    'document_date'       => now(),
                    'created_by'          => auth()->id(),
                ]);
                $incomeBudget->update(['current_amount' => $incomeNew]);

                // Registrar modificación en GASTO
                $expensePrev = $expenseBudget->current_amount;
                $expenseNew  = $expensePrev - $amount;
                BudgetModification::create([
                    'budget_id'           => $expenseBudget->id,
                    'modification_number' => $expenseBudget->getNextModificationNumber(),
                    'type'                => 'reduction',
                    'amount'              => $amount,
                    'previous_amount'     => $expensePrev,
                    'new_amount'          => $expenseNew,
                    'reason'              => $this->reason,
                    'document_number'     => $this->document_number ?: null,
                    'document_date'       => now(),
                    'created_by'          => auth()->id(),
                ]);
                $expenseBudget->update(['current_amount' => $expenseNew]);

                DB::commit();
                $this->dispatch('toast', message: 'Reducción registrada exitosamente en ingreso, gasto y distribuciones.', type: 'success');
                $this->closeModal();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
            }
            return;
        }

        // ── Flujo normal (adición O reducción sin distribuciones) ──
        $amount = (float) $this->amount;

        // Validaciones para reducción
        if ($this->operationType === 'reduction') {
            if ($amount > $incomeBudget->current_amount) {
                $this->addError('amount', 'La reducción no puede ser mayor al saldo actual ($' . number_format($incomeBudget->current_amount, 2, ',', '.') . ').');
                return;
            }

            // Sin distribuciones: validar que no baje del total distribuido
            $totalDistributed = $expenseBudget->distributions()->sum('amount');
            $newExpenseAmount = $expenseBudget->current_amount - $amount;
            if ($newExpenseAmount < $totalDistributed) {
                $this->addError('amount', 'La reducción dejaría el presupuesto de gasto ($' . number_format($newExpenseAmount, 2, ',', '.') . ') por debajo del total distribuido ($' . number_format($totalDistributed, 2, ',', '.') . ').');
                return;
            }
        }

        DB::beginTransaction();
        try {
            // ── Adición por distribución ──
            if ($this->operationType === 'addition' && count($this->affectedDistributions) > 0 && $this->additionMode === 'distributions') {
                $lines = [];
                foreach ($this->distributionReductions as $distId => $rawAmt) {
                    $amt = (float) str_replace(',', '.', (string) $rawAmt);
                    if ($amt <= 0) continue;
                    $lines[] = ['id' => (int) $distId, 'amount' => $amt];
                }
                if (empty($lines)) {
                    DB::rollBack();
                    $this->addError('amount', 'Ingrese al menos un monto a adicionar en alguna distribución.');
                    return;
                }
                $amount = array_sum(array_column($lines, 'amount'));
                foreach ($lines as $line) {
                    ExpenseDistribution::where('id', $line['id'])->increment('amount', $line['amount']);
                }
            }

            // Aplicar modificación al presupuesto de INGRESO
            $incomePrevious = $incomeBudget->current_amount;
            $incomeNew = $this->operationType === 'addition'
                ? $incomePrevious + $amount
                : $incomePrevious - $amount;

            BudgetModification::create([
                'budget_id' => $incomeBudget->id,
                'modification_number' => $incomeBudget->getNextModificationNumber(),
                'type' => $this->operationType,
                'amount' => $amount,
                'previous_amount' => $incomePrevious,
                'new_amount' => $incomeNew,
                'reason' => $this->reason,
                'document_number' => $this->document_number ?: null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);
            $incomeBudget->update(['current_amount' => $incomeNew]);

            // Aplicar modificación al presupuesto de GASTO
            $expensePrevious = $expenseBudget->current_amount;
            $expenseNew = $this->operationType === 'addition'
                ? $expensePrevious + $amount
                : $expensePrevious - $amount;

            BudgetModification::create([
                'budget_id' => $expenseBudget->id,
                'modification_number' => $expenseBudget->getNextModificationNumber(),
                'type' => $this->operationType,
                'amount' => $amount,
                'previous_amount' => $expensePrevious,
                'new_amount' => $expenseNew,
                'reason' => $this->reason,
                'document_number' => $this->document_number ?: null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);
            $expenseBudget->update(['current_amount' => $expenseNew]);

            DB::commit();

            $typeName = $this->operationType === 'addition' ? 'Adición' : 'Reducción';
            $this->dispatch('toast', message: "{$typeName} registrada exitosamente en ingreso y gasto.", type: 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openHistoryModal($incomeBudgetId, $expenseBudgetId)
    {
        $this->historyIncomeBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'modifications.creator'])
            ->findOrFail($incomeBudgetId);

        $this->historyExpenseBudget = Budget::forSchool($this->schoolId)
            ->with(['modifications.creator'])
            ->findOrFail($expenseBudgetId);

        $this->showHistoryModal = true;
    }

    // ══════════════════════════════════════════════════════════
    // ADICIÓN PRINCIPAL (crear nueva fuente sin presupuesto)
    // ══════════════════════════════════════════════════════════

    public function openPrincipalAdditionModal()
    {
        if (!auth()->user()->can('budget_modifications.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear adiciones.', type: 'error');
            return;
        }

        $this->principalBudgetItemId = '';
        $this->principalFundingSourceId = '';
        $this->principalAmount = '';
        $this->principalReason = '';
        $this->principalDocumentNumber = '';
        $this->principalFundingSources = [];
        $this->resetValidation();

        // Cargar rubros que tienen fuentes de financiación
        $this->principalBudgetItems = BudgetItem::active()
            ->has('fundingSources')
            ->orderBy('code')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => "{$item->code} - {$item->name}"])
            ->toArray();

        $this->showPrincipalAdditionModal = true;
    }

    public function onPrincipalBudgetItemSelected()
    {
        $this->principalFundingSourceId = '';
        $this->principalFundingSources = [];

        if (empty($this->principalBudgetItemId)) return;

        $year = (int) ($this->filterYear ?: date('Y'));

        // IDs de fuentes que YA tienen presupuesto para este rubro+año+colegio
        $existingSourceIds = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->where('budget_item_id', $this->principalBudgetItemId)
            ->pluck('funding_source_id')
            ->unique()
            ->toArray();

        // Solo mostrar fuentes que NO tienen presupuesto aún
        $this->principalFundingSources = FundingSource::forBudgetItem((int) $this->principalBudgetItemId)
            ->active()
            ->whereNotIn('id', $existingSourceIds)
            ->orderBy('code')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => "{$s->code} - {$s->name}",
                'type_name' => $s->type_name,
            ])
            ->toArray();
    }

    public function savePrincipalAddition()
    {
        if (!auth()->user()->can('budget_modifications.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate([
            'principalBudgetItemId' => 'required|exists:budget_items,id',
            'principalFundingSourceId' => 'required|exists:funding_sources,id',
            'principalAmount' => 'required|numeric|min:0.01',
            'principalReason' => 'required|string|min:10',
            'principalDocumentNumber' => 'nullable|string|max:50',
        ], [
            'principalBudgetItemId.required' => 'Debe seleccionar un rubro.',
            'principalFundingSourceId.required' => 'Debe seleccionar una fuente de financiación.',
            'principalAmount.required' => 'El monto de adición es obligatorio.',
            'principalAmount.min' => 'El monto debe ser mayor a 0.',
            'principalReason.required' => 'La observación es obligatoria.',
            'principalReason.min' => 'La observación debe tener al menos 10 caracteres.',
        ]);

        $year = (int) ($this->filterYear ?: date('Y'));
        $amount = (float) $this->principalAmount;

        // Verificar que no exista ya
        $exists = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->where('budget_item_id', $this->principalBudgetItemId)
            ->where('funding_source_id', $this->principalFundingSourceId)
            ->exists();

        if ($exists) {
            $this->dispatch('toast', message: 'Ya existe un presupuesto para esta combinación de rubro y fuente. Use el botón + para adicionar.', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            // Crear Budget de INGRESO con initial_amount = 0
            $incomeBudget = Budget::create([
                'school_id' => $this->schoolId,
                'budget_item_id' => $this->principalBudgetItemId,
                'funding_source_id' => $this->principalFundingSourceId,
                'type' => 'income',
                'initial_amount' => 0,
                'current_amount' => $amount,
                'fiscal_year' => $year,
                'description' => 'Creado por adición principal',
                'is_active' => true,
            ]);

            // Registrar modificación de adición en ingreso
            BudgetModification::create([
                'budget_id' => $incomeBudget->id,
                'modification_number' => 1,
                'type' => 'addition',
                'amount' => $amount,
                'previous_amount' => 0,
                'new_amount' => $amount,
                'reason' => $this->principalReason,
                'document_number' => $this->principalDocumentNumber ?: null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            // Crear Budget de GASTO con initial_amount = 0
            $expenseBudget = Budget::create([
                'school_id' => $this->schoolId,
                'budget_item_id' => $this->principalBudgetItemId,
                'funding_source_id' => $this->principalFundingSourceId,
                'type' => 'expense',
                'initial_amount' => 0,
                'current_amount' => $amount,
                'fiscal_year' => $year,
                'description' => 'Creado por adición principal',
                'is_active' => true,
            ]);

            // Registrar modificación de adición en gasto
            BudgetModification::create([
                'budget_id' => $expenseBudget->id,
                'modification_number' => 1,
                'type' => 'addition',
                'amount' => $amount,
                'previous_amount' => 0,
                'new_amount' => $amount,
                'reason' => $this->principalReason,
                'document_number' => $this->principalDocumentNumber ?: null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $this->dispatch('toast', message: 'Adición principal creada exitosamente (ingreso y gasto).', type: 'success');
            $this->closePrincipalAdditionModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closePrincipalAdditionModal()
    {
        $this->showPrincipalAdditionModal = false;
        $this->principalBudgetItemId = '';
        $this->principalFundingSourceId = '';
        $this->principalAmount = '';
        $this->principalReason = '';
        $this->principalDocumentNumber = '';
        $this->principalBudgetItems = [];
        $this->principalFundingSources = [];
        $this->resetValidation();
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedIncomeBudgetId = null;
        $this->selectedExpenseBudgetId = null;
        $this->selectedBudgetInfo = [];
        $this->affectedDistributions = [];
        $this->distributionReductions = [];
        $this->additionMode = 'general';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->resetValidation();
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyIncomeBudget = null;
        $this->historyExpenseBudget = null;
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
        return view('livewire.budget-addition-reduction-management');
    }
}
