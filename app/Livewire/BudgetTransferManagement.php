<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

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
    public $source_budget_id = '';
    public $destination_budget_id = '';
    public $amount = '';
    public $reason = '';
    public $document_number = '';

    // Datos para selects
    public $sourceBudgets = [];
    public $destinationBudgets = [];
    public $selectedSourceBudget = null;

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
            'destination_budget_id' => 'required|exists:budgets,id|different:source_budget_id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
        ];
    }

    protected $messages = [
        'source_budget_id.required' => 'Debe seleccionar el rubro origen (contracrédito).',
        'destination_budget_id.required' => 'Debe seleccionar el rubro destino (crédito).',
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

        $this->filterYear = date('Y');
        $this->loadSourceBudgets();
    }

    public function loadSourceBudgets()
    {
        // Solo cargar rubros de GASTO con saldo disponible
        $this->sourceBudgets = Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('expense')
            ->active()
            ->where('current_amount', '>', 0)
            ->with('budgetItem')
            ->orderBy('created_at')
            ->get()
            ->map(fn($budget) => [
                'id' => $budget->id,
                'name' => "{$budget->budgetItem->code} - {$budget->budgetItem->name}",
                'current_amount' => $budget->current_amount,
            ])
            ->toArray();
    }

    public function updatedSourceBudgetId($value)
    {
        $this->destination_budget_id = '';
        $this->destinationBudgets = [];
        
        if ($value) {
            $this->selectedSourceBudget = Budget::with('budgetItem')->find($value);
            $this->loadDestinationBudgets();
        } else {
            $this->selectedSourceBudget = null;
        }
    }

    public function loadDestinationBudgets()
    {
        // Cargar todos los rubros de GASTO excepto el origen
        $this->destinationBudgets = Budget::forSchool($this->schoolId)
            ->forYear($this->filterYear)
            ->byType('expense')
            ->active()
            ->where('id', '!=', $this->source_budget_id)
            ->with('budgetItem')
            ->orderBy('created_at')
            ->get()
            ->map(fn($budget) => [
                'id' => $budget->id,
                'name' => "{$budget->budgetItem->code} - {$budget->budgetItem->name}",
                'current_amount' => $budget->current_amount,
            ])
            ->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatedFilterYear()
    {
        $this->loadSourceBudgets();
    }

    public function getTransfersProperty()
    {
        return BudgetTransfer::forSchool($this->schoolId)
            ->with(['sourceBudget.budgetItem', 'destinationBudget.budgetItem', 'creator'])
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
        $this->loadSourceBudgets();
        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        $this->validate();

        // Obtener presupuestos
        $sourceBudget = Budget::forSchool($this->schoolId)->findOrFail($this->source_budget_id);
        $destinationBudget = Budget::forSchool($this->schoolId)->findOrFail($this->destination_budget_id);

        // Validar que el monto no exceda el saldo disponible
        if ($this->amount > $sourceBudget->current_amount) {
            $this->addError('amount', 'El monto no puede ser mayor al saldo disponible ($' . number_format($sourceBudget->current_amount, 2) . ').');
            return;
        }

        // Validar que ambos sean del mismo año fiscal
        if ($sourceBudget->fiscal_year !== $destinationBudget->fiscal_year) {
            $this->addError('destination_budget_id', 'Ambos rubros deben pertenecer al mismo año fiscal.');
            return;
        }

        // Calcular nuevos montos
        $sourceNewAmount = $sourceBudget->current_amount - $this->amount;
        $destinationNewAmount = $destinationBudget->current_amount + $this->amount;

        // Crear el traslado
        BudgetTransfer::create([
            'school_id' => $this->schoolId,
            'transfer_number' => BudgetTransfer::getNextTransferNumber($this->schoolId, $sourceBudget->fiscal_year),
            'source_budget_id' => $sourceBudget->id,
            'destination_budget_id' => $destinationBudget->id,
            'amount' => $this->amount,
            'source_previous_amount' => $sourceBudget->current_amount,
            'source_new_amount' => $sourceNewAmount,
            'destination_previous_amount' => $destinationBudget->current_amount,
            'destination_new_amount' => $destinationNewAmount,
            'reason' => $this->reason,
            'document_number' => $this->document_number ?: null,
            'document_date' => now(),
            'fiscal_year' => $sourceBudget->fiscal_year,
            'created_by' => auth()->id(),
        ]);

        // Actualizar saldos de ambos presupuestos
        $sourceBudget->update(['current_amount' => $sourceNewAmount]);
        $destinationBudget->update(['current_amount' => $destinationNewAmount]);

        $this->dispatch('toast', message: 'Traslado presupuestal registrado exitosamente.', type: 'success');
        $this->closeModal();
        $this->loadSourceBudgets();
    }

    public function showDetail($id)
    {
        $this->detailTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with(['sourceBudget.budgetItem', 'destinationBudget.budgetItem', 'creator'])
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
        $this->destination_budget_id = '';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->selectedSourceBudget = null;
        $this->destinationBudgets = [];
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search']);
        $this->filterYear = date('Y');
        $this->resetPage();
        $this->loadSourceBudgets();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-transfer-management');
    }
}
