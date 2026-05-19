<?php

namespace App\Livewire;

use App\Models\AccountingAccount;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
use App\Models\Income;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $search = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $filterFundingSource = '';
    public $perPage = 10;

    public $showModal = false;
    public $isEditing = false;
    public $budgetId = null;
    public $budget_item_id = '';
    public $initial_amount = '';
    public $fiscal_year = '';
    public $description = '';
    public $is_active = true;
    public $useMultipleSources = false;
    public $selectedFundingSourceId = '';
    public $fundingSourceAmounts = [];
    public $accounting_account_id = '';

    public $accountingAccounts = [];

    public $showModificationModal = false;
    public $modificationBudget = null;
    public $modification_type = 'addition';
    public $modification_amount = '';
    public $modification_reason = '';
    public $modification_document_number = '';

    public $showHistoryModal = false;
    public $historyBudget = null;

    public $showDeleteModal = false;
    public $itemToDelete = null;
    public $showDeleteGroupModal = false;
    public $groupToDelete = null;

    public $budgetItems = [];
    public $fundingSources = [];
    public $allFundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterFundingSource' => ['except' => ''],
    ];

    protected function rules()
    {
        $rules = [
            'budget_item_id' => 'required|exists:budget_items,id',
            'initial_amount' => 'required|numeric|min:0',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if ($this->useMultipleSources && !$this->isEditing) {
            $rules['fundingSourceAmounts'] = 'required|array|min:1';
        } else {
            $rules['selectedFundingSourceId'] = 'required|exists:funding_sources,id';
        }

        return $rules;
    }

    protected $messages = [
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'initial_amount.required' => 'El monto presupuestado es obligatorio.',
        'initial_amount.min' => 'El monto no puede ser negativo.',
        'initial_amount.numeric' => 'El monto debe ser un número.',
        'fiscal_year.required' => 'El año fiscal es obligatorio.',
        'selectedFundingSourceId.required' => 'Debe seleccionar una fuente de financiación.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budgets.view'), 403);
        
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
        $currentValidity = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->fiscal_year = $currentValidity;
        $this->filterYear = $currentValidity;
        $this->loadBudgetItems();
        $this->loadAllFundingSources();
        $this->loadAccountingAccounts();
    }

    public function loadBudgetItems()
    {
        $this->budgetItems = BudgetItem::active()
            ->orderBy('code')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => "{$item->code} - {$item->name}"])
            ->toArray();
    }

    public function loadAllFundingSources()
    {
        $this->allFundingSources = FundingSource::active()
            ->orderBy('code')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => "{$s->code} - {$s->name}"])
            ->toArray();
    }

    public function loadAccountingAccounts()
    {
        $this->accountingAccounts = AccountingAccount::where('allows_movement', true)
            ->orderBy('code')
            ->get()
            ->map(fn($a) => ['id' => $a->id, 'name' => "{$a->code} - {$a->name}"])
            ->toArray();
    }

    public function updatedBudgetItemId($value)
    {
        $this->selectedFundingSourceId = '';
        $this->fundingSourceAmounts = [];
        $this->loadFundingSourcesForItem($value);
    }

    public function loadFundingSourcesForItem($budgetItemId)
    {
        if (empty($budgetItemId)) {
            $this->fundingSources = [];
            return;
        }

        $this->fundingSources = FundingSource::forBudgetItem($budgetItemId)
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'full_name' => "{$s->code} - {$s->name}",
                'type' => $s->type,
                'type_name' => $s->type_name,
            ])
            ->toArray();

        $this->fundingSourceAmounts = [];
        foreach ($this->fundingSources as $source) {
            $this->fundingSourceAmounts[$source['id']] = '';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterFundingSource()
    {
        $this->resetPage();
    }

    public function getGroupedBudgetsProperty()
    {
        if (!$this->schoolId) {
            return new LengthAwarePaginator(collect([]), 0, $this->perPage, 1, [
                'path' => request()->url(),
                'pageName' => 'page',
            ]);
        }

        $query = Budget::where('school_id', $this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'accountingAccount', 'modifications']);
        
        if ($this->search) {
            $query->search($this->search);
        }
        
        if ($this->filterYear) {
            $query->where('fiscal_year', (int) $this->filterYear);
        }
        
        if ($this->filterFundingSource) {
            $query->where('funding_source_id', (int) $this->filterFundingSource);
        }
        
        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === '1');
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
        
        // Use Livewire's page tracking
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
        $years = Budget::forSchool($this->schoolId)->distinct()->pluck('fiscal_year')->toArray();
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }
        rsort($years);
        return $years;
    }

    public function getTotalsProperty()
    {
        $year = $this->filterYear ?: date('Y');
        $budgets = Budget::forSchool($this->schoolId)->forYear($year)->get();

        $totals = ['total_income' => 0, 'total_expense' => 0, 'balance' => 0];
        foreach ($budgets as $budget) {
            if ($budget->type === 'income') {
                $totals['total_income'] += $budget->current_amount;
            } else {
                $totals['total_expense'] += $budget->current_amount;
            }
        }
        $totals['balance'] = $totals['total_income'] - $totals['total_expense'];
        return $totals;
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('budgets.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear presupuestos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function editBudget($id)
    {
        if (!auth()->user()->can('budgets.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar presupuestos.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)->findOrFail($id);
        $this->budgetId = $budget->id;
        $this->budget_item_id = $budget->budget_item_id;
        $this->loadFundingSourcesForItem($budget->budget_item_id);
        $this->selectedFundingSourceId = $budget->funding_source_id;
        $this->initial_amount = $budget->initial_amount;
        $this->fiscal_year = $budget->fiscal_year;
        $this->description = $budget->description;
        $this->is_active = $budget->is_active;
        $this->accounting_account_id = $budget->accounting_account_id ?? '';
        $this->useMultipleSources = false;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'budgets.edit' : 'budgets.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        if ($this->useMultipleSources && !$this->isEditing) {
            $totalAssigned = collect($this->fundingSourceAmounts)
                ->filter(fn($a) => is_numeric($a) && $a >= 0)
                ->sum();
            $totalBudget = (float) $this->initial_amount;

            if (abs($totalAssigned - $totalBudget) > 0.01) {
                $formattedAssigned = number_format($totalAssigned, 2);
                $formattedBudget = number_format($totalBudget, 2);
                $this->dispatch('toast', message: "La suma asignada (\${$formattedAssigned}) debe ser igual al monto total (\${$formattedBudget}).", type: 'error');
                return;
            }
        }

        if ($this->isEditing) {
            $this->updateBudget();
        } else {
            $created = $this->createBudgets();
            if (!$created) {
                return; // Don't close modal if creation failed
            }
        }

        $this->closeModal();
    }

    protected function createBudgets(): bool
    {
        DB::beginTransaction();
        try {
            $sourcesToCreate = [];

            if ($this->useMultipleSources) {
                foreach ($this->fundingSourceAmounts as $sourceId => $amount) {
                    // Incluir fuentes con monto explícito (incluyendo 0). Saltamos strings vacíos y no numéricos.
                    if ($amount === '' || $amount === null || !is_numeric($amount)) {
                        continue;
                    }
                    $sourcesToCreate[] = ['funding_source_id' => $sourceId, 'amount' => (float) $amount];
                }

                // Si con múltiples fuentes ninguna fue capturada pero el total es 0, no hay qué crear.
                if (empty($sourcesToCreate)) {
                    $this->dispatch('toast', message: 'Debe asignar al menos una fuente.', type: 'error');
                    DB::rollBack();
                    return false;
                }
            } else {
                $sourcesToCreate[] = [
                    'funding_source_id' => $this->selectedFundingSourceId,
                    'amount' => $this->initial_amount,
                ];
            }

            foreach ($sourcesToCreate as $sourceData) {
                $exists = Budget::where('school_id', $this->schoolId)
                    ->where('budget_item_id', $this->budget_item_id)
                    ->where('funding_source_id', $sourceData['funding_source_id'])
                    ->where('fiscal_year', $this->fiscal_year)
                    ->exists();

                if ($exists) {
                    $source = FundingSource::find($sourceData['funding_source_id']);
                    $budgetItem = BudgetItem::find($this->budget_item_id);
                    DB::rollBack();
                    $this->dispatch('toast', 
                        message: "Ya existe un presupuesto para el rubro '{$budgetItem->name}' con la fuente '{$source->name}' en el año {$this->fiscal_year}.", 
                        type: 'error'
                    );
                    return false;
                }

                Budget::create([
                    'school_id' => $this->schoolId,
                    'budget_item_id' => $this->budget_item_id,
                    'funding_source_id' => $sourceData['funding_source_id'],
                    'accounting_account_id' => $this->accounting_account_id ?: null,
                    'type' => 'income',
                    'initial_amount' => $sourceData['amount'],
                    'current_amount' => $sourceData['amount'],
                    'fiscal_year' => $this->fiscal_year,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);

                Budget::create([
                    'school_id' => $this->schoolId,
                    'budget_item_id' => $this->budget_item_id,
                    'funding_source_id' => $sourceData['funding_source_id'],
                    'accounting_account_id' => $this->accounting_account_id ?: null,
                    'type' => 'expense',
                    'initial_amount' => $sourceData['amount'],
                    'current_amount' => $sourceData['amount'],
                    'fiscal_year' => $this->fiscal_year,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
            }

            DB::commit();
            $this->dispatch('toast', message: 'Presupuesto creado exitosamente.', type: 'success');
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
            return false;
        }
    }

    protected function updateBudget()
    {
        $budget = Budget::forSchool($this->schoolId)->findOrFail($this->budgetId);
        
        $data = [
            'budget_item_id' => $this->budget_item_id,
            'funding_source_id' => $this->selectedFundingSourceId,
            'accounting_account_id' => $this->accounting_account_id ?: null,
            'initial_amount' => $this->initial_amount,
            'fiscal_year' => $this->fiscal_year,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($budget->initial_amount != $this->initial_amount) {
            $data['current_amount'] = $this->initial_amount + $budget->total_additions - $budget->total_reductions
                + $budget->total_creditos - $budget->total_contracreditos;
        }
        
        $budget->update($data);
        $this->dispatch('toast', message: 'Presupuesto actualizado.', type: 'success');
    }

    public function openModificationModal($id)
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $this->modificationBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($id);
        $this->resetModificationForm();
        $this->showModificationModal = true;
    }

    public function saveModification()
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $this->validate([
            'modification_amount' => 'required|numeric|min:0.01',
            'modification_reason' => 'required|string|min:10',
        ], [
            'modification_amount.required' => 'El monto es obligatorio.',
            'modification_amount.min' => 'El monto debe ser mayor a 0.',
            'modification_reason.required' => 'La razón es obligatoria.',
            'modification_reason.min' => 'La razón debe tener al menos 10 caracteres.',
        ]);

        $budget = $this->modificationBudget;
        $amount = (float) $this->modification_amount;

        if ($this->modification_type === 'reduction' && $amount > $budget->current_amount) {
            $this->addError('modification_amount', 'La reducción no puede ser mayor al saldo actual.');
            return;
        }

        $previousAmount = $budget->current_amount;
        $newAmount = $this->modification_type === 'addition' 
            ? $previousAmount + $amount 
            : $previousAmount - $amount;

        DB::beginTransaction();
        try {
            BudgetModification::create([
                'budget_id' => $budget->id,
                'modification_number' => $budget->getNextModificationNumber(),
                'type' => $this->modification_type,
                'amount' => $amount,
                'previous_amount' => $previousAmount,
                'new_amount' => $newAmount,
                'reason' => $this->modification_reason,
                'document_number' => $this->modification_document_number,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $budget->update(['current_amount' => $newAmount]);

            DB::commit();
            $this->dispatch('toast', message: 'Modificación registrada exitosamente.', type: 'success');
            $this->closeModificationModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openHistoryModal($id)
    {
        $this->historyBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'modifications.creator'])
            ->findOrFail($id);
        $this->showHistoryModal = true;
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar presupuestos.', type: 'error');
            return;
        }

        $this->itemToDelete = Budget::forSchool($this->schoolId)
            ->with('budgetItem')
            ->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteBudget()
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar presupuestos.', type: 'error');
            return;
        }

        if (!$this->itemToDelete) {
            return;
        }

        // Validar según tipo de presupuesto
        if ($this->itemToDelete->type === 'income') {
            $hasIncome = Income::where('school_id', $this->itemToDelete->school_id)
                ->where('funding_source_id', $this->itemToDelete->funding_source_id)
                ->whereYear('date', $this->itemToDelete->fiscal_year)
                ->exists();
            if ($hasIncome) {
                $this->dispatch('toast', message: 'No se puede eliminar: este presupuesto tiene ingresos reales registrados.', type: 'error');
                $this->closeDeleteModal();
                return;
            }
        } elseif ($this->itemToDelete->type === 'expense') {
            if ($this->itemToDelete->distributions()->exists()) {
                $this->dispatch('toast', message: 'No se puede eliminar: este presupuesto tiene gastos distribuidos.', type: 'error');
                $this->closeDeleteModal();
                return;
            }
        }

        DB::beginTransaction();
        try {
            // Eliminar líneas de modificaciones primero
            foreach ($this->itemToDelete->modifications as $mod) {
                $mod->lines()->delete();
            }
            $this->itemToDelete->modifications()->delete();
            $this->itemToDelete->delete(); // LogsActivity auto-registra la eliminación

            DB::commit();
            $this->dispatch('toast', message: 'Presupuesto eliminado.', type: 'success');
            $this->closeDeleteModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function deleteModification($modId)
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar modificaciones.', type: 'error');
            return;
        }

        $mod = BudgetModification::whereHas('budget', fn($q) => $q->where('school_id', $this->schoolId))
            ->with('budget')
            ->findOrFail($modId);

        DB::beginTransaction();
        try {
            $budget = $mod->budget;
            $mod->lines()->delete();
            $mod->delete(); // LogsActivity auto-registra la eliminación

            $budget->recalculateCurrentAmount();

            // Refrescar historyBudget si el modal está abierto
            if ($this->historyBudget && $this->historyBudget->id === $budget->id) {
                $this->historyBudget = Budget::forSchool($this->schoolId)
                    ->with(['budgetItem', 'fundingSource', 'modifications.creator'])
                    ->find($budget->id);
            }

            DB::commit();
            $this->dispatch('toast', message: 'Modificación eliminada y presupuesto recalculado.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('budgets.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar el estado.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)->findOrFail($id);
        $budget->update(['is_active' => !$budget->is_active]);
        
        $status = $budget->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Presupuesto {$status}.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModificationModal()
    {
        $this->showModificationModal = false;
        $this->modificationBudget = null;
        $this->resetModificationForm();
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyBudget = null;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function confirmDeleteGroup(int $budgetItemId, int $fundingSourceId, int $fiscalYear): void
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar rubros.', type: 'error');
            return;
        }

        $income = Budget::forSchool($this->schoolId)
            ->where('budget_item_id', $budgetItemId)
            ->where('funding_source_id', $fundingSourceId)
            ->where('fiscal_year', $fiscalYear)
            ->where('type', 'income')
            ->first();

        $expense = Budget::forSchool($this->schoolId)
            ->where('budget_item_id', $budgetItemId)
            ->where('funding_source_id', $fundingSourceId)
            ->where('fiscal_year', $fiscalYear)
            ->where('type', 'expense')
            ->first();

        // Bloquear si hay ingresos reales registrados
        if ($income) {
            $hasRealIncome = Income::where('school_id', $this->schoolId)
                ->where('funding_source_id', $fundingSourceId)
                ->whereYear('date', $fiscalYear)
                ->exists();
            if ($hasRealIncome) {
                $this->dispatch('toast', message: 'No se puede eliminar: el rubro tiene ingresos reales registrados.', type: 'error');
                return;
            }
        }

        // Bloquear si hay contratos, ejecutados o pagos en gastos
        if ($expense) {
            $hasMovements = $expense->distributions()
                ->where(function ($q) {
                    $q->whereHas('executions')
                      ->orWhereHas('convocatorias')
                      ->orWhereHas('paymentOrderLines');
                })
                ->exists();
            if ($hasMovements) {
                $this->dispatch('toast', message: 'No se puede eliminar: el rubro tiene contratos o pagos registrados.', type: 'error');
                return;
            }
        }

        $budgetItem    = BudgetItem::find($budgetItemId);
        $fundingSource = FundingSource::find($fundingSourceId);

        $this->groupToDelete = [
            'income_id'         => $income?->id,
            'expense_id'        => $expense?->id,
            'item_name'         => $budgetItem?->name ?? '',
            'item_code'         => $budgetItem?->code ?? '',
            'source_name'       => $fundingSource?->name ?? '',
            'fiscal_year'       => $fiscalYear,
            'has_distributions' => $expense ? $expense->distributions()->exists() : false,
        ];
        $this->showDeleteGroupModal = true;
    }

    public function deleteGroup(): void
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar rubros.', type: 'error');
            return;
        }

        if (!$this->groupToDelete) return;

        DB::beginTransaction();
        try {
            if ($this->groupToDelete['expense_id']) {
                $expense = Budget::forSchool($this->schoolId)
                    ->with('modifications')
                    ->find($this->groupToDelete['expense_id']);
                if ($expense) {
                    // Eliminar distribuciones (sin movimientos, ya validado)
                    $expense->distributions()->each(fn($d) => $d->delete());
                    foreach ($expense->modifications as $mod) {
                        $mod->lines()->delete();
                    }
                    $expense->modifications()->delete();
                    $expense->delete();
                }
            }

            if ($this->groupToDelete['income_id']) {
                $income = Budget::forSchool($this->schoolId)
                    ->with('modifications')
                    ->find($this->groupToDelete['income_id']);
                if ($income) {
                    foreach ($income->modifications as $mod) {
                        $mod->lines()->delete();
                    }
                    $income->modifications()->delete();
                    $income->delete();
                }
            }

            DB::commit();
            $this->dispatch('toast', message: 'Rubro eliminado correctamente.', type: 'success');
            $this->closeDeleteGroupModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al eliminar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeDeleteGroupModal(): void
    {
        $this->showDeleteGroupModal = false;
        $this->groupToDelete = null;
    }

    public function resetForm()
    {
        $this->budgetId = null;
        $this->budget_item_id = '';
        $this->initial_amount = '';
        $this->fiscal_year = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->description = '';
        $this->is_active = true;
        $this->useMultipleSources = false;
        $this->selectedFundingSourceId = '';
        $this->fundingSourceAmounts = [];
        $this->fundingSources = [];
        $this->accounting_account_id = '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function resetModificationForm()
    {
        $this->modification_type = 'addition';
        $this->modification_amount = '';
        $this->modification_reason = '';
        $this->modification_document_number = '';
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->filterStatus = '';
        $this->filterFundingSource = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-management');
    }
}
