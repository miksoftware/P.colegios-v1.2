<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Bank;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\FundingSource;
use App\Models\Income;
use App\Models\IncomeBankAccount;
use App\Models\School;
use App\Support\IncomeRegistrationCatalog;
use App\Support\MonthlyIncomeReceiptBuilder;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class IncomeManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Filtros
    public $search = '';
    public $filterYear = '';
    public $filterBudgetItem = '';
    public $filterSource = '';
    public $filterStatus = '';
    public $perPage = 15;

    // Modal Crear/Editar Ingreso
    public $showModal = false;
    public $showBatchModal = false;
    public $isEditing = false;
    public $incomeId = null;

    // Formulario Ingreso
    public $budget_item_id = '';
    public $funding_source_id = '';
    public $name = '';
    public $description = '';
    public $amount = '';
    public $date = '';

    // Líneas de cuentas bancarias
    public $bankAccountLines = [];
    public $availableBanks = [];
    public $lineAccounts = []; // Cuentas por línea

    // Registro múltiple de ingresos
    public $batchDate = '';
    public $batchDescription = '';
    public $batchIncomeLines = [];
    public $batchLineAccounts = [];

    // Info del presupuesto seleccionado
    public $selectedBudgetInfo = null;
    public $exceedsAmount = 0;
    public $showExceedsWarning = false;

    // Modal Confirmación Eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Modal Marcar como Completado (reducción)
    public $showCompleteModal = false;
    public $budgetToComplete = null;

    // Modal Aplicar Adición Pendiente
    public $showAdditionModal = false;
    public $budgetForAddition = null;
    public $additionReason = '';

    public $showMonthlyReceiptModal = false;
    public $monthlyReceiptMonth = '';

    // Listas para selects
    public $budgetItems = [];
    public $fundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterBudgetItem' => ['except' => ''],
        'filterSource' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'budget_item_id' => 'required|exists:budget_items,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'bankAccountLines' => 'required|array|min:1',
            'bankAccountLines.*.bank_id' => 'required|exists:banks,id',
            'bankAccountLines.*.bank_account_id' => 'required|exists:bank_accounts,id',
            'bankAccountLines.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    protected $messages = [
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'funding_source_id.required' => 'Debe seleccionar una fuente de financiación.',
        'name.required' => 'El nombre es obligatorio.',
        'amount.required' => 'El monto es obligatorio.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'date.required' => 'La fecha es obligatoria.',
        'bankAccountLines.required' => 'Debe agregar al menos una cuenta bancaria.',
        'bankAccountLines.min' => 'Debe agregar al menos una cuenta bancaria.',
        'bankAccountLines.*.bank_id.required' => 'Debe seleccionar un banco.',
        'bankAccountLines.*.bank_account_id.required' => 'Debe seleccionar una cuenta.',
        'bankAccountLines.*.amount.required' => 'El monto de la línea es obligatorio.',
        'bankAccountLines.*.amount.min' => 'El monto debe ser mayor a 0.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('incomes.view'), 403);
        
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

        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->date = date('Y-m-d');
        $this->batchDate = date('Y-m-d');
        $this->loadBudgetItems();
        $this->loadAvailableBanks();
    }

    public function loadAvailableBanks()
    {
        $this->availableBanks = Bank::forSchool($this->schoolId)
            ->active()
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function addBankAccountLine()
    {
        $this->bankAccountLines[] = [
            'bank_id' => '',
            'bank_account_id' => '',
            'amount' => '',
        ];
    }

    public function removeBankAccountLine($index)
    {
        unset($this->bankAccountLines[$index]);
        unset($this->lineAccounts[$index]);
        $this->bankAccountLines = array_values($this->bankAccountLines);
        $this->lineAccounts = array_values($this->lineAccounts);
    }

    public function updatedBankAccountLines($value, $key)
    {
        // key format: "0.bank_id" or "0.bank_account_id" or "0.amount"
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'bank_id') {
            $index = (int) $parts[0];
            $bankId = $value;
            // Reset account for this line
            $this->bankAccountLines[$index]['bank_account_id'] = '';
            // Load accounts for this bank
            if ($bankId) {
                $this->lineAccounts[$index] = \App\Models\BankAccount::where('bank_id', $bankId)
                    ->active()
                    ->orderBy('account_number')
                    ->get()
                    ->toArray();
            } else {
                $this->lineAccounts[$index] = [];
            }
        }

        // Recalcular monto total si cambian los montos de líneas
        if (count($parts) === 2 && $parts[1] === 'amount') {
            $this->recalculateAmountFromLines();
        }
    }

    public function updatedBatchIncomeLines($value, $key)
    {
        $parts = explode('.', $key);

        if (count($parts) !== 2) {
            return;
        }

        $index = (int) $parts[0];
        $field = $parts[1];

        if (!isset($this->batchIncomeLines[$index])) {
            return;
        }

        if ($field === 'selected' && !$value) {
            $this->batchIncomeLines[$index]['amount'] = '';
            $this->batchIncomeLines[$index]['bank_id'] = '';
            $this->batchIncomeLines[$index]['bank_account_id'] = '';
            $this->batchLineAccounts[$index] = [];
            return;
        }

        if ($field === 'bank_id') {
            $this->batchIncomeLines[$index]['bank_account_id'] = '';

            if ($value) {
                $this->batchLineAccounts[$index] = \App\Models\BankAccount::where('bank_id', $value)
                    ->active()
                    ->orderBy('account_number')
                    ->get()
                    ->toArray();
            } else {
                $this->batchLineAccounts[$index] = [];
            }
        }
    }

    public function recalculateAmountFromLines()
    {
        $total = 0;
        foreach ($this->bankAccountLines as $line) {
            $total += (float) ($line['amount'] ?? 0);
        }
        $this->amount = $total > 0 ? $total : '';
        $this->calculateExceeds();
    }

    public function loadBudgetItems()
    {
        $this->budgetItems = BudgetItem::active()
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => "{$item->code} - {$item->name}",
            ])
            ->toArray();
    }

    public function updatedBudgetItemId($value)
    {
        $this->funding_source_id = '';
        $this->selectedBudgetInfo = null;
        $this->resetExceedsInfo();
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
            ->map(function($source) {
                $budget = $source->budgets()
                    ->where('school_id', $this->schoolId)
                    ->where('type', 'income')
                    ->where('fiscal_year', $this->filterYear)
                    ->first();
                
                $budgeted = $budget ? $budget->current_amount : 0;
                $collected = $source->incomes()->where('school_id', $this->schoolId)->whereYear('date', $this->filterYear)->sum('amount');
                $pending = $budgeted - $collected;
                
                return [
                    'id' => $source->id,
                    'name' => "{$source->code} - {$source->name}",
                    'budgeted' => $budgeted,
                    'collected' => $collected,
                    'pending' => $pending,
                ];
            })
            ->toArray();
    }

    public function updatedFundingSourceId($value)
    {
        $this->selectedBudgetInfo = null;
        $this->resetExceedsInfo();
        
        if (empty($value)) return;
        
        $fundingSource = FundingSource::find($value);
        if (!$fundingSource) return;
        
        $budget = Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('income')
            ->where('funding_source_id', $value)
            ->first();
        
        if ($budget) {
            $collected = Income::forSchool($this->schoolId)
                ->where('funding_source_id', $value)
                ->whereYear('date', $this->filterYear)
                ->sum('amount');
            
            if ($this->isEditing && $this->incomeId) {
                $currentIncome = Income::find($this->incomeId);
                if ($currentIncome && $currentIncome->funding_source_id == $value) {
                    $collected -= $currentIncome->amount;
                }
            }
            
            $this->selectedBudgetInfo = [
                'budget_id' => $budget->id,
                'budgeted' => (float) $budget->current_amount,
                'collected' => (float) $collected,
                'pending' => (float) $budget->current_amount - (float) $collected,
            ];
            
            if (empty($this->name)) {
                $this->name = "Recaudo {$fundingSource->name}";
            }
        }
    }

    public function updatedAmount($value)
    {
        $this->calculateExceeds();
    }

    public function calculateExceeds()
    {
        $this->resetExceedsInfo();
        
        if (!$this->selectedBudgetInfo || empty($this->amount)) return;
        
        $amount = (float) $this->amount;
        $pending = $this->selectedBudgetInfo['pending'];
        
        // Si el monto supera lo pendiente, calcular el exceso
        if ($amount > $pending && $pending >= 0) {
            $this->exceedsAmount = $amount - $pending;
            $this->showExceedsWarning = true;
        } elseif ($pending < 0) {
            // Ya hay exceso previo
            $this->exceedsAmount = $amount;
            $this->showExceedsWarning = true;
        }
    }

    public function resetExceedsInfo()
    {
        $this->exceedsAmount = 0;
        $this->showExceedsWarning = false;
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterYear() { 
        $this->resetPage(); 
        $this->loadBudgetItems();
        if ($this->showBatchModal) {
            $this->prepareBatchIncomeLines();
            if (!empty($this->budget_item_id)) {
                $this->loadFundingSourcesForItem($this->budget_item_id);
                if (!empty($this->funding_source_id)) {
                    $this->updatedFundingSourceId($this->funding_source_id);
                }
            } else {
                $this->fundingSources = [];
                $this->selectedBudgetInfo = null;
            }
        }
    }

    /**
     * Obtener presupuestos de ingreso con su estado de recaudo
     */
    public function getPendingBudgetsProperty()
    {
        return Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('income')
            ->with(['budgetItem', 'fundingSource'])
            ->get()
            ->map(function($budget) {
                $collected = Income::forSchool($this->schoolId)
                    ->where('funding_source_id', $budget->funding_source_id)
                    ->whereYear('date', $this->filterYear)
                    ->sum('amount');
                
                $budgeted = (float) $budget->current_amount;
                $pending = $budgeted - $collected;
                $percentage = $budgeted > 0 ? round(($collected / $budgeted) * 100, 1) : 0;
                
                // Calcular si hay adición pendiente (recaudado > presupuestado)
                $pendingAddition = $collected > $budgeted ? $collected - $budgeted : 0;
                
                // Determinar estado
                if ($collected == 0) {
                    $status = 'pending';
                    $statusLabel = 'Pendiente';
                    $statusColor = 'bg-yellow-100 text-yellow-700';
                } elseif ($pending > 0.01) {
                    $status = 'partial';
                    $statusLabel = 'Parcial';
                    $statusColor = 'bg-blue-100 text-blue-700';
                } elseif (abs($pending) < 0.01) {
                    $status = 'completed';
                    $statusLabel = 'Completo';
                    $statusColor = 'bg-green-100 text-green-700';
                } else {
                    // Recaudado > Presupuestado = Adición pendiente
                    $status = 'exceeded';
                    $statusLabel = 'Adición Pendiente';
                    $statusColor = 'bg-orange-100 text-orange-700';
                }
                
                return [
                    'id' => $budget->id,
                    'budget_item' => $budget->budgetItem,
                    'funding_source' => $budget->fundingSource,
                    'budgeted' => $budgeted,
                    'collected' => (float) $collected,
                    'pending' => (float) $pending,
                    'pending_addition' => $pendingAddition,
                    'percentage' => $percentage,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'status_color' => $statusColor,
                ];
            })
            ->when($this->filterStatus, function($collection) {
                return $collection->filter(fn($item) => $item['status'] === $this->filterStatus);
            })
            ->when($this->filterBudgetItem, function($collection) {
                return $collection->filter(fn($item) => $item['budget_item']->id == $this->filterBudgetItem);
            })
            ->sortBy([
                ['status', 'asc'],
                ['budget_item.code', 'asc'],
            ])
            ->values();
    }

    public function getIncomesProperty()
    {
        return Income::forSchool($this->schoolId)
            ->with(['fundingSource.budgetItem', 'creator', 'bankAccounts.bank', 'bankAccounts.bankAccount'])
            ->whereYear('date', $this->filterYear)
            ->when($this->filterBudgetItem, fn($q) => $q->whereHas('fundingSource', fn($sub) => $sub->where('budget_item_id', $this->filterBudgetItem)))
            ->when($this->filterSource, fn($q) => $q->where('funding_source_id', $this->filterSource))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);
    }

    public function getSummaryProperty()
    {
        $totalBudgeted = Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('income')
            ->sum('current_amount');

        $totalExecuted = Income::forSchool($this->schoolId)
            ->whereYear('date', $this->filterYear)
            ->sum('amount');

        $percentage = $totalBudgeted > 0 ? ($totalExecuted / $totalBudgeted) * 100 : 0;

        $pendingBudgets = $this->pendingBudgets;
        $countPending = $pendingBudgets->where('status', 'pending')->count();
        $countPartial = $pendingBudgets->where('status', 'partial')->count();
        $countCompleted = $pendingBudgets->where('status', 'completed')->count();
        $countExceeded = $pendingBudgets->where('status', 'exceeded')->count();
        $totalPendingAddition = $pendingBudgets->sum('pending_addition');

        return [
            'budgeted' => $totalBudgeted,
            'executed' => $totalExecuted,
            'percentage' => $percentage,
            'pending' => max(0, $totalBudgeted - $totalExecuted),
            'count_pending' => $countPending,
            'count_partial' => $countPartial,
            'count_completed' => $countCompleted,
            'count_exceeded' => $countExceeded,
            'total_pending_addition' => $totalPendingAddition,
        ];
    }

    public function getAvailableYearsProperty()
    {
        $years = Budget::forSchool($this->schoolId)
            ->byType('income')
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();
        
        $currentValidity = \App\Models\School::find($this->schoolId)?->current_validity ?? (int) date('Y');
        if (!in_array($currentValidity, $years)) {
            $years[] = $currentValidity;
        }
        
        rsort($years);
        return $years;
    }

    public function registerIncomeFor($budgetId)
    {
        if (!auth()->user()->can('incomes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear ingresos.', type: 'error');
            return;
        }

        $this->openBatchIncomeModal($budgetId);
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('incomes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear ingresos.', type: 'error');
            return;
        }
        $this->openBatchIncomeModal();
    }

    public function edit($id)
    {
        if (!auth()->user()->can('incomes.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar ingresos.', type: 'error');
            return;
        }

        $income = Income::forSchool($this->schoolId)->with(['fundingSource', 'bankAccounts'])->findOrFail($id);
        
        $this->incomeId = $income->id;
        $this->budget_item_id = $income->fundingSource->budget_item_id;
        $this->loadFundingSourcesForItem($income->fundingSource->budget_item_id);
        $this->funding_source_id = $income->funding_source_id;
        $this->name = $income->name;
        $this->description = $income->description;
        $this->amount = $income->amount;
        $this->date = $income->date->format('Y-m-d');

        // Cargar líneas de cuentas bancarias
        $this->bankAccountLines = [];
        $this->lineAccounts = [];
        foreach ($income->bankAccounts as $i => $ba) {
            $this->bankAccountLines[] = [
                'bank_id' => (string) $ba->bank_id,
                'bank_account_id' => (string) $ba->bank_account_id,
                'amount' => $ba->amount,
            ];
            $this->lineAccounts[$i] = \App\Models\BankAccount::where('bank_id', $ba->bank_id)
                ->active()
                ->orderBy('account_number')
                ->get()
                ->toArray();
        }
        if (empty($this->bankAccountLines)) {
            $this->addBankAccountLine();
        }

        $this->isEditing = true;
        $this->updatedFundingSourceId($income->funding_source_id);
        $this->calculateExceeds();
        
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'incomes.edit' : 'incomes.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        // Validar que la suma de líneas coincida con el monto total
        $linesTotal = collect($this->bankAccountLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
        if (abs($linesTotal - (float) $this->amount) > 0.01) {
            $this->dispatch('toast', message: 'La suma de las líneas bancarias ($' . number_format($linesTotal, 2, ',', '.') . ') no coincide con el monto total ($' . number_format($this->amount, 2, ',', '.') . ').', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $data = [
                'school_id' => $this->schoolId,
                'funding_source_id' => $this->funding_source_id,
                'name' => $this->name,
                'description' => $this->description,
                'amount' => $this->amount,
                'date' => $this->date,
            ];

            if ($this->isEditing) {
                $income = Income::forSchool($this->schoolId)->findOrFail($this->incomeId);
                $income->update($data);
                // Reemplazar líneas bancarias
                $income->bankAccounts()->delete();
                $message = 'Ingreso actualizado exitosamente.';
            } else {
                $data['created_by'] = auth()->id();
                $income = Income::create($data);
                $message = 'Ingreso registrado exitosamente.';
            }

            // Crear líneas bancarias
            foreach ($this->bankAccountLines as $line) {
                IncomeBankAccount::create([
                    'income_id' => $income->id,
                    'bank_id' => $line['bank_id'],
                    'bank_account_id' => $line['bank_account_id'],
                    'amount' => $line['amount'],
                ]);
            }

            // NO crear adición automática - solo informar si hay exceso
            if ($this->showExceedsWarning && $this->exceedsAmount > 0) {
                $message .= ' ⚠️ El recaudo excede el presupuesto. Recuerde aplicar la adición cuando sea aprobada.';
            }

            DB::commit();
            $this->dispatch('toast', message: $message, type: 'success');
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al guardar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openBatchIncomeModal(?int $preferredBudgetId = null)
    {
        $this->resetBatchForm();
        $this->prepareBatchIncomeLines();

        if ($preferredBudgetId) {
            $budget = Budget::forSchool($this->schoolId)
                ->with(['budgetItem', 'fundingSource'])
                ->find($preferredBudgetId);

            if ($budget) {
                $this->budget_item_id = $budget->budget_item_id;
                $this->loadFundingSourcesForItem($budget->budget_item_id);
                $this->funding_source_id = $budget->funding_source_id;
                $this->updatedFundingSourceId($budget->funding_source_id);
            }
        }

        $this->showBatchModal = true;
    }

    public function closeBatchModal()
    {
        $this->showBatchModal = false;
        $this->resetBatchForm();
    }

    public function prepareBatchIncomeLines(): void
    {
        $school = School::find($this->schoolId);

        if (!$school) {
            $this->batchIncomeLines = [];
            return;
        }

        $this->batchIncomeLines = IncomeRegistrationCatalog::buildForSchool($school);
        $this->batchLineAccounts = array_fill(0, count($this->batchIncomeLines), []);
    }

    public function saveBatchIncomes()
    {
        if (!auth()->user()->can('incomes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear ingresos.', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->validate([
            'budget_item_id' => 'required|exists:budget_items,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'batchDate' => 'required|date',
            'batchDescription' => 'nullable|string',
        ]);

        $validGeneralSource = FundingSource::where('id', $this->funding_source_id)
            ->where('budget_item_id', $this->budget_item_id)
            ->exists();

        if (!$validGeneralSource) {
            $this->addError('funding_source_id', 'La fuente seleccionada no pertenece al rubro elegido.');
            return;
        }

        $selectedLines = collect($this->batchIncomeLines)
            ->filter(fn (array $line) => !empty($line['selected']))
            ->values();

        if ($selectedLines->isEmpty()) {
            $this->addError('batchIncomeLines', 'Debe seleccionar al menos un ingreso.');
            return;
        }

        foreach ($this->batchIncomeLines as $index => $line) {
            if (empty($line['selected'])) {
                continue;
            }

            if (!is_numeric($line['amount'] ?? null) || (float) $line['amount'] <= 0) {
                $this->addError("batchIncomeLines.{$index}.amount", 'Debe ingresar un valor mayor a 0.');
            }

            if (empty($line['bank_id'])) {
                $this->addError("batchIncomeLines.{$index}.bank_id", 'Debe seleccionar un banco.');
            }

            if (empty($line['bank_account_id'])) {
                $this->addError("batchIncomeLines.{$index}.bank_account_id", 'Debe seleccionar una cuenta.');
            }

            if (!empty($line['bank_id']) && !empty($line['bank_account_id'])) {
                $validAccount = \App\Models\BankAccount::where('id', $line['bank_account_id'])
                    ->where('bank_id', $line['bank_id'])
                    ->exists();

                if (!$validAccount) {
                    $this->addError("batchIncomeLines.{$index}.bank_account_id", 'La cuenta seleccionada no pertenece al banco elegido.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $warningMessages = [];

        $budget = Budget::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->byType('income')
            ->with(['budgetItem', 'fundingSource'])
            ->where('funding_source_id', $this->funding_source_id)
            ->first();

        if ($budget) {
            $selectedAmount = (float) $selectedLines->sum(fn ($line) => (float) $line['amount']);
            $collected = Income::forSchool($this->schoolId)
                ->where('funding_source_id', $this->funding_source_id)
                ->whereYear('date', $this->filterYear)
                ->sum('amount');

            $pending = (float) $budget->current_amount - (float) $collected;

            if ($selectedAmount > $pending && $pending >= 0) {
                $warningMessages[] = ($budget->fundingSource?->name ?? 'la fuente seleccionada')
                    . ' excede el presupuesto en $'
                    . number_format($selectedAmount - $pending, 2, ',', '.');
            } elseif ($pending < 0) {
                $warningMessages[] = ($budget->fundingSource?->name ?? 'la fuente seleccionada')
                    . ' ya tenía un exceso previo y seguirá pendiente una adición.';
            }
        }

        DB::beginTransaction();

        try {
            foreach ($selectedLines as $line) {
                $income = Income::create([
                    'school_id' => $this->schoolId,
                    'funding_source_id' => $this->funding_source_id,
                    'name' => $line['accounting_code'] . ' - ' . $line['label'],
                    'description' => $this->batchDescription ?: null,
                    'amount' => $line['amount'],
                    'date' => $this->batchDate,
                    'created_by' => auth()->id(),
                ]);

                IncomeBankAccount::create([
                    'income_id' => $income->id,
                    'bank_id' => $line['bank_id'],
                    'bank_account_id' => $line['bank_account_id'],
                    'amount' => $line['amount'],
                ]);
            }

            DB::commit();

            $message = 'Ingresos registrados exitosamente. Se crearon '
                . $selectedLines->count()
                . ' movimientos por $'
                . number_format((float) $selectedLines->sum(fn ($line) => (float) $line['amount']), 2, ',', '.')
                . '.';

            if (!empty($warningMessages)) {
                $message .= ' ⚠️ ' . implode(' ', $warningMessages);
            }

            $this->dispatch('toast', message: $message, type: 'success');
            $this->closeBatchModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al guardar: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Abrir modal para aplicar adición pendiente
     */
    public function openAdditionModal($budgetId)
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($budgetId);

        $collected = Income::forSchool($this->schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->whereYear('date', $budget->fiscal_year)
            ->sum('amount');

        $pendingAddition = $collected - $budget->current_amount;

        if ($pendingAddition <= 0) {
            $this->dispatch('toast', message: 'No hay adición pendiente para este presupuesto.', type: 'info');
            return;
        }

        $this->budgetForAddition = [
            'id' => $budget->id,
            'budget_item_code' => $budget->budgetItem->code,
            'budget_item_name' => $budget->budgetItem->name,
            'funding_source_code' => $budget->fundingSource->code,
            'funding_source_name' => $budget->fundingSource->name,
            'current_budgeted' => (float) $budget->current_amount,
            'collected' => (float) $collected,
            'pending_addition' => $pendingAddition,
        ];

        $this->additionReason = '';
        $this->showAdditionModal = true;
    }

    /**
     * Aplicar la adición pendiente al presupuesto
     */
    public function applyAddition()
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $this->validate([
            'additionReason' => 'required|string|min:10',
        ], [
            'additionReason.required' => 'Debe ingresar una justificación para la adición.',
            'additionReason.min' => 'La justificación debe tener al menos 10 caracteres.',
        ]);

        if (!$this->budgetForAddition) {
            return;
        }

        DB::beginTransaction();
        try {
            $budget = Budget::forSchool($this->schoolId)->findOrFail($this->budgetForAddition['id']);
            $additionAmount = $this->budgetForAddition['pending_addition'];

            $previousAmount = $budget->current_amount;
            $newAmount = $previousAmount + $additionAmount;

            BudgetModification::create([
                'budget_id' => $budget->id,
                'modification_number' => $budget->getNextModificationNumber(),
                'type' => 'addition',
                'amount' => $additionAmount,
                'previous_amount' => $previousAmount,
                'new_amount' => $newAmount,
                'reason' => $this->additionReason,
                'document_number' => null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $budget->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', 
                message: "Adición de $" . number_format($additionAmount, 2, ',', '.') . " aplicada exitosamente.", 
                type: 'success'
            );

            $this->closeAdditionModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al procesar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeAdditionModal()
    {
        $this->showAdditionModal = false;
        $this->budgetForAddition = null;
        $this->additionReason = '';
    }

    public function openMonthlyReceiptModal()
    {
        $this->monthlyReceiptMonth = (string) now()->month;
        $this->showMonthlyReceiptModal = true;
    }

    public function closeMonthlyReceiptModal()
    {
        $this->showMonthlyReceiptModal = false;
        $this->monthlyReceiptMonth = '';
    }

    public function printMonthlyReceipt()
    {
        $this->validate([
            'monthlyReceiptMonth' => 'required|integer|min:1|max:12',
        ], [
            'monthlyReceiptMonth.required' => 'Seleccione un mes.',
            'monthlyReceiptMonth.integer' => 'Mes inválido.',
            'monthlyReceiptMonth.min' => 'Mes inválido.',
            'monthlyReceiptMonth.max' => 'Mes inválido.',
        ]);

        $year = (int) $this->filterYear;
        $month = (int) $this->monthlyReceiptMonth;

        $incomes = Income::forSchool($this->schoolId)
            ->whereYear('date', $year)
            ->with([
                'fundingSource.budgetItem',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date')
            ->get();

        $exists = $incomes
            ->filter(fn ($income) => (int) $income->date?->format('n') === $month)
            ->isNotEmpty();

        if (!$exists) {
            $this->dispatch('toast', message: 'No hay ingresos registrados para ese mes en la vigencia seleccionada.', type: 'info');
            return;
        }

        $accountingAccounts = MonthlyIncomeReceiptBuilder::collectRelevantAccountingAccounts($incomes);

        $builder = new MonthlyIncomeReceiptBuilder();
        $errors = $builder->validate($incomes, $accountingAccounts, $year, $month);

        if (!empty($errors)) {
            $this->dispatch(
                'toast',
                message: implode(' | ', array_slice($errors, 0, 3)),
                type: 'error'
            );
            return;
        }

        $url = route('incomes.monthly.pdf', [
            'year' => $year,
            'month' => $month,
        ]);

        $this->dispatch('openPdfWindow', url: $url);
        $this->closeMonthlyReceiptModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('incomes.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar ingresos.', type: 'error');
            return;
        }
        $this->itemToDelete = Income::forSchool($this->schoolId)->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('incomes.delete')) return;

        if ($this->itemToDelete) {
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: 'Ingreso eliminado exitosamente.', type: 'success');
        }
        $this->closeDeleteModal();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    /**
     * Mostrar modal para marcar presupuesto como completado (reducción)
     */
    public function confirmComplete($budgetId)
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($budgetId);

        $collected = Income::forSchool($this->schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->sum('amount');

        $pending = $budget->current_amount - $collected;

        if ($pending <= 0) {
            $this->dispatch('toast', message: 'Este presupuesto ya está completo o excedido.', type: 'info');
            return;
        }

        $this->budgetToComplete = [
            'id' => $budget->id,
            'budget_item_code' => $budget->budgetItem->code,
            'budget_item_name' => $budget->budgetItem->name,
            'funding_source_code' => $budget->fundingSource->code,
            'funding_source_name' => $budget->fundingSource->name,
            'budgeted' => (float) $budget->current_amount,
            'collected' => (float) $collected,
            'pending' => (float) $pending,
        ];

        $this->showCompleteModal = true;
    }

    public function markAsComplete()
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        if (!$this->budgetToComplete) {
            return;
        }

        DB::beginTransaction();
        try {
            $budget = Budget::forSchool($this->schoolId)->findOrFail($this->budgetToComplete['id']);
            $pendingAmount = $this->budgetToComplete['pending'];

            $previousAmount = $budget->current_amount;
            $newAmount = $previousAmount - $pendingAmount;

            BudgetModification::create([
                'budget_id' => $budget->id,
                'modification_number' => $budget->getNextModificationNumber(),
                'type' => 'reduction',
                'amount' => $pendingAmount,
                'previous_amount' => $previousAmount,
                'new_amount' => $newAmount,
                'reason' => "Ajuste por cierre de recaudo. El ingreso real fue menor al presupuestado. " .
                           "Presupuestado: $" . number_format($previousAmount, 2, ',', '.') . " - " .
                           "Recaudado: $" . number_format($this->budgetToComplete['collected'], 2, ',', '.'),
                'document_number' => null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $budget->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', 
                message: "Presupuesto cerrado. Se aplicó una reducción de $" . number_format($pendingAmount, 2, ',', '.'), 
                type: 'success'
            );

            $this->closeCompleteModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al procesar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->budgetToComplete = null;
    }

    public function resetForm()
    {
        $this->incomeId = null;
        $this->budget_item_id = '';
        $this->funding_source_id = '';
        $this->name = '';
        $this->description = '';
        $this->amount = '';
        $this->date = date('Y-m-d');
        $this->bankAccountLines = [];
        $this->lineAccounts = [];
        $this->addBankAccountLine();
        $this->fundingSources = [];
        $this->selectedBudgetInfo = null;
        $this->isEditing = false;
        $this->resetExceedsInfo();
        $this->resetValidation();
    }

    public function resetBatchForm()
    {
        $this->budget_item_id = '';
        $this->funding_source_id = '';
        $this->fundingSources = [];
        $this->selectedBudgetInfo = null;
        $this->resetExceedsInfo();
        $this->batchDate = date('Y-m-d');
        $this->batchDescription = '';
        $this->batchIncomeLines = [];
        $this->batchLineAccounts = [];
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterSource', 'filterBudgetItem', 'filterStatus']);
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->resetPage();
        $this->loadBudgetItems();
        if ($this->showBatchModal) {
            $this->prepareBatchIncomeLines();
        }
    }

    public function getBatchSelectedCountProperty()
    {
        return collect($this->batchIncomeLines)->where('selected', true)->count();
    }

    public function getBatchSelectedTotalProperty()
    {
        return (float) collect($this->batchIncomeLines)
            ->where('selected', true)
            ->sum(fn ($line) => (float) ($line['amount'] ?? 0));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-management');
    }
}
