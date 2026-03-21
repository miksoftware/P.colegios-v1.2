<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Cdp;
use App\Models\CdpFundingSource;
use App\Models\Contract;
use App\Models\ContractRp;
use App\Models\Convocatoria;
use App\Models\FundingSource;
use App\Models\RpFundingSource;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

class ContractualManagement extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $schoolId;
    public $filterYear;
    public $filterStatus = '';
    public $search = '';

    // Vista principal
    public $currentView = 'list'; // list | create | detail

    // Detalle
    public $contractId = null;
    public $contract = null;

    // ── Formulario de creación ────────────────────────────────
    public $selectedConvocatoriaId = '';
    public $contractNumber = '';
    public $contractingModality = '';
    public $executionPlace = '';
    public $startDate = '';
    public $endDate = '';
    public $durationDays = 0;
    public $contractObject = '';
    public $contractJustification = '';
    public $paymentMethod = 'single';
    public $supervisorId = '';
    public $contractSubtotal = '';
    public $contractIva = '';
    public $contractTotal = '';

    // Datos del proveedor (auto-fill)
    public $supplierData = [];

    // CDPs y asignación de RPs
    public $cdpsData = [];
    public $rpAssignments = []; // array indexed by cdp_id

    // Datos auxiliares
    public $awardedConvocatorias = [];
    public $supervisors = [];
    public $availableBanks = [];
    public $rpLineAccounts = []; // Cuentas bancarias por línea de RP
    public $convocatoriaEndDate = ''; // Fecha fin de la convocatoria seleccionada (para restringir fechas del contrato)

    // Modal Cambio de Estado
    public $showStatusModal = false;
    public $newStatus = '';

    // Modal Eliminar
    public $showDeleteModal = false;

    // Modal Anular
    public $showAnnulModal = false;
    public $annulmentReason = '';

    // Modal Prórroga
    public $showExtensionModal = false;
    public $extensionNewEndDate = '';
    public $extensionDocument = null;

    // Modal Adición de Recursos
    public $showAdditionModal = false;
    public $additionAmount = '';
    public $additionDocument = null;
    public $additionCdpBudgetItemId = '';
    public $additionCdpFundingSources = [];
    public $additionAvailableFundingSources = [];
    public $additionBudgetItems = [];

    protected $queryString = [
        'filterYear'   => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    // ── Mount ────────────────────────────────────────────────
    public function mount($convocatoria_id = null)
    {
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');

        // Si viene del precontractual con convocatoria preseleccionada
        if ($convocatoria_id) {
            $this->openCreateView($convocatoria_id);
        }
    }

    public function updatingSearch()   { $this->resetPage(); }
    public function updatingFilterYear() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    // ══════════════════════════════════════════════════════════
    // LISTADO
    // ══════════════════════════════════════════════════════════

    public function getContractsProperty()
    {
        return Contract::with(['convocatoria', 'supplier', 'supervisor', 'rps'])
            ->forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->byStatus($this->filterStatus))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getSummaryProperty()
    {
        $base = Contract::forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear));

        return [
            'total'        => (clone $base)->count(),
            'draft'        => (clone $base)->byStatus('draft')->count(),
            'active'       => (clone $base)->byStatus('active')->count(),
            'in_execution' => (clone $base)->byStatus('in_execution')->count(),
            'completed'    => (clone $base)->byStatus('completed')->count(),
            'total_value'  => (clone $base)->sum('total'),
        ];
    }

    // ══════════════════════════════════════════════════════════
    // DETALLE
    // ══════════════════════════════════════════════════════════

    public function viewDetail($id)
    {
        // Verificar que el contrato existe y pertenece al colegio
        $exists = Contract::forSchool($this->schoolId)->where('id', $id)->exists();
        if (!$exists) {
            $this->dispatch('toast', message: 'Contrato no encontrado.', type: 'error');
            return;
        }

        $this->contractId = $id;
        $this->currentView = 'detail';
    }

    public function backToList()
    {
        $this->currentView = 'list';
        $this->contract = null;
        $this->contractId = null;
        $this->resetCreateForm();
    }

    // ══════════════════════════════════════════════════════════
    // CREAR CONTRATO
    // ══════════════════════════════════════════════════════════

    public function openCreateView($convocatoriaId = null)
    {
        if (!auth()->user()->can('contractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear contratos.', type: 'error');
            return;
        }

        $this->resetCreateForm();
        $this->loadAuxiliaryData();

        $this->contractNumber = Contract::getNextContractNumber($this->schoolId, (int) $this->filterYear);

        if ($convocatoriaId) {
            $this->selectedConvocatoriaId = $convocatoriaId;
            $this->onConvocatoriaSelected();
        }

        $this->currentView = 'create';
    }

    public function loadAuxiliaryData()
    {
        // Convocatorias adjudicadas que NO tienen contrato aún
        $this->awardedConvocatorias = Convocatoria::with(['selectedProposal.supplier', 'cdps.budgetItem', 'cdps.fundingSources.fundingSource'])
            ->forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->byStatus('awarded')
            ->doesntHave('contract')
            ->orderBy('convocatoria_number')
            ->get()
            ->map(fn($c) => [
                'id'     => $c->id,
                'number' => $c->formatted_number,
                'object' => $c->object,
                'total'  => $c->selectedProposal?->total ?? 0,
            ])
            ->toArray();

        // Supervisores (usuarios del colegio)
        $this->supervisors = User::whereHas('schools', fn($q) => $q->where('schools.id', $this->schoolId))
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();

        // Bancos disponibles
        $this->availableBanks = Bank::forSchool($this->schoolId)
            ->active()
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function onConvocatoriaSelected()
    {
        if (!$this->selectedConvocatoriaId) {
            $this->supplierData = [];
            $this->cdpsData = [];
            $this->rpAssignments = [];
            $this->contractObject = '';
            $this->contractJustification = '';
            $this->contractSubtotal = '';
            $this->contractIva = '';
            $this->contractTotal = '';
            $this->convocatoriaEndDate = '';
            $this->startDate = '';
            $this->endDate = '';
            $this->durationDays = 0;
            return;
        }

        $convocatoria = Convocatoria::with([
            'selectedProposal.supplier.department',
            'selectedProposal.supplier.municipality',
            'cdps.budgetItem',
            'cdps.fundingSources.fundingSource',
        ])->forSchool($this->schoolId)->findOrFail($this->selectedConvocatoriaId);

        // Guardar fecha fin de la convocatoria para restringir fechas del contrato
        $this->convocatoriaEndDate = $convocatoria->end_date->format('Y-m-d');

        // Limpiar fechas previas al cambiar de convocatoria
        $this->startDate = '';
        $this->endDate = '';
        $this->durationDays = 0;

        // Auto-fill objeto y justificación
        $this->contractObject = $convocatoria->object;
        $this->contractJustification = $convocatoria->justification ?? '';

        // Auto-fill proveedor (del ganador)
        $proposal = $convocatoria->selectedProposal;
        if ($proposal && $proposal->supplier) {
            $supplier = $proposal->supplier;
            $this->supplierData = [
                'id'         => $supplier->id,
                'name'       => $supplier->full_name,
                'document'   => $supplier->full_document,
                'address'    => $supplier->address ?? 'No registrada',
                'municipality' => $supplier->city ?? 'No registrado',
                'phone'      => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
                'tax_regime' => $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
            ];

            // Auto-fill valor del contrato
            $this->contractSubtotal = $proposal->subtotal;
            $this->contractIva = $proposal->iva ?? 0;
            $this->contractTotal = $proposal->total;
        }

        // Cargar CDPs activos con sus fuentes (excluir anulados)
        $this->cdpsData = [];
        $this->rpAssignments = [];

        foreach ($convocatoria->cdps->where('status', 'active') as $cdp) {
            $cdpFundingSources = [];
            foreach ($cdp->fundingSources as $fs) {
                $cdpFundingSources[] = [
                    'id'              => $fs->id,
                    'funding_source_id' => $fs->funding_source_id,
                    'name'            => $fs->fundingSource->name ?? 'Sin nombre',
                    'amount'          => (float) $fs->amount,
                    'budget_id'       => $fs->budget_id,
                ];
            }

            $this->cdpsData[] = [
                'id'              => $cdp->id,
                'cdp_number'      => $cdp->formatted_number,
                'budget_item'     => $cdp->budgetItem->name ?? 'N/A',
                'budget_item_code' => $cdp->budgetItem->code ?? '',
                'total_amount'    => (float) $cdp->total_amount,
                'funding_sources' => $cdpFundingSources,
            ];

            // Pre-inicializar la asignación de RP para este CDP
            // El monto del RP es igual al valor de la propuesta, distribuido proporcionalmente entre las fuentes del CDP
            $cdpTotal = (float) $cdp->total_amount;
            $proposalTotal = (float) ($this->contractTotal ?? 0);
            $rpFundingSources = [];
            foreach ($cdpFundingSources as $fs) {
                $proportion = $cdpTotal > 0 ? $fs['amount'] / $cdpTotal : 0;
                $rpAmount = min($fs['amount'], round($proposalTotal * $proportion, 2));
                $rpFundingSources[] = [
                    'funding_source_id' => $fs['funding_source_id'],
                    'name'              => $fs['name'],
                    'available'         => $fs['amount'],
                    'amount'            => $rpAmount,
                    'budget_id'         => $fs['budget_id'],
                    'bank_id'            => '',
                    'bank_account_id'    => '',
                ];
            }

            $this->rpAssignments[$cdp->id] = [
                'rp_number'       => ContractRp::getNextRpNumber($this->schoolId, (int) $this->filterYear),
                'funding_sources' => $rpFundingSources,
            ];
        }
    }

    public function updatedRpAssignments($value, $key)
    {
        // key format: "123.funding_sources.0.bank_id"
        $parts = explode('.', $key);
        if (count($parts) === 4 && $parts[1] === 'funding_sources' && $parts[3] === 'bank_id') {
            $cdpId = $parts[0];
            $fsIndex = (int) $parts[2];
            $bankId = $value;
            // Reset account for this line
            $this->rpAssignments[$cdpId]['funding_sources'][$fsIndex]['bank_account_id'] = '';
            // Load accounts for this bank
            $lineKey = $cdpId . '_' . $fsIndex;
            if ($bankId) {
                $this->rpLineAccounts[$lineKey] = BankAccount::where('bank_id', $bankId)
                    ->active()
                    ->orderBy('account_number')
                    ->get()
                    ->toArray();
            } else {
                $this->rpLineAccounts[$lineKey] = [];
            }
        }
    }

    public function updatedStartDate()
    {
        $this->calculateDuration();
    }

    public function updatedEndDate()
    {
        $this->calculateDuration();
    }

    public function calculateDuration()
    {
        if ($this->startDate && $this->endDate) {
            try {
                $start = \Carbon\Carbon::parse($this->startDate);
                $end   = \Carbon\Carbon::parse($this->endDate);

                if ($end->lte($start)) {
                    $this->durationDays = 0;
                    return;
                }

                // Contar solo días hábiles (lunes a viernes)
                $days = 0;
                $current = $start->copy();
                while ($current->lte($end)) {
                    if ($current->isWeekday()) {
                        $days++;
                    }
                    $current->addDay();
                }

                $this->durationDays = $days;
            } catch (\Exception $e) {
                $this->durationDays = 0;
            }
        } else {
            $this->durationDays = 0;
        }
    }

    public function saveContract()
    {
        if (!auth()->user()->can('contractual.create')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        // Validación
        $this->validate([
            'selectedConvocatoriaId' => 'required|exists:convocatorias,id',
            'contractingModality'    => 'required|in:' . implode(',', array_keys(Contract::MODALITIES)),
            'executionPlace'         => 'nullable|string|max:255',
            'startDate'              => 'required|date|after:convocatoriaEndDate',
            'endDate'                => 'required|date|after_or_equal:startDate',
            'paymentMethod'          => 'required|in:single,partial',
            'contractSubtotal'       => 'required|numeric|min:0',
            'contractIva'            => 'nullable|numeric|min:0',
            'contractTotal'          => 'required|numeric|min:0',
        ], [
            'selectedConvocatoriaId.required' => 'Debe seleccionar una convocatoria.',
            'contractingModality.required'    => 'Debe seleccionar la modalidad de contratación.',
            'startDate.required'              => 'La fecha de inicio es obligatoria.',
            'startDate.after'                 => 'La fecha de inicio debe ser posterior a la fecha fin de la convocatoria (' . ($this->convocatoriaEndDate ? \Carbon\Carbon::parse($this->convocatoriaEndDate)->format('d/m/Y') : '') . ').',
            'endDate.required'                => 'La fecha de terminación es obligatoria.',
            'endDate.after_or_equal'          => 'La fecha de terminación debe ser posterior o igual a la de inicio.',
            'paymentMethod.required'          => 'Debe seleccionar la forma de pago.',
            'contractSubtotal.required'       => 'El subtotal es obligatorio.',
            'contractTotal.required'          => 'El total es obligatorio.',
        ]);

        // Validar que cada RP tiene al menos una fuente con monto > 0
        $totalAllRps = 0;
        foreach ($this->rpAssignments as $cdpId => $rpData) {
            $totalRp = collect($rpData['funding_sources'])->sum(fn($fs) => (float) ($fs['amount'] ?? 0));
            $totalAllRps += $totalRp;
            if ($totalRp <= 0) {
                $this->dispatch('toast', message: 'Cada RP debe tener al menos un monto asignado por fuente de financiación.', type: 'error');
                return;
            }

            // Validar que no exceda el disponible del CDP
            foreach ($rpData['funding_sources'] as $fs) {
                $amount = (float) ($fs['amount'] ?? 0);
                $available = (float) ($fs['available'] ?? 0);
                if ($amount > $available) {
                    $this->dispatch('toast', message: "El monto del RP para la fuente \"{$fs['name']}\" excede el disponible del CDP.", type: 'error');
                    return;
                }
            }
        }

        // Validar que la suma total de RPs sea exactamente igual al valor del contrato
        $contractTotalValue = (float) $this->contractTotal;
        if (abs($totalAllRps - $contractTotalValue) > 0.01) {
            $this->dispatch('toast', message: 'La suma de los RPs ($' . number_format($totalAllRps, 0, ',', '.') . ') debe ser igual al valor del contrato ($' . number_format($contractTotalValue, 0, ',', '.') . '). Ajuste los montos de las fuentes de financiación.', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;

            // Crear contrato
            $contract = Contract::create([
                'school_id'            => $this->schoolId,
                'convocatoria_id'      => $this->selectedConvocatoriaId,
                'contract_number'      => Contract::getNextContractNumber($this->schoolId, $year),
                'fiscal_year'          => $year,
                'contracting_modality' => $this->contractingModality,
                'execution_place'      => $this->executionPlace ?? '',
                'start_date'           => $this->startDate,
                'end_date'             => $this->endDate,
                'duration_days'        => $this->durationDays,
                'object'               => $this->contractObject,
                'justification'        => $this->contractJustification,
                'supplier_id'          => $this->supplierData['id'],
                'supervisor_id'        => $this->supervisorId ?: null,
                'subtotal'             => (float) $this->contractSubtotal,
                'iva'                  => (float) ($this->contractIva ?? 0),
                'total'                => (float) $this->contractTotal,
                'payment_method'       => $this->paymentMethod,
                'status'               => 'draft',
                'created_by'           => auth()->id(),
            ]);

            // Crear RPs para cada CDP
            foreach ($this->rpAssignments as $cdpId => $rpData) {
                $totalRp = collect($rpData['funding_sources'])->sum(fn($fs) => (float) ($fs['amount'] ?? 0));

                $rp = ContractRp::create([
                    'contract_id'  => $contract->id,
                    'cdp_id'       => $cdpId,
                    'rp_number'    => ContractRp::getNextRpNumber($this->schoolId, $year),
                    'fiscal_year'  => $year,
                    'total_amount' => $totalRp,
                    'status'       => 'active',
                    'created_by'   => auth()->id(),
                ]);

                // Crear detalle de fuentes de financiación del RP
                foreach ($rpData['funding_sources'] as $fs) {
                    $amount = (float) ($fs['amount'] ?? 0);
                    if ($amount > 0) {
                        RpFundingSource::create([
                            'contract_rp_id'    => $rp->id,
                            'funding_source_id' => $fs['funding_source_id'],
                            'budget_id'         => $fs['budget_id'] ?? null,
                            'amount'            => $amount,
                            'bank_id'           => $fs['bank_id'] ?: null,
                            'bank_account_id'   => $fs['bank_account_id'] ?: null,
                        ]);
                    }
                }

                // Marcar CDP como utilizado
                Cdp::where('id', $cdpId)->update(['status' => 'used']);
            }

            DB::commit();

            $this->dispatch('toast', message: 'Contrato creado exitosamente.', type: 'success');
            $this->viewDetail($contract->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al crear contrato: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // CAMBIO DE ESTADO
    // ══════════════════════════════════════════════════════════

    public function openStatusModal($status = null)
    {
        if (!auth()->user()->can('contractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->newStatus = $status ?? '';
        $this->showStatusModal = true;
    }

    public function changeStatus()
    {
        if (!$this->contractId || !$this->newStatus) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);

        $allowed = $this->getAllowedStatuses($contract->status);
        if (!in_array($this->newStatus, $allowed)) {
            $this->dispatch('toast', message: 'Cambio de estado no permitido.', type: 'error');
            return;
        }

        $contract->update(['status' => $this->newStatus]);
        $this->showStatusModal = false;
        $this->dispatch('toast', message: 'Estado actualizado a ' . Contract::STATUSES[$this->newStatus] . '.', type: 'success');
        $this->viewDetail($this->contractId);
    }

    public function getAllowedStatuses(string $current): array
    {
        return match ($current) {
            'draft'        => ['active'],
            'active'       => ['in_execution', 'suspended'],
            'in_execution' => ['completed', 'suspended'],
            'suspended'    => ['active', 'in_execution'],
            default        => [],
        };
    }

    // ══════════════════════════════════════════════════════════
    // ELIMINAR CONTRATO
    // ══════════════════════════════════════════════════════════

    public function confirmDelete()
    {
        if (!auth()->user()->can('contractual.delete')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->showDeleteModal = true;
    }

    public function deleteContract()
    {
        if (!$this->contractId) return;

        $contract = Contract::with('rps')->forSchool($this->schoolId)->findOrFail($this->contractId);

        if ($contract->status !== 'draft') {
            $this->dispatch('toast', message: 'Solo se pueden eliminar contratos en estado borrador.', type: 'error');
            $this->showDeleteModal = false;
            return;
        }

        DB::beginTransaction();
        try {
            // Revertir CDPs a estado activo
            foreach ($contract->rps as $rp) {
                Cdp::where('id', $rp->cdp_id)->update(['status' => 'active']);
            }

            $contract->delete();
            DB::commit();

            $this->dispatch('toast', message: 'Contrato eliminado.', type: 'success');
            $this->showDeleteModal = false;
            $this->backToList();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // ANULAR CONTRATO
    // ══════════════════════════════════════════════════════════

    public function openAnnulModal()
    {
        if (!auth()->user()->can('contractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        if (!$this->contractId) return;

        $contract = Contract::with('paymentOrders')->forSchool($this->schoolId)->findOrFail($this->contractId);

        if ($contract->hasPaymentOrders()) {
            $this->dispatch('toast', message: 'No se puede anular un contrato que tiene órdenes de pago asociadas.', type: 'error');
            return;
        }

        if (in_array($contract->status, ['annulled', 'completed'])) {
            $this->dispatch('toast', message: 'Este contrato no se puede anular en su estado actual.', type: 'error');
            return;
        }

        $this->annulmentReason = '';
        $this->showAnnulModal = true;
    }

    public function annulContract()
    {
        if (!$this->contractId) return;

        $this->validate([
            'annulmentReason' => 'required|min:10|max:500',
        ], [
            'annulmentReason.required' => 'La razón de anulación es obligatoria.',
            'annulmentReason.min' => 'La razón debe tener al menos 10 caracteres.',
        ]);

        $contract = Contract::with(['paymentOrders', 'convocatoria.cdps', 'rps'])->forSchool($this->schoolId)->findOrFail($this->contractId);

        if ($contract->hasPaymentOrders()) {
            $this->dispatch('toast', message: 'No se puede anular: tiene órdenes de pago.', type: 'error');
            $this->showAnnulModal = false;
            return;
        }

        DB::transaction(function () use ($contract) {
            $contract->update([
                'status' => 'annulled',
                'annulment_reason' => $this->annulmentReason,
                'annulment_date' => now(),
            ]);

            // Cancelar RPs del contrato
            if ($contract->rps) {
                foreach ($contract->rps as $rp) {
                    $rp->update(['status' => 'cancelled']);
                }
            }

            // Liberar CDPs: revertir de 'used' a 'cancelled' para que los fondos queden disponibles
            if ($contract->convocatoria) {
                $contract->convocatoria->cdps()
                    ->where('status', 'used')
                    ->update(['status' => 'cancelled']);

                // También cancelar CDPs activos que no se usaron
                $contract->convocatoria->cdps()
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                // Revertir convocatoria a cancelada si estaba adjudicada
                if (in_array($contract->convocatoria->status, ['awarded'])) {
                    $contract->convocatoria->update(['status' => 'cancelled']);
                }
            }
        });

        $this->showAnnulModal = false;
        $this->dispatch('toast', message: 'Contrato anulado. Los CDPs y recursos comprometidos fueron liberados.', type: 'success');
        $this->viewDetail($this->contractId);
    }

    // ══════════════════════════════════════════════════════════
    // PRÓRROGA DE TIEMPO
    // ══════════════════════════════════════════════════════════

    public function openExtensionModal()
    {
        if (!auth()->user()->can('contractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        if (!$this->contractId) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);

        if (!in_array($contract->status, ['active', 'in_execution'])) {
            $this->dispatch('toast', message: 'Solo se puede prorrogar contratos activos o en ejecución.', type: 'error');
            return;
        }

        $this->extensionNewEndDate = '';
        $this->extensionDocument = null;
        $this->showExtensionModal = true;
    }

    public function saveExtension()
    {
        if (!$this->contractId) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);

        $this->validate([
            'extensionNewEndDate' => 'required|date|after:' . $contract->end_date->format('Y-m-d'),
            'extensionDocument' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'extensionNewEndDate.required' => 'La nueva fecha de terminación es obligatoria.',
            'extensionNewEndDate.after' => 'La nueva fecha debe ser posterior a la fecha actual de terminación (' . $contract->end_date->format('d/m/Y') . ').',
            'extensionDocument.required' => 'El documento Otrosí es obligatorio.',
            'extensionDocument.mimes' => 'El documento debe ser PDF, DOC o DOCX.',
            'extensionDocument.max' => 'El documento no puede superar 10MB.',
        ]);

        $newEnd = \Carbon\Carbon::parse($this->extensionNewEndDate);
        $oldEnd = $contract->end_date;

        // Calcular días hábiles de extensión
        $extensionDays = 0;
        $current = $oldEnd->copy()->addDay();
        while ($current->lte($newEnd)) {
            if ($current->isWeekday()) {
                $extensionDays++;
            }
            $current->addDay();
        }

        // Recalcular duración total en días hábiles
        $start = $contract->start_date;
        $totalDays = 0;
        $cur = $start->copy();
        while ($cur->lte($newEnd)) {
            if ($cur->isWeekday()) {
                $totalDays++;
            }
            $cur->addDay();
        }

        // Guardar documento
        $path = $this->extensionDocument->store('contracts/extensions', 'public');

        DB::transaction(function () use ($contract, $newEnd, $extensionDays, $totalDays, $path) {
            $contract->update([
                'original_end_date' => $contract->original_end_date ?? $contract->end_date,
                'end_date' => $newEnd,
                'extension_days' => $contract->extension_days + $extensionDays,
                'extension_document_path' => $path,
                'extension_date' => now(),
                'duration_days' => $totalDays,
            ]);
        });

        $this->showExtensionModal = false;
        $this->extensionDocument = null;
        $this->dispatch('toast', message: "Prórroga registrada: +{$extensionDays} días hábiles.", type: 'success');
        $this->viewDetail($this->contractId);
    }

    // ══════════════════════════════════════════════════════════
    // ADICIÓN DE RECURSOS
    // ══════════════════════════════════════════════════════════

    public function openAdditionModal()
    {
        if (!auth()->user()->can('contractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        if (!$this->contractId) return;

        $contract = Contract::with('rps.cdp.budgetItem', 'convocatoria.cdps.budgetItem')
            ->forSchool($this->schoolId)->findOrFail($this->contractId);

        if (!in_array($contract->status, ['active', 'in_execution'])) {
            $this->dispatch('toast', message: 'Solo se puede adicionar recursos a contratos activos o en ejecución.', type: 'error');
            return;
        }

        if ($contract->max_addition <= 0) {
            $this->dispatch('toast', message: 'Este contrato ya alcanzó el máximo de adición permitido (50% del valor inicial).', type: 'error');
            return;
        }

        // Cargar rubros del contrato (de los CDPs de la convocatoria)
        $budgetItemIds = $contract->convocatoria->cdps
            ->where('status', '!=', 'cancelled')
            ->pluck('budget_item_id')
            ->unique();

        $this->additionBudgetItems = \App\Models\BudgetItem::with('accountingAccount')
            ->active()
            ->whereIn('id', $budgetItemIds)
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => "{$item->code} - {$item->name}",
                'accounting_account' => $item->accountingAccount ? "{$item->accountingAccount->code} - {$item->accountingAccount->name}" : 'N/A',
            ])
            ->toArray();

        $this->additionAmount = '';
        $this->additionDocument = null;
        $this->additionCdpBudgetItemId = '';
        $this->additionCdpFundingSources = [];
        $this->additionAvailableFundingSources = [];

        if (count($this->additionBudgetItems) === 1) {
            $this->additionCdpBudgetItemId = $this->additionBudgetItems[0]['id'];
            $this->onAdditionBudgetItemSelected();
        }

        $this->showAdditionModal = true;
    }

    public function onAdditionBudgetItemSelected()
    {
        $this->additionCdpFundingSources = [];
        $this->additionAvailableFundingSources = [];

        if (empty($this->additionCdpBudgetItemId)) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);
        $maxAddition = $contract->max_addition;

        $sources = FundingSource::where('budget_item_id', $this->additionCdpBudgetItemId)
            ->active()
            ->get();

        $this->additionAvailableFundingSources = $sources->map(function ($source) use ($maxAddition) {
            $balance = $source->getAvailableBalanceForYear((int) $this->filterYear, $this->schoolId);
            $reserved = Cdp::getTotalReservedForFundingSource($source->id, (int) $this->filterYear, $this->schoolId);
            $sourceAvailable = max(0, $balance - $reserved);

            // Limitar al máximo de adición permitido para el contrato
            $available = min($sourceAvailable, $maxAddition);

            $budget = Budget::forSchool($this->schoolId)
                ->where('funding_source_id', $source->id)
                ->where('budget_item_id', $this->additionCdpBudgetItemId)
                ->where('fiscal_year', $this->filterYear)
                ->where('type', 'expense')
                ->first();

            return [
                'id' => $source->id,
                'name' => $source->code . ' - ' . $source->name,
                'type' => $source->type_name,
                'available' => $available,
                'budget_id' => $budget?->id,
            ];
        })->filter(fn($s) => $s['available'] > 0)->values()->toArray();
    }

    public function addAdditionFundingSource($sourceId)
    {
        $source = collect($this->additionAvailableFundingSources)->firstWhere('id', $sourceId);
        if (!$source) return;

        if (collect($this->additionCdpFundingSources)->contains('id', $sourceId)) {
            $this->dispatch('toast', message: 'Esta fuente ya fue agregada.', type: 'error');
            return;
        }

        $this->additionCdpFundingSources[] = [
            'id' => $source['id'],
            'name' => $source['name'],
            'available' => $source['available'],
            'budget_id' => $source['budget_id'],
            'amount' => '',
        ];
    }

    public function removeAdditionFundingSource($index)
    {
        unset($this->additionCdpFundingSources[$index]);
        $this->additionCdpFundingSources = array_values($this->additionCdpFundingSources);
    }

    public function saveAddition()
    {
        if (!$this->contractId) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);

        $this->validate([
            'additionDocument' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'additionCdpBudgetItemId' => 'required',
            'additionCdpFundingSources' => 'required|array|min:1',
            'additionCdpFundingSources.*.amount' => 'required|numeric|min:0.01',
        ], [
            'additionDocument.required' => 'El documento Otrosí es obligatorio.',
            'additionDocument.mimes' => 'El documento debe ser PDF, DOC o DOCX.',
            'additionCdpBudgetItemId.required' => 'Seleccione un rubro.',
            'additionCdpFundingSources.required' => 'Agregue al menos una fuente.',
            'additionCdpFundingSources.*.amount.required' => 'Ingrese un monto.',
            'additionCdpFundingSources.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        $totalAddition = collect($this->additionCdpFundingSources)->sum(fn($fs) => (float) ($fs['amount'] ?? 0));

        if ($totalAddition > $contract->max_addition) {
            $this->dispatch('toast', message: 'El monto excede el máximo permitido (50% del valor inicial). Máximo: $' . number_format($contract->max_addition, 0, ',', '.'), type: 'error');
            return;
        }

        // Validar montos vs disponibles
        foreach ($this->additionCdpFundingSources as $i => $fs) {
            if ((float) $fs['amount'] > (float) $fs['available']) {
                $this->addError("additionCdpFundingSources.{$i}.amount", 'Excede el saldo disponible.');
                return;
            }
        }

        $path = $this->additionDocument->store('contracts/additions', 'public');
        $year = (int) $this->filterYear;

        DB::beginTransaction();
        try {
            // Crear CDP de adición
            $cdp = Cdp::create([
                'school_id' => $this->schoolId,
                'convocatoria_id' => $contract->convocatoria_id,
                'cdp_number' => Cdp::getNextCdpNumber($this->schoolId, $year),
                'fiscal_year' => $year,
                'budget_item_id' => $this->additionCdpBudgetItemId,
                'total_amount' => $totalAddition,
                'status' => 'used',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->additionCdpFundingSources as $fs) {
                $source = FundingSource::find($fs['id']);
                CdpFundingSource::create([
                    'cdp_id' => $cdp->id,
                    'funding_source_id' => $fs['id'],
                    'budget_id' => $fs['budget_id'],
                    'amount' => (float) $fs['amount'],
                    'available_balance_at_creation' => $source ? $source->getAvailableBalanceForYear($year, $this->schoolId) : 0,
                ]);
            }

            // Crear RP de adición
            $rp = ContractRp::create([
                'contract_id' => $contract->id,
                'cdp_id' => $cdp->id,
                'rp_number' => ContractRp::getNextRpNumber($this->schoolId, $year),
                'fiscal_year' => $year,
                'total_amount' => $totalAddition,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->additionCdpFundingSources as $fs) {
                $amount = (float) ($fs['amount'] ?? 0);
                if ($amount > 0) {
                    RpFundingSource::create([
                        'contract_rp_id' => $rp->id,
                        'funding_source_id' => $fs['id'],
                        'budget_id' => $fs['budget_id'] ?? null,
                        'amount' => $amount,
                    ]);
                }
            }

            // Actualizar contrato
            $contract->update([
                'original_total' => $contract->original_total ?? $contract->total,
                'total' => (float) $contract->total + $totalAddition,
                'subtotal' => (float) $contract->subtotal + $totalAddition,
                'addition_amount' => (float) $contract->addition_amount + $totalAddition,
                'addition_document_path' => $path,
                'addition_date' => now(),
            ]);

            DB::commit();

            $this->showAdditionModal = false;
            $this->additionDocument = null;
            $this->dispatch('toast', message: 'Adición de $' . number_format($totalAddition, 0, ',', '.') . ' registrada con CDP #' . $cdp->formatted_number . ' y RP #' . str_pad($rp->rp_number, 4, '0', STR_PAD_LEFT) . '.', type: 'success');
            $this->viewDetail($this->contractId);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    public function resetCreateForm()
    {
        $this->selectedConvocatoriaId = '';
        $this->contractNumber = '';
        $this->contractingModality = '';
        $this->executionPlace = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->durationDays = 0;
        $this->contractObject = '';
        $this->contractJustification = '';
        $this->paymentMethod = 'single';
        $this->supervisorId = '';
        $this->contractSubtotal = '';
        $this->contractIva = '';
        $this->contractTotal = '';
        $this->supplierData = [];
        $this->cdpsData = [];
        $this->rpAssignments = [];
        $this->awardedConvocatorias = [];
        $this->supervisors = [];
        $this->convocatoriaEndDate = '';
    }

    // ══════════════════════════════════════════════════════════
    // RENDER
    // ══════════════════════════════════════════════════════════

    #[Layout('layouts.app')]
    public function render()
    {
        // Recargar contrato fresco en cada render para evitar problemas de serialización
        if ($this->currentView === 'detail' && $this->contractId) {
            $this->contract = Contract::with([
                'convocatoria.cdps.budgetItem',
                'convocatoria.cdps.fundingSources.fundingSource',
                'convocatoria.selectedProposal',
                'supplier.department',
                'supplier.municipality',
                'supervisor',
                'creator',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'paymentOrders',
            ])->forSchool($this->schoolId)->find($this->contractId);
        }

        return view('livewire.contractual-management', [
            'contracts' => $this->contracts,
            'summary'   => $this->summary,
        ]);
    }
}
