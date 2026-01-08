<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\FundingSource;
use App\Models\Income;
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
    public $filterStatus = ''; // all, pending, partial, completed
    public $perPage = 15;

    // Modal Crear/Editar
    public $showModal = false;
    public $isEditing = false;
    public $incomeId = null;

    // Formulario
    public $budget_item_id = '';
    public $funding_source_id = '';
    public $name = '';
    public $description = '';
    public $amount = '';
    public $date = '';
    public $payment_method = '';
    public $transaction_reference = '';

    // Info del presupuesto seleccionado
    public $selectedBudgetInfo = null;
    public $adjustmentType = null; // 'addition', 'reduction', null
    public $adjustmentAmount = 0;
    public $showAdjustmentWarning = false;

    // Modal Confirmación Eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Modal Marcar como Completado
    public $showCompleteModal = false;
    public $budgetToComplete = null;

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
            'payment_method' => 'nullable|string|in:transferencia,efectivo,cheque,consignacion,otro',
            'transaction_reference' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'funding_source_id.required' => 'Debe seleccionar una fuente de financiación.',
        'name.required' => 'El nombre es obligatorio.',
        'amount.required' => 'El monto es obligatorio.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'date.required' => 'La fecha es obligatoria.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('incomes.view'), 403, 'No tienes permisos para ver ingresos.');
        
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
    }

    public function loadBudgetItems()
    {
        // Cargar rubros que tienen presupuesto de ingreso para el año seleccionado
        $this->budgetItems = BudgetItem::forSchool($this->schoolId)
            ->active()
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

    /**
     * Cuando cambia el rubro seleccionado, cargar las fuentes de ese rubro con presupuesto de ingreso
     */
    public function updatedBudgetItemId($value)
    {
        $this->funding_source_id = '';
        $this->selectedBudgetInfo = null;
        $this->resetAdjustmentInfo();
        $this->loadFundingSourcesForItem($value);
    }

    /**
     * Cargar fuentes de financiación para un rubro específico que tengan presupuesto de ingreso
     */
    public function loadFundingSourcesForItem($budgetItemId)
    {
        if (empty($budgetItemId)) {
            $this->fundingSources = [];
            return;
        }

        $this->fundingSources = FundingSource::forSchool($this->schoolId)
            ->forBudgetItem($budgetItemId)
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

    /**
     * Cuando cambia la fuente seleccionada, mostrar info del presupuesto
     */
    public function updatedFundingSourceId($value)
    {
        $this->selectedBudgetInfo = null;
        $this->resetAdjustmentInfo();
        
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
            
            // Si estamos editando, excluir el ingreso actual del total recaudado
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
            
            // Autocompletar nombre con la fuente
            if (empty($this->name)) {
                $this->name = "Recaudo {$fundingSource->name}";
            }
        }
    }

    /**
     * Cuando cambia el monto, calcular si hay ajuste necesario
     */
    public function updatedAmount($value)
    {
        $this->calculateAdjustment();
    }

    /**
     * Calcular si el ingreso generará una adición o reducción
     */
    public function calculateAdjustment()
    {
        $this->resetAdjustmentInfo();
        
        if (!$this->selectedBudgetInfo || empty($this->amount)) return;
        
        $amount = (float) $this->amount;
        $pending = $this->selectedBudgetInfo['pending'];
        
        // Si el monto es mayor al pendiente → se necesita ADICIÓN
        if ($amount > $pending && $pending >= 0) {
            $this->adjustmentType = 'addition';
            $this->adjustmentAmount = $amount - $pending;
            $this->showAdjustmentWarning = true;
        }
        // Si el pendiente es positivo y el monto es menor, verificamos si después quedará pendiente
        // No se requiere ajuste inmediato, solo al final cuando se complete todo
        // Sin embargo, si el usuario ingresa un monto negativo de pendiente (ya recaudó más de lo presupuestado)
        // o si está haciendo un ajuste parcial con monto menor
        elseif ($pending < 0) {
            // Ya se recaudó más de lo presupuestado, el presupuesto ya debería estar ajustado
            $this->showAdjustmentWarning = false;
        }
        else {
            $this->showAdjustmentWarning = false;
        }
    }

    public function resetAdjustmentInfo()
    {
        $this->adjustmentType = null;
        $this->adjustmentAmount = 0;
        $this->showAdjustmentWarning = false;
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
                
                $pending = $budget->current_amount - $collected;
                $percentage = $budget->current_amount > 0 
                    ? round(($collected / $budget->current_amount) * 100, 1) 
                    : 0;
                
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
                    $status = 'exceeded';
                    $statusLabel = 'Excedido';
                    $statusColor = 'bg-purple-100 text-purple-700';
                }
                
                return [
                    'id' => $budget->id,
                    'budget_item' => $budget->budgetItem,
                    'funding_source' => $budget->fundingSource,
                    'budgeted' => (float) $budget->current_amount,
                    'collected' => (float) $collected,
                    'pending' => (float) $pending,
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
                ['status', 'asc'], // Pendientes primero
                ['budget_item.code', 'asc'],
            ])
            ->values();
    }

    public function getIncomesProperty()
    {
        return Income::forSchool($this->schoolId)
            ->with(['fundingSource.budgetItem', 'creator'])
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterBudgetItem, fn($q) => $q->whereHas('fundingSource', fn($sub) => $sub->where('budget_item_id', $this->filterBudgetItem)))
            ->when($this->filterSource, fn($q) => $q->where('funding_source_id', $this->filterSource))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);
    }

    public function getSummaryProperty()
    {
        // 1. Total Ingresos Presupuestados (Rubros tipo Income)
        $totalBudgeted = Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('income')
            ->sum('current_amount');

        // 2. Total Ejecutado (Recaudado)
        $totalExecuted = Income::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->sum('amount');

        $percentage = $totalBudgeted > 0 ? ($totalExecuted / $totalBudgeted) * 100 : 0;

        // 3. Contar estados
        $pendingBudgets = $this->pendingBudgets;
        $countPending = $pendingBudgets->where('status', 'pending')->count();
        $countPartial = $pendingBudgets->where('status', 'partial')->count();
        $countCompleted = $pendingBudgets->where('status', 'completed')->count();

        return [
            'budgeted' => $totalBudgeted,
            'executed' => $totalExecuted,
            'percentage' => $percentage,
            'pending' => max(0, $totalBudgeted - $totalExecuted),
            'count_pending' => $countPending,
            'count_partial' => $countPartial,
            'count_completed' => $countCompleted,
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

    /**
     * Abrir modal desde la tabla de pendientes
     */
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
        
        // Sugerir el monto pendiente
        if ($this->selectedBudgetInfo && $this->selectedBudgetInfo['pending'] > 0) {
            $this->amount = $this->selectedBudgetInfo['pending'];
        }
        
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

        $income = Income::forSchool($this->schoolId)->with('fundingSource')->findOrFail($id);
        
        $this->incomeId = $income->id;
        $this->budget_item_id = $income->fundingSource->budget_item_id;
        
        // Cargar las fuentes del rubro antes de asignar la fuente seleccionada
        $this->loadFundingSourcesForItem($income->fundingSource->budget_item_id);
        
        $this->funding_source_id = $income->funding_source_id;
        $this->name = $income->name;
        $this->description = $income->description;
        $this->amount = $income->amount;
        $this->date = $income->date->format('Y-m-d');
        $this->payment_method = $income->payment_method;
        $this->transaction_reference = $income->transaction_reference;

        $this->isEditing = true;
        
        // Calcular info del presupuesto
        $this->updatedFundingSourceId($income->funding_source_id);
        $this->calculateAdjustment();
        
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

        DB::beginTransaction();
        try {
            $data = [
                'school_id' => $this->schoolId,
                'funding_source_id' => $this->funding_source_id,
                'name' => $this->name,
                'description' => $this->description,
                'amount' => $this->amount,
                'date' => $this->date,
                'payment_method' => $this->payment_method ?: null,
                'transaction_reference' => $this->transaction_reference,
            ];

            if ($this->isEditing) {
                $income = Income::forSchool($this->schoolId)->findOrFail($this->incomeId);
                $income->update($data);
                $message = 'Ingreso actualizado exitosamente.';
            } else {
                $data['created_by'] = auth()->id();
                $income = Income::create($data);
                $message = 'Ingreso registrado exitosamente.';
            }

            // Si hay ajuste necesario, crear la modificación presupuestal
            if ($this->showAdjustmentWarning && $this->adjustmentType && $this->adjustmentAmount > 0 && $this->selectedBudgetInfo) {
                $budget = Budget::find($this->selectedBudgetInfo['budget_id']);
                
                if ($budget) {
                    $previousAmount = $budget->current_amount;
                    $newAmount = $this->adjustmentType === 'addition' 
                        ? $previousAmount + $this->adjustmentAmount
                        : $previousAmount - $this->adjustmentAmount;
                    
                    // Crear modificación presupuestal
                    BudgetModification::create([
                        'budget_id' => $budget->id,
                        'modification_number' => $budget->getNextModificationNumber(),
                        'type' => $this->adjustmentType,
                        'amount' => $this->adjustmentAmount,
                        'previous_amount' => $previousAmount,
                        'new_amount' => $newAmount,
                        'reason' => "Ajuste automático por registro de ingreso real. " . 
                                   ($this->adjustmentType === 'addition' 
                                       ? "Ingreso mayor al presupuestado." 
                                       : "Ingreso menor al presupuestado."),
                        'document_number' => $this->transaction_reference,
                        'document_date' => $this->date,
                        'created_by' => auth()->id(),
                    ]);
                    
                    // Recalcular el presupuesto
                    $budget->recalculateCurrentAmount();
                    
                    $adjustmentTypeName = $this->adjustmentType === 'addition' ? 'adición' : 'reducción';
                    $message .= " Se realizó una {$adjustmentTypeName} de $" . number_format($this->adjustmentAmount, 0, ',', '.') . " al presupuesto.";
                }
            }

            DB::commit();
            $this->dispatch('toast', message: $message, type: 'success');
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al guardar: ' . $e->getMessage(), type: 'error');
        }
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
     * Mostrar modal para marcar presupuesto como completado
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

        // Calcular el monto pendiente
        $collected = Income::forSchool($this->schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->whereYear('date', $this->filterYear)
            ->sum('amount');

        $pending = $budget->current_amount - $collected;

        if ($pending <= 0) {
            $this->dispatch('toast', message: 'Este presupuesto ya está completo.', type: 'info');
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

    /**
     * Marcar presupuesto como completado creando una reducción
     */
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

            // Crear la modificación de reducción
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

            // Recalcular el presupuesto
            $budget->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', 
                message: "Presupuesto marcado como completo. Se aplicó una reducción de $" . number_format($pendingAmount, 0, ',', '.'), 
                type: 'success'
            );

            $this->closeCompleteModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al procesar: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Cerrar modal de completar
     */
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
        $this->payment_method = '';
        $this->transaction_reference = '';
        $this->fundingSources = [];
        $this->selectedBudgetInfo = null;
        $this->isEditing = false;
        $this->resetAdjustmentInfo();
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
