<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Bank;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\FundingSource;
use App\Models\Income;
use App\Models\IncomeBankAccount;
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

        $this->filterYear = date('Y');
        $this->date = date('Y-m-d');
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
            ->whereHas('budgets', function($q) {
                $q->where('type', 'income')
                  ->where('fiscal_year', $this->filterYear);
            })
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
            ->whereHas('budgets', function($q) {
                $q->where('type', 'income')
                  ->where('fiscal_year', $this->filterYear);
            })
            ->orderBy('code')
            ->get()
            ->map(function($source) {
                $budget = $source->budgets()
                    ->where('type', 'income')
                    ->where('fiscal_year', $this->filterYear)
                    ->first();
                
                $budgeted = $budget ? $budget->current_amount : 0;
                $collected = $source->incomes()->whereYear('date', $this->filterYear)->sum('amount');
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
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
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
            ->forYear($this->filterYear)
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
        $budgetYears = Budget::forSchool($this->schoolId)
            ->byType('income')
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();
        
        $incomeYears = Income::forSchool($this->schoolId)
            ->distinct()
            ->selectRaw('YEAR(date) as year')
            ->pluck('year')
            ->toArray();
        
        $years = array_unique(array_merge($budgetYears, $incomeYears));
        
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
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

        $budget = Budget::forSchool($this->schoolId)->with(['budgetItem', 'fundingSource'])->findOrFail($budgetId);
        
        $this->resetForm();
        $this->budget_item_id = $budget->budget_item_id;
        $this->loadFundingSourcesForItem($budget->budget_item_id);
        $this->funding_source_id = $budget->funding_source_id;
        $this->updatedFundingSourceId($budget->funding_source_id);
        
        $this->showModal = true;
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('incomes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear ingresos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->loadBudgetItems();
        $this->showModal = true;
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
            $this->dispatch('toast', message: 'La suma de las líneas bancarias ($' . number_format($linesTotal, 0, ',', '.') . ') no coincide con el monto total ($' . number_format($this->amount, 0, ',', '.') . ').', type: 'error');
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
            ->whereYear('date', $this->filterYear)
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
                message: "Adición de $" . number_format($additionAmount, 0, ',', '.') . " aplicada exitosamente.", 
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
            ->whereYear('date', $this->filterYear)
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
                           "Presupuestado: $" . number_format($previousAmount, 0, ',', '.') . " - " .
                           "Recaudado: $" . number_format($this->budgetToComplete['collected'], 0, ',', '.'),
                'document_number' => null,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $budget->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', 
                message: "Presupuesto cerrado. Se aplicó una reducción de $" . number_format($pendingAmount, 0, ',', '.'), 
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

    public function resetFilters()
    {
        $this->reset(['search', 'filterSource', 'filterBudgetItem', 'filterStatus']);
        $this->filterYear = date('Y');
        $this->resetPage();
        $this->loadBudgetItems();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-management');
    }
}
