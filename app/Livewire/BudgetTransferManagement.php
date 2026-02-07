<?php

namespace App\Livewire;

use App\Models\BudgetItem;
use App\Models\BudgetTransfer;
use App\Models\FundingSource;
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
    
    // Origen
    public $source_budget_item_id = '';
    public $source_funding_source_id = '';
    
    // Destino
    public $destination_budget_item_id = '';
    public $destination_funding_source_id = '';
    
    // Datos del traslado
    public $amount = '';
    public $reason = '';
    public $document_number = '';

    // Datos para selects
    public $budgetItems = [];
    public $sourceFundingSources = [];
    public $destinationFundingSources = [];
    
    // Info seleccionada
    public $selectedSourceFundingSource = null;
    public $selectedDestinationFundingSource = null;

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
            'source_budget_item_id' => 'required|exists:budget_items,id',
            'source_funding_source_id' => 'required|exists:funding_sources,id',
            'destination_budget_item_id' => 'required|exists:budget_items,id',
            'destination_funding_source_id' => 'required|exists:funding_sources,id|different:source_funding_source_id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
        ];
    }

    protected $messages = [
        'source_budget_item_id.required' => 'Debe seleccionar el rubro origen.',
        'source_funding_source_id.required' => 'Debe seleccionar la fuente de financiación origen.',
        'destination_budget_item_id.required' => 'Debe seleccionar el rubro destino.',
        'destination_funding_source_id.required' => 'Debe seleccionar la fuente de financiación destino.',
        'destination_funding_source_id.different' => 'La fuente destino debe ser diferente a la origen.',
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
        $this->loadBudgetItems();
    }

    public function loadBudgetItems()
    {
        // Cargar rubros que tienen fuentes de financiación con saldo
        $this->budgetItems = BudgetItem::whereHas('fundingSources', function($q) {
                $q->active();
            })
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => "{$item->code} - {$item->name}",
            ])
            ->toArray();
    }

    public function updatedSourceBudgetItemId($value)
    {
        $this->source_funding_source_id = '';
        $this->sourceFundingSources = [];
        $this->selectedSourceFundingSource = null;
        
        if ($value) {
            $this->loadSourceFundingSources($value);
        }
    }

    public function loadSourceFundingSources($budgetItemId)
    {
        $sources = FundingSource::where('budget_item_id', $budgetItemId)
            ->active()
            ->with('budgetItem')
            ->get();

        $this->sourceFundingSources = $sources->map(function($source) {
            $balance = $source->getAvailableBalanceForYear($this->filterYear);
            return [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type_name,
                'balance' => $balance,
            ];
        })->filter(fn($s) => $s['balance'] > 0)->values()->toArray();
    }

    public function updatedSourceFundingSourceId($value)
    {
        $this->selectedSourceFundingSource = null;
        
        if ($value) {
            $source = FundingSource::find($value);
            if ($source) {
                $this->selectedSourceFundingSource = [
                    'id' => $source->id,
                    'name' => $source->name,
                    'balance' => $source->getAvailableBalanceForYear($this->filterYear),
                ];
            }
        }
    }

    public function updatedDestinationBudgetItemId($value)
    {
        $this->destination_funding_source_id = '';
        $this->destinationFundingSources = [];
        $this->selectedDestinationFundingSource = null;
        
        if ($value) {
            $this->loadDestinationFundingSources($value);
        }
    }

    public function loadDestinationFundingSources($budgetItemId)
    {
        $sources = FundingSource::where('budget_item_id', $budgetItemId)
            ->active()
            ->with('budgetItem')
            ->get();

        $this->destinationFundingSources = $sources->map(function($source) {
            $balance = $source->getAvailableBalanceForYear($this->filterYear);
            return [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type_name,
                'balance' => $balance,
            ];
        })->values()->toArray();
    }

    public function updatedDestinationFundingSourceId($value)
    {
        $this->selectedDestinationFundingSource = null;
        
        if ($value) {
            $source = FundingSource::find($value);
            if ($source) {
                $this->selectedDestinationFundingSource = [
                    'id' => $source->id,
                    'name' => $source->name,
                    'balance' => $source->getAvailableBalanceForYear($this->filterYear),
                ];
            }
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

    public function updatedFilterYear()
    {
        $this->loadBudgetItems();
    }

    public function getTransfersProperty()
    {
        return BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem', 
                'destinationBudget.budgetItem', 
                'sourceFundingSource',
                'destinationFundingSource',
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
        $this->loadBudgetItems();
        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        $this->validate();

        // Obtener fuentes de financiación
        $sourceFundingSource = FundingSource::findOrFail($this->source_funding_source_id);
        $destinationFundingSource = FundingSource::findOrFail($this->destination_funding_source_id);

        // Calcular saldo disponible de la fuente origen
        $sourceBalance = $sourceFundingSource->getAvailableBalanceForYear($this->filterYear);

        // Validar que el monto no exceda el saldo disponible
        if ($this->amount > $sourceBalance) {
            $this->addError('amount', 'El monto no puede ser mayor al saldo disponible ($' . number_format($sourceBalance, 2) . ').');
            return;
        }

        // Calcular nuevos saldos
        $destinationBalance = $destinationFundingSource->getAvailableBalanceForYear($this->filterYear);
        $sourceNewBalance = $sourceBalance - $this->amount;
        $destinationNewBalance = $destinationBalance + $this->amount;

        // Crear el traslado
        BudgetTransfer::create([
            'school_id' => $this->schoolId,
            'transfer_number' => BudgetTransfer::getNextTransferNumber($this->schoolId, $this->filterYear),
            'source_budget_id' => $sourceFundingSource->budget_item_id,
            'source_funding_source_id' => $sourceFundingSource->id,
            'destination_budget_id' => $destinationFundingSource->budget_item_id,
            'destination_funding_source_id' => $destinationFundingSource->id,
            'amount' => $this->amount,
            'source_previous_amount' => $sourceBalance,
            'source_new_amount' => $sourceNewBalance,
            'destination_previous_amount' => $destinationBalance,
            'destination_new_amount' => $destinationNewBalance,
            'reason' => $this->reason,
            'document_number' => $this->document_number ?: null,
            'document_date' => now(),
            'fiscal_year' => $this->filterYear,
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('toast', message: 'Traslado presupuestal registrado exitosamente.', type: 'success');
        $this->closeModal();
    }

    public function showDetail($id)
    {
        $this->detailTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem', 
                'destinationBudget.budgetItem', 
                'sourceFundingSource.budgetItem',
                'destinationFundingSource.budgetItem',
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
        $this->source_budget_item_id = '';
        $this->source_funding_source_id = '';
        $this->destination_budget_item_id = '';
        $this->destination_funding_source_id = '';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->sourceFundingSources = [];
        $this->destinationFundingSources = [];
        $this->selectedSourceFundingSource = null;
        $this->selectedDestinationFundingSource = null;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search']);
        $this->filterYear = date('Y');
        $this->resetPage();
        $this->loadBudgetItems();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-transfer-management');
    }
}
