<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\FundingSource;
use App\Models\Income;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class IncomeManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Filtros
    public $search = '';
    public $filterYear = '';
    public $filterSource = '';
    public $perPage = 15;

    // Modal Crear/Editar
    public $showModal = false;
    public $isEditing = false;
    public $incomeId = null;

    // Formulario
    public $funding_source_id = '';
    public $name = '';
    public $description = '';
    public $amount = '';
    public $date = '';
    public $payment_method = '';
    public $transaction_reference = '';

    // Modal Confirmación Eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Listas para selects
    public $fundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterSource' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
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
        $this->loadFundingSources();
    }

    public function loadFundingSources()
    {
        $this->fundingSources = FundingSource::forSchool($this->schoolId)
            ->active()
            ->with('budgetItem')
            ->orderBy('name')
            ->get();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterYear() { $this->resetPage(); }

    public function getIncomesProperty()
    {
        return Income::forSchool($this->schoolId)
            ->with(['fundingSource.budgetItem', 'creator'])
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
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

        return [
            'budgeted' => $totalBudgeted,
            'executed' => $totalExecuted,
            'percentage' => $percentage,
            'pending' => max(0, $totalBudgeted - $totalExecuted),
        ];
    }

    public function getAvailableYearsProperty()
    {
        $years = Income::forSchool($this->schoolId)
            ->distinct()
            ->selectRaw('YEAR(date) as year')
            ->pluck('year')
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
        if (!auth()->user()->can('incomes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear ingresos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('incomes.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar ingresos.', type: 'error');
            return;
        }

        $income = Income::forSchool($this->schoolId)->findOrFail($id);
        
        $this->incomeId = $income->id;
        $this->funding_source_id = $income->funding_source_id;
        $this->name = $income->name;
        $this->description = $income->description;
        $this->amount = $income->amount;
        $this->date = $income->date->format('Y-m-d');
        $this->payment_method = $income->payment_method;
        $this->transaction_reference = $income->transaction_reference;

        $this->isEditing = true;
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
            $this->dispatch('toast', message: 'Ingreso actualizado exitosamente.', type: 'success');
        } else {
            $data['created_by'] = auth()->id();
            Income::create($data);
            $this->dispatch('toast', message: 'Ingreso registrado exitosamente.', type: 'success');
        }

        $this->closeModal();
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

    public function resetForm()
    {
        $this->incomeId = null;
        $this->funding_source_id = '';
        $this->name = '';
        $this->description = '';
        $this->amount = '';
        $this->date = date('Y-m-d');
        $this->payment_method = '';
        $this->transaction_reference = '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterSource']);
        $this->filterYear = date('Y');
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-management');
    }
}
