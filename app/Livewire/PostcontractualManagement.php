<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractRp;
use App\Models\Cdp;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderExpenseLine;
use App\Models\School;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostcontractualManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $filterYear;
    public $filterStatus = '';
    public $search = '';

    // Vista
    public $currentView = 'list'; // list | create | detail

    // Detalle
    public $paymentOrderId = null;
    public $paymentOrder = null;

    // ── Formulario de creación ────────────────────────────────
    public $selectedContractId = '';
    public $contractData = [];
    public $supplierData = [];
    public $fundingSourcesData = [];

    // Factura
    public $invoiceNumber = '';
    public $invoiceDate = '';
    public $paymentDate = '';

    // Pago
    public $isFullPayment = true;
    public $paySubtotal = '';
    public $payIva = '';
    public $payTotal = '';

    // Retenciones DIAN
    public $retentionConcept = '';
    public $supplierDeclaresRent = false;
    public $retentionPercentage = 0;
    public $retefuente = 0;
    public $reteiva = 0;
    public $totalRetentionsDian = 0;

    // Otros impuestos
    public $estampillaProdultoMayor = 0;
    public $estampillaProcultura = 0;
    public $retencionIca = 0;
    public $otherTaxesTotal = 0;

    // Totales
    public $totalRetentions = 0;
    public $netPayment = 0;

    public $observations = '';

    // ── Distribución por Código de Gasto ──────────────────────
    public $expenseDistributions = []; // available expense codes from convocatoria
    public $paymentMode = 'single';    // 'single' or 'split'
    public $selectedExpenseDistributionId = ''; // for single mode
    public $expenseLines = [];         // for split mode: array of line data

    // Datos auxiliares
    public $availableContracts = [];
    public $schoolMunicipality = '';
    public $supplierBankAccounts = [];
    public $selectedBankAccountId = '';
    public $showNewBankAccountForm = false;
    public $newBankName = '';
    public $newAccountType = 'ahorros';
    public $newAccountNumber = '';

    // Modales
    public $showStatusModal = false;
    public $newStatus = '';
    public $showDeleteModal = false;

    // Modal Imprimir Documentos
    public $showPrintModal = false;
    public $printDocuments = [
        'comprobante_egreso' => false,
        'orden_pago' => false,
        'constancia_recibido' => false,
        'certificado_retenciones' => false,
        'documento_soporte' => false,
    ];

    // ── Pago Directo (sin contrato) ──────────────────────────
    public $paymentType = 'contract'; // 'contract' o 'direct'
    public $selectedSupplierId = '';
    public $directDescription = '';
    public $availableSuppliers = [];
    public $availableCdps = [];
    public $selectedCdpId = '';
    public $cdpData = [];
    public $availableRps = [];
    public $selectedRpId = '';
    public $rpData = [];
    public $directBudgetItemName = '';

    protected $queryString = [
        'filterYear'   => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    // ── Mount ────────────────────────────────────────────────

    public function mount($contract_id = null)
    {
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $school = School::find($this->schoolId);
        $this->filterYear = $school?->current_validity ?? date('Y');

        // Obtener municipio del colegio para impuestos locales
        $this->schoolMunicipality = strtolower(trim($school->municipality ?? ''));

        // Si viene desde contractual con un contrato preseleccionado, ir directo a crear pago
        if ($contract_id) {
            $contract = Contract::forSchool($this->schoolId)
                ->whereIn('status', ['active', 'in_execution', 'completed'])
                ->find($contract_id);

            if ($contract) {
                $this->loadContracts();
                $this->selectedContractId = $contract->id;
                $this->currentView = 'create';
                $this->onContractSelected();
            }
        }
    }

    public function updatingSearch()      { $this->resetPage(); }
    public function updatingFilterYear()  { $this->resetPage(); }
    public function updatingFilterStatus(){ $this->resetPage(); }

    // ══════════════════════════════════════════════════════════
    // LISTADO
    // ══════════════════════════════════════════════════════════

    public function getPaymentOrdersProperty()
    {
        return PaymentOrder::with(['contract.supplier', 'supplier', 'creator'])
            ->forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->byStatus($this->filterStatus))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getSummaryProperty()
    {
        $base = PaymentOrder::forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear));

        return [
            'total'       => (clone $base)->count(),
            'draft'       => (clone $base)->byStatus('draft')->count(),
            'approved'    => (clone $base)->byStatus('approved')->count(),
            'paid'        => (clone $base)->byStatus('paid')->count(),
            'total_value' => (clone $base)->whereIn('status', ['approved', 'paid'])->sum('net_payment'),
        ];
    }

    // ══════════════════════════════════════════════════════════
    // DETALLE
    // ══════════════════════════════════════════════════════════

    public function viewDetail($id)
    {
        $this->paymentOrder = PaymentOrder::with([
            'contract.supplier.department',
            'contract.supplier.municipality',
            'contract.rps.fundingSources.fundingSource',
            'contract.rps.fundingSources.bank',
            'contract.rps.fundingSources.bankAccount',
            'supplier.department',
            'supplier.municipality',
            'cdp.budgetItem',
            'cdp.fundingSources.fundingSource',
            'contractRp.fundingSources.fundingSource',
            'budgetItem',
            'expenseLines.expenseCode',
            'expenseLines.expenseDistribution.budget.fundingSource',
            'creator',
        ])->forSchool($this->schoolId)->findOrFail($id);

        $this->paymentOrderId = $id;
        $this->currentView = 'detail';
    }

    public function backToList()
    {
        $this->currentView = 'list';
        $this->paymentOrder = null;
        $this->paymentOrderId = null;
        $this->resetCreateForm();
    }

    // ══════════════════════════════════════════════════════════
    // CREAR ORDEN DE PAGO
    // ══════════════════════════════════════════════════════════

    public function openCreateView()
    {
        if (!auth()->user()->can('postcontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear órdenes de pago.', type: 'error');
            return;
        }

        $this->resetCreateForm();
        $this->loadContracts();
        $this->loadSuppliers();
        $this->invoiceNumber = PaymentOrder::getNextInvoiceNumber($this->schoolId, (int) $this->filterYear);
        $this->currentView = 'create';
    }

    public function updatedPaymentType()
    {
        // Limpiar datos del formulario al cambiar tipo
        $this->resetCreateFormData();
        if ($this->paymentType === 'contract') {
            $this->loadContracts();
        } else {
            $this->loadSuppliers();
        }
    }

    public function loadContracts()
    {
        // Incluir contratos en ejecución y finalizados (no anulados)
        $this->availableContracts = Contract::with('supplier')
            ->forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->whereIn('status', ['active', 'in_execution', 'completed'])
            ->orderBy('contract_number')
            ->get()
            ->map(fn($c) => [
                'id'     => $c->id,
                'number' => $c->formatted_number,
                'object' => $c->object,
                'total'  => (float) $c->total,
                'status' => $c->status_name,
                'supplier' => $c->supplier?->full_name ?? 'N/A',
            ])
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════
    // PAGO DIRECTO (SIN CONTRATO)
    // ══════════════════════════════════════════════════════════

    public function loadSuppliers()
    {
        $this->availableSuppliers = Supplier::forSchool($this->schoolId)
            ->active()
            ->orderBy('first_surname')
            ->orderBy('first_name')
            ->get()
            ->map(fn($s) => [
                'id'       => $s->id,
                'name'     => $s->full_name,
                'document' => $s->full_document,
            ])
            ->toArray();
    }

    public function onSupplierSelected()
    {
        if (!$this->selectedSupplierId) {
            $this->supplierData = [];
            $this->supplierBankAccounts = [];
            $this->selectedBankAccountId = '';
            return;
        }

        $supplier = Supplier::with(['department', 'municipality'])
            ->forSchool($this->schoolId)
            ->findOrFail($this->selectedSupplierId);

        $this->supplierData = [
            'name'            => $supplier->full_name,
            'document'        => $supplier->full_document,
            'address'         => $supplier->address ?? 'No registrada',
            'municipality'    => $supplier->municipality?->name ?? 'No registrado',
            'phone'           => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
            'tax_regime'      => $supplier->tax_regime ?? '',
            'tax_regime_name' => $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
            'person_type'     => $supplier->person_type ?? '',
        ];

        // Cargar cuentas bancarias del proveedor
        $this->supplierBankAccounts = $supplier->bankAccounts()
            ->active()
            ->orderBy('bank_name')
            ->get()
            ->toArray();

        if (count($this->supplierBankAccounts) === 1) {
            $this->selectedBankAccountId = $this->supplierBankAccounts[0]['id'];
        }

        // Cargar CDPs disponibles (activos y usados) del colegio
        $this->loadAvailableCdps();

        $this->calculateRetentions();
    }

    public function loadAvailableCdps()
    {
        $this->availableCdps = Cdp::with(['budgetItem', 'fundingSources.fundingSource'])
            ->forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->whereIn('status', ['active', 'used'])
            ->orderBy('cdp_number')
            ->get()
            ->map(fn($cdp) => [
                'id'          => $cdp->id,
                'number'      => $cdp->formatted_number,
                'budget_item' => $cdp->budgetItem?->name ?? 'N/A',
                'budget_item_code' => $cdp->budgetItem?->code ?? '',
                'total'       => (float) $cdp->total_amount,
                'status'      => $cdp->status_name,
                'sources'     => $cdp->fundingSources->map(fn($fs) => [
                    'name'   => $fs->fundingSource?->name ?? 'N/A',
                    'amount' => (float) $fs->amount,
                ])->toArray(),
            ])
            ->toArray();
    }

    public function onCdpSelected()
    {
        $this->cdpData = [];
        $this->availableRps = [];
        $this->selectedRpId = '';
        $this->rpData = [];
        $this->fundingSourcesData = [];
        $this->directBudgetItemName = '';

        if (!$this->selectedCdpId) return;

        $cdp = Cdp::with([
                'budgetItem',
                'fundingSources.fundingSource',
                'contractRp.fundingSources.fundingSource',
                'contractRp.contract.supplier',
                'convocatoria',
            ])
            ->forSchool($this->schoolId)
            ->findOrFail($this->selectedCdpId);

        $this->cdpData = [
            'id'               => $cdp->id,
            'number'           => $cdp->formatted_number,
            'budget_item'      => $cdp->budgetItem?->name ?? 'N/A',
            'budget_item_code' => $cdp->budgetItem?->code ?? '',
            'budget_item_id'   => $cdp->budget_item_id,
            'total'            => (float) $cdp->total_amount,
            'status'           => $cdp->status_name,
            'convocatoria'     => $cdp->convocatoria ? ('Conv. N° ' . $cdp->convocatoria->formatted_number . ' - ' . Str::limit($cdp->convocatoria->object, 50)) : null,
        ];

        $this->directBudgetItemName = ($cdp->budgetItem?->code ?? '') . ' - ' . ($cdp->budgetItem?->name ?? '');

        // Fuentes de financiación del CDP
        $sources = [];
        foreach ($cdp->fundingSources as $fs) {
            $sources[] = [
                'name'   => $fs->fundingSource->name ?? 'N/A',
                'amount' => (float) $fs->amount,
            ];
        }
        $this->fundingSourcesData = $sources;

        // Cargar RPs asociados a este CDP
        if ($cdp->contractRp && $cdp->contractRp->status !== 'cancelled') {
            $rp = $cdp->contractRp;
            $contract = $rp->contract;
            $rpSources = $rp->fundingSources->map(fn($fs) => $fs->fundingSource?->name ?? 'N/A')->implode(', ');

            $this->availableRps = [[
                'id'              => $rp->id,
                'number'          => $rp->formatted_number,
                'total'           => (float) $rp->total_amount,
                'status'          => $rp->status_name,
                'contract_number' => $contract?->formatted_number,
                'contract_object' => $contract?->object,
                'supplier_name'   => $contract?->supplier?->full_name,
                'funding_sources' => $rpSources,
            ]];
            // Auto-seleccionar si solo hay uno
            $this->selectedRpId = $rp->id;
            $this->onRpSelected();
        }
    }

    public function onRpSelected()
    {
        $this->rpData = [];
        if (!$this->selectedRpId) return;

        $rp = ContractRp::with(['fundingSources.fundingSource'])->find($this->selectedRpId);
        if (!$rp) return;

        $this->rpData = [
            'id'     => $rp->id,
            'number' => $rp->formatted_number,
            'total'  => (float) $rp->total_amount,
            'status' => $rp->status_name,
        ];

        // Actualizar fuentes de financiación con las del RP
        $sources = [];
        foreach ($rp->fundingSources as $fs) {
            $sources[] = [
                'name'   => $fs->fundingSource->name ?? 'N/A',
                'amount' => (float) $fs->amount,
            ];
        }
        if (!empty($sources)) {
            $this->fundingSourcesData = $sources;
        }
    }

    private function resetCreateFormData()
    {
        $this->selectedContractId = '';
        $this->contractData = [];
        $this->supplierData = [];
        $this->fundingSourcesData = [];
        $this->selectedSupplierId = '';
        $this->directDescription = '';
        $this->selectedCdpId = '';
        $this->cdpData = [];
        $this->availableRps = [];
        $this->selectedRpId = '';
        $this->rpData = [];
        $this->directBudgetItemName = '';
        $this->availableCdps = [];
        $this->isFullPayment = true;
        $this->paySubtotal = '';
        $this->payIva = '';
        $this->payTotal = '';
        $this->retentionConcept = '';
        $this->supplierDeclaresRent = false;
        $this->retentionPercentage = 0;
        $this->retefuente = 0;
        $this->reteiva = 0;
        $this->totalRetentionsDian = 0;
        $this->estampillaProdultoMayor = 0;
        $this->estampillaProcultura = 0;
        $this->retencionIca = 0;
        $this->otherTaxesTotal = 0;
        $this->totalRetentions = 0;
        $this->netPayment = 0;
        $this->observations = '';
        $this->supplierBankAccounts = [];
        $this->selectedBankAccountId = '';
        $this->showNewBankAccountForm = false;
        $this->newBankName = '';
        $this->newAccountType = 'ahorros';
        $this->newAccountNumber = '';
        $this->expenseDistributions = [];
        $this->paymentMode = 'single';
        $this->selectedExpenseDistributionId = '';
        $this->expenseLines = [];
    }

    public function onContractSelected()
    {
        if (!$this->selectedContractId) {
            $this->contractData = [];
            $this->supplierData = [];
            $this->fundingSourcesData = [];
            $this->expenseDistributions = [];
            $this->expenseLines = [];
            $this->paymentMode = 'single';
            $this->selectedExpenseDistributionId = '';
            $this->paySubtotal = '';
            $this->payIva = '';
            $this->payTotal = '';
            return;
        }

        $contract = Contract::with([
            'supplier.department',
            'supplier.municipality',
            'rps.fundingSources.fundingSource',
            'rps.fundingSources.bank',
            'rps.fundingSources.bankAccount',
            'convocatoria.distributionDetails.expenseDistribution.expenseCode',
            'convocatoria.distributionDetails.expenseDistribution.budget.fundingSource',
        ])->forSchool($this->schoolId)->findOrFail($this->selectedContractId);

        $totalPaid = PaymentOrder::getTotalPaidForContract($contract->id, $this->schoolId);
        $remaining = (float) $contract->total - $totalPaid;

        $this->contractData = [
            'id'          => $contract->id,
            'number'      => $contract->formatted_number,
            'object'      => $contract->object,
            'subtotal'    => (float) $contract->subtotal,
            'iva'         => (float) $contract->iva,
            'total'       => (float) $contract->total,
            'total_paid'  => $totalPaid,
            'remaining'   => $remaining,
        ];

        $supplier = $contract->supplier;
        if ($supplier) {
            $this->supplierData = [
                'name'           => $supplier->full_name,
                'document'       => $supplier->full_document,
                'address'        => $supplier->address ?? 'No registrada',
                'municipality'   => $supplier->municipality?->name ?? 'No registrado',
                'phone'          => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
                'tax_regime'     => $supplier->tax_regime ?? '',
                'tax_regime_name'=> $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
                'person_type'    => $supplier->person_type ?? '',
            ];

            // Cargar cuentas bancarias del proveedor
            $this->supplierBankAccounts = $supplier->bankAccounts()
                ->active()
                ->orderBy('bank_name')
                ->get()
                ->toArray();

            // Si tiene una sola cuenta, seleccionarla automáticamente
            if (count($this->supplierBankAccounts) === 1) {
                $this->selectedBankAccountId = $this->supplierBankAccounts[0]['id'];
            }
        }

        // Fuentes de financiación desde los RPs
        $sources = [];
        $rpInfoByBudgetId = []; // Mapa budget_id → info de RP (fuente, banco, cuenta)
        foreach ($contract->rps as $rp) {
            foreach ($rp->fundingSources as $fs) {
                $sources[] = [
                    'name'   => $fs->fundingSource->name ?? 'N/A',
                    'amount' => (float) $fs->amount,
                ];
                if ($fs->budget_id) {
                    $rpInfoByBudgetId[$fs->budget_id] = [
                        'funding_source_name' => $fs->fundingSource->code . ' - ' . $fs->fundingSource->name ?? 'N/A',
                        'bank_name'           => $fs->bank->name ?? null,
                        'bank_account'        => $fs->bankAccount ? ($fs->bankAccount->account_type . ' - ' . $fs->bankAccount->account_number) : null,
                    ];
                }
            }
        }
        $this->fundingSourcesData = $sources;

        // Cargar distribuciones de gasto desde la convocatoria
        $this->expenseDistributions = [];
        $this->expenseLines = [];
        $this->paymentMode = 'single';
        $this->selectedExpenseDistributionId = '';

        if ($contract->convocatoria) {
            $distributions = $contract->convocatoria->distributionDetails;
            foreach ($distributions as $dist) {
                $ed = $dist->expenseDistribution;
                if ($ed && $ed->expenseCode) {
                    $budgetId = $ed->budget_id;
                    $rpInfo = $rpInfoByBudgetId[$budgetId] ?? [];
                    $this->expenseDistributions[] = [
                        'id'                      => $ed->id,
                        'expense_code_id'         => $ed->expenseCode->id,
                        'expense_code_name'       => $ed->expenseCode->code . ' - ' . $ed->expenseCode->name,
                        'convocatoria_amount'     => (float) $dist->amount,
                        'funding_source_name'     => $rpInfo['funding_source_name'] ?? ($ed->budget?->fundingSource ? ($ed->budget->fundingSource->code . ' - ' . $ed->budget->fundingSource->name) : null),
                        'bank_name'               => $rpInfo['bank_name'] ?? null,
                        'bank_account'            => $rpInfo['bank_account'] ?? null,
                    ];
                }
            }

            // Si solo hay una distribución, seleccionarla automáticamente
            if (count($this->expenseDistributions) === 1) {
                $this->selectedExpenseDistributionId = $this->expenseDistributions[0]['id'];
            }
        }

        // Pre-llenar valores si es pago completo
        if ($remaining > 0) {
            $this->paySubtotal = $this->isFullPayment ? $contract->subtotal : '';
            $this->payIva = $this->isFullPayment ? $contract->iva : '';
            $this->payTotal = $this->isFullPayment ? $remaining : '';
        } else {
            $this->paySubtotal = 0;
            $this->payIva = 0;
            $this->payTotal = 0;
        }
        $this->paymentDate = now()->format('Y-m-d');

        $this->initializeExpenseLines();
        $this->calculateRetentions();
    }

    public function updatedIsFullPayment($value)
    {
        if ($value && !empty($this->contractData)) {
            $remaining = $this->contractData['remaining'] ?? 0;
            $this->paySubtotal = $remaining > 0 ? $this->contractData['subtotal'] : 0;
            $this->payIva = $remaining > 0 ? $this->contractData['iva'] : 0;
            $this->payTotal = $remaining > 0 ? $remaining : 0;
        } else {
            $this->paySubtotal = '';
            $this->payIva = '';
            $this->payTotal = '';
        }
        $this->calculateRetentions();
    }

    public function updatedPaySubtotal()
    {
        $this->payTotal = (float) ($this->paySubtotal ?? 0) + (float) ($this->payIva ?? 0);
        $this->calculateRetentions();
    }

    public function updatedPayIva()
    {
        $this->payTotal = (float) ($this->paySubtotal ?? 0) + (float) ($this->payIva ?? 0);
        $this->calculateRetentions();
    }

    public function updatedRetentionConcept()
    {
        $this->calculateRetentions();
    }

    public function updatedSupplierDeclaresRent()
    {
        $this->calculateRetentions();
    }

    // ══════════════════════════════════════════════════════════
    // DISTRIBUCIÓN POR CÓDIGO DE GASTO
    // ══════════════════════════════════════════════════════════

    /**
     * Inicializa las líneas de gasto según el modo seleccionado.
     */
    public function initializeExpenseLines()
    {
        if (empty($this->expenseDistributions)) {
            $this->expenseLines = [];
            return;
        }

        if ($this->paymentMode === 'single') {
            // En modo single, una sola línea con el total
            $this->expenseLines = [];
        } else {
            // En modo split, una línea por cada distribución
            $lines = [];
            foreach ($this->expenseDistributions as $dist) {
                $lines[] = $this->makeExpenseLine($dist);
            }
            $this->expenseLines = $lines;
        }
    }

    private function makeExpenseLine(array $dist): array
    {
        return [
            'expense_distribution_id' => $dist['id'],
            'expense_code_id'         => $dist['expense_code_id'],
            'expense_code_name'       => $dist['expense_code_name'],
            'max_amount'              => $dist['convocatoria_amount'],
            'funding_source_name'     => $dist['funding_source_name'] ?? null,
            'bank_name'               => $dist['bank_name'] ?? null,
            'bank_account'            => $dist['bank_account'] ?? null,
            'subtotal'                => '',
            'iva'                     => '',
            'total'                   => 0,
            'exceeded'                => false,
            'retention_concept'       => $this->retentionConcept,
            'supplier_declares_rent'  => $this->supplierDeclaresRent,
            'retention_percentage'    => 0,
            'retefuente'              => 0,
            'reteiva'                 => 0,
            'estampilla_produlto_mayor' => 0,
            'estampilla_procultura'   => 0,
            'retencion_ica'           => 0,
            'total_retentions'        => 0,
            'net_payment'             => 0,
        ];
    }

    public function updatedPaymentMode()
    {
        $this->initializeExpenseLines();

        if ($this->paymentMode === 'single') {
            // Restore values from contract when switching back to single
            if (!empty($this->contractData)) {
                $remaining = $this->contractData['remaining'] ?? 0;
                if ($this->isFullPayment && $remaining > 0) {
                    $this->paySubtotal = $this->contractData['subtotal'];
                    $this->payIva = $this->contractData['iva'];
                    $this->payTotal = $remaining;
                }
            }
            $this->calculateRetentions();
        } else {
            // In split mode, reset global values — they'll be recalculated from lines
            $this->paySubtotal = 0;
            $this->payIva = 0;
            $this->payTotal = 0;
            $this->retefuente = 0;
            $this->reteiva = 0;
            $this->totalRetentionsDian = 0;
            $this->estampillaProdultoMayor = 0;
            $this->estampillaProcultura = 0;
            $this->retencionIca = 0;
            $this->otherTaxesTotal = 0;
            $this->totalRetentions = 0;
            $this->netPayment = 0;
        }
    }

    public function updatedSelectedExpenseDistributionId()
    {
        // No need to recalculate, just track the selection
    }

    /**
     * Livewire lifecycle hook: catches ANY change to expenseLines.*.* properties.
     * This is the reliable way to trigger recalculations when nested array values change.
     */
    public function updatedExpenseLines($value, $key)
    {
        // $key is like "0.subtotal", "1.iva", "0.retention_concept", etc.
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $this->calculateLineRetentions($index);
            $this->recalculateFromLines();
        }
    }

    /**
     * Actualiza una línea de gasto cuando cambia subtotal o IVA.
     */
    public function updateExpenseLine($index, $field)
    {
        if (!isset($this->expenseLines[$index])) return;

        $line = &$this->expenseLines[$index];
        $subtotal = (float) ($line['subtotal'] ?? 0);
        $iva = (float) ($line['iva'] ?? 0);
        $line['total'] = $subtotal + $iva;

        $this->calculateLineRetentions($index);
        $this->recalculateFromLines();
    }

    /**
     * Actualiza retenciones de una línea cuando cambia su concepto o declara renta.
     */
    public function updateLineRetentionConfig($index)
    {
        if (!isset($this->expenseLines[$index])) return;
        $this->calculateLineRetentions($index);
        $this->recalculateFromLines();
    }

    /**
     * Calcula retenciones para una línea específica de gasto.
     * Also validates that the line subtotal+iva doesn't exceed the convocatoria amount.
     */
    private function calculateLineRetentions(int $index)
    {
        if (!isset($this->expenseLines[$index])) return;

        $line = &$this->expenseLines[$index];
        $subtotal = (float) ($line['subtotal'] ?? 0);
        $iva = (float) ($line['iva'] ?? 0);
        $total = $subtotal + $iva;
        $maxAmount = (float) ($line['max_amount'] ?? 0);

        // Validate: total cannot exceed the convocatoria assigned amount
        if ($total > $maxAmount && $maxAmount > 0) {
            $line['exceeded'] = true;
        } else {
            $line['exceeded'] = false;
        }

        $line['total'] = $total;

        $taxRegime = $this->supplierData['tax_regime'] ?? '';
        $contractSubtotal = (float) ($this->contractData['subtotal'] ?? $subtotal);
        $concept = $line['retention_concept'] ?? '';
        $declaresRent = (bool) ($line['supplier_declares_rent'] ?? false);

        // Retefuente
        $line['retefuente'] = 0;
        $line['retention_percentage'] = 0;

        if ($taxRegime !== 'simple' && $concept && isset(PaymentOrder::RETENTION_RATES[$concept])) {
            if (PaymentOrder::meetsRetentionThreshold($concept, $contractSubtotal)) {
                $rate = PaymentOrder::getRetentionRate($concept, $declaresRent);
                $line['retention_percentage'] = $rate;
                $line['retefuente'] = $this->roundRetention($subtotal * ($rate / 100));
            }
        }

        // ReteIVA
        $line['reteiva'] = 0;
        $isIvaResponsible = in_array($taxRegime, ['comun', 'gran_contribuyente', 'simple']);
        if ($isIvaResponsible && $iva > 0 && $concept) {
            if (PaymentOrder::meetsRetentionThreshold($concept, $contractSubtotal)) {
                $line['reteiva'] = $this->roundRetention($iva * 0.15);
            }
        }

        // Otros impuestos
        $line['estampilla_produlto_mayor'] = 0;
        $line['estampilla_procultura'] = 0;
        $line['retencion_ica'] = 0;
        $municipality = $this->schoolMunicipality;

        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 1) {
            $line['estampilla_produlto_mayor'] = $this->roundRetention($subtotal * 0.02);
        }
        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 35018010) {
            $line['estampilla_procultura'] = $this->roundRetention($subtotal * 0.02);
        }

        $line['total_retentions'] = $line['retefuente'] + $line['reteiva']
            + $line['estampilla_produlto_mayor'] + $line['estampilla_procultura'] + $line['retencion_ica'];
        $line['net_payment'] = $total - $line['total_retentions'];

        $this->expenseLines[$index] = $line;
    }

    /**
     * Recalcula los totales globales sumando todas las líneas de gasto.
     */
    private function recalculateFromLines()
    {
        if ($this->paymentMode !== 'split' || empty($this->expenseLines)) {
            return;
        }

        $totalSubtotal = 0;
        $totalIva = 0;
        $totalRetefuente = 0;
        $totalReteiva = 0;
        $totalEstProdulto = 0;
        $totalEstProcultura = 0;
        $totalRetencionIca = 0;

        foreach ($this->expenseLines as $line) {
            $totalSubtotal += (float) ($line['subtotal'] ?? 0);
            $totalIva += (float) ($line['iva'] ?? 0);
            $totalRetefuente += (float) ($line['retefuente'] ?? 0);
            $totalReteiva += (float) ($line['reteiva'] ?? 0);
            $totalEstProdulto += (float) ($line['estampilla_produlto_mayor'] ?? 0);
            $totalEstProcultura += (float) ($line['estampilla_procultura'] ?? 0);
            $totalRetencionIca += (float) ($line['retencion_ica'] ?? 0);
        }

        $this->paySubtotal = $totalSubtotal;
        $this->payIva = $totalIva;
        $this->payTotal = $totalSubtotal + $totalIva;
        $this->retefuente = $totalRetefuente;
        $this->reteiva = $totalReteiva;
        $this->estampillaProdultoMayor = $totalEstProdulto;
        $this->estampillaProcultura = $totalEstProcultura;
        $this->retencionIca = $totalRetencionIca;
        $this->totalRetentionsDian = $totalRetefuente + $totalReteiva;
        $this->otherTaxesTotal = $totalEstProdulto + $totalEstProcultura + $totalRetencionIca;
        $this->totalRetentions = $this->totalRetentionsDian + $this->otherTaxesTotal;
        $this->netPayment = $this->payTotal - $this->totalRetentions;
    }

    /**
     * Calcula todas las retenciones según las reglas colombianas:
     *
     * RETENCIONES DIAN:
     * - Retefuente: % sobre subtotal del pago, solo si el subtotal del CONTRATO >= base mínima del concepto
     *   (para pagos parciales, si el contrato cumple la base, TODOS los pagos descuentan proporcionalmente)
     * - ReteIVA: 15% del IVA, solo para responsables de IVA (régimen común, gran contribuyente, o régimen simple si es responsable de IVA)
     *   y si la base del contrato sobrepasa para la retención en la fuente de renta
     *
     * OTROS IMPUESTOS (según municipio del colegio):
     * - Estampilla Produlto Mayor: 2% del subtotal si >= $1 (solo Bucaramanga)
     * - Estampilla Procultura: 2% del subtotal si >= $35,018,010 (solo Bucaramanga)
     * - Retención ICA: solo Piedecuesta y Villanueva
     *
     * REGLAS DE RÉGIMEN:
     * - Régimen Simplificado: son personas naturales, NO responsables de IVA,
     *   hay unos que declaran y otros que no declara
     * - Régimen Simple: solo se les puede aplicar ReteIVA si son responsables de IVA
     * - Régimen Común: responsables de IVA y declarantes de renta
     * - Gran Contribuyente: responsables de IVA y declarantes de renta
     * - No Responsable de IVA: personas naturales, NO responsables de IVA
     */
    public function calculateRetentions()
    {
        // In split mode, retentions are calculated per-line
        if ($this->paymentMode === 'split' && !empty($this->expenseLines)) {
            $this->recalculateFromLines();
            return;
        }

        $subtotal = (float) ($this->paySubtotal ?? 0);
        $iva = (float) ($this->payIva ?? 0);
        $total = $subtotal + $iva;
        $this->payTotal = $total;

        $taxRegime = $this->supplierData['tax_regime'] ?? '';

        // Para pagos parciales, usar el subtotal del CONTRATO para verificar la base mínima
        // pero calcular la retención sobre el subtotal del PAGO
        $contractSubtotal = (float) ($this->contractData['subtotal'] ?? $subtotal);

        // ── RETEFUENTE ──
        // Proveedores en Régimen Simple de Tributación NO están sujetos a retención en la fuente de renta
        $this->retefuente = 0;
        $this->retentionPercentage = 0;

        if ($taxRegime !== 'simple' && $this->retentionConcept && isset(PaymentOrder::RETENTION_RATES[$this->retentionConcept])) {
            // Verificar si el subtotal del CONTRATO supera la base mínima
            if (PaymentOrder::meetsRetentionThreshold($this->retentionConcept, $contractSubtotal)) {
                $rate = PaymentOrder::getRetentionRate($this->retentionConcept, (bool) $this->supplierDeclaresRent);
                $this->retentionPercentage = $rate;
                // Calcular retención sobre el subtotal del PAGO (proporcional)
                $this->retefuente = $this->roundRetention($subtotal * ($rate / 100));
            }
        }

        // ── RETEIVA ──
        // 15% del IVA, solo para responsables de IVA
        // Responsables de IVA: régimen común, gran contribuyente, y régimen simple SI es responsable
        // y solo si la base del contrato sobrepasa el umbral de retención en la fuente
        $this->reteiva = 0;
        $isIvaResponsible = in_array($taxRegime, ['comun', 'gran_contribuyente', 'simple']);

        if ($isIvaResponsible && $iva > 0 && $this->retentionConcept) {
            if (PaymentOrder::meetsRetentionThreshold($this->retentionConcept, $contractSubtotal)) {
                $this->reteiva = $this->roundRetention($iva * 0.15);
            }
        }

        $this->totalRetentionsDian = $this->retefuente + $this->reteiva;

        // ── OTROS IMPUESTOS (según municipio del colegio) ──
        $this->estampillaProdultoMayor = 0;
        $this->estampillaProcultura = 0;
        $this->retencionIca = 0;

        $municipality = $this->schoolMunicipality;

        // Estampilla Produlto Mayor: 2% si subtotal >= $1 (solo Bucaramanga)
        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 1) {
            $this->estampillaProdultoMayor = $this->roundRetention($subtotal * 0.02);
        }

        // Estampilla Procultura: 2% si subtotal >= $35,018,010 (solo Bucaramanga)
        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 35018010) {
            $this->estampillaProcultura = $this->roundRetention($subtotal * 0.02);
        }

        // Retención ICA: solo Piedecuesta y Villanueva (placeholder - tasa configurable)
        // Por ahora no se aplica automáticamente, se deja en 0

        $this->otherTaxesTotal = $this->estampillaProdultoMayor + $this->estampillaProcultura + $this->retencionIca;

        // ── TOTALES ──
        $this->totalRetentions = $this->totalRetentionsDian + $this->otherTaxesTotal;
        $this->netPayment = $total - $this->totalRetentions;
    }

    /**
     * Redondea valores de retención a valores cerrados:
     * - Si el valor >= 1000: redondea al millar más cercano (3311 → 3000, 3500 → 4000)
     * - Si el valor < 1000: redondea a la centena más cercana (602 → 600, 650 → 700)
     * - Si el valor es 0: retorna 0
     */
    private function roundRetention(float $value): int
    {
        if ($value <= 0) return 0;

        if ($value >= 1000) {
            return (int) round($value / 1000) * 1000;
        }

        return (int) round($value / 100) * 100;
    }

    public function toggleNewBankAccountForm()
    {
        $this->showNewBankAccountForm = !$this->showNewBankAccountForm;
        if (!$this->showNewBankAccountForm) {
            $this->newBankName = '';
            $this->newAccountType = 'ahorros';
            $this->newAccountNumber = '';
        }
    }

    public function saveNewBankAccount()
    {
        $this->validate([
            'newBankName'      => 'required|string|max:100',
            'newAccountType'   => 'required|in:ahorros,corriente',
            'newAccountNumber' => 'required|string|max:30',
        ], [
            'newBankName.required'      => 'El nombre del banco es obligatorio.',
            'newAccountType.required'   => 'El tipo de cuenta es obligatorio.',
            'newAccountNumber.required' => 'El número de cuenta es obligatorio.',
        ]);

        // Obtener el proveedor según el tipo de pago
        $supplier = null;
        if ($this->paymentType === 'direct' && $this->selectedSupplierId) {
            $supplier = Supplier::find($this->selectedSupplierId);
        } elseif ($this->selectedContractId) {
            $contract = Contract::with('supplier')->find($this->selectedContractId);
            $supplier = $contract?->supplier;
        }

        if (!$supplier) {
            $this->dispatch('toast', message: 'No se encontró el proveedor.', type: 'error');
            return;
        }

        $bankAccount = SupplierBankAccount::create([
            'supplier_id'    => $supplier->id,
            'bank_name'      => $this->newBankName,
            'account_type'   => $this->newAccountType,
            'account_number' => $this->newAccountNumber,
        ]);

        // Recargar cuentas y seleccionar la nueva
        $this->supplierBankAccounts = $supplier->bankAccounts()
            ->active()
            ->orderBy('bank_name')
            ->get()
            ->toArray();

        $this->selectedBankAccountId = $bankAccount->id;

        // Limpiar formulario
        $this->showNewBankAccountForm = false;
        $this->newBankName = '';
        $this->newAccountType = 'ahorros';
        $this->newAccountNumber = '';

        $this->dispatch('toast', message: 'Cuenta bancaria registrada exitosamente.', type: 'success');
    }

    public function savePaymentOrder()
    {
        if (!auth()->user()->can('postcontractual.create')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        // Validación base
        $rules = [
            'paymentDate'  => 'required|date',
            'invoiceDate'  => 'required|date',
            'invoiceNumber'=> 'required|string|max:100',
            'paySubtotal'  => 'required|numeric|min:0.01',
            'payIva'       => 'nullable|numeric|min:0',
            'payTotal'     => 'required|numeric|min:0.01',
        ];
        $messages = [
            'paymentDate.required'  => 'La fecha de pago es obligatoria.',
            'invoiceDate.required'  => 'La fecha de la factura es obligatoria.',
            'invoiceNumber.required'=> 'El número de factura es obligatorio.',
            'paySubtotal.required'  => 'El subtotal es obligatorio.',
            'paySubtotal.min'       => 'El subtotal debe ser mayor a 0.',
            'payTotal.required'     => 'El total es obligatorio.',
        ];

        if ($this->paymentType === 'contract') {
            $rules['selectedContractId'] = 'required|exists:contracts,id';
            $messages['selectedContractId.required'] = 'Debe seleccionar un contrato.';
        } else {
            $rules['selectedSupplierId'] = 'required|exists:suppliers,id';
            $rules['directDescription'] = 'required|string|min:5|max:1000';
            $messages['selectedSupplierId.required'] = 'Debe seleccionar un proveedor.';
            $messages['directDescription.required'] = 'La descripción del pago es obligatoria.';
            $messages['directDescription.min'] = 'La descripción debe tener al menos 5 caracteres.';
        }

        $this->validate($rules, $messages);

        // Validaciones específicas para pagos con contrato
        if ($this->paymentType === 'contract') {
            // Validar distribución de gasto
            if (!empty($this->expenseDistributions)) {
                if ($this->paymentMode === 'single' && !$this->selectedExpenseDistributionId) {
                    $this->dispatch('toast', message: 'Debe seleccionar un código de gasto.', type: 'error');
                    return;
                }

                if ($this->paymentMode === 'single' && $this->selectedExpenseDistributionId) {
                    $selectedDist = collect($this->expenseDistributions)->firstWhere('id', $this->selectedExpenseDistributionId);
                    if ($selectedDist) {
                        $maxAmount = (float) $selectedDist['convocatoria_amount'];
                        if ((float) $this->payTotal > $maxAmount) {
                            $this->dispatch('toast', message: "El total del pago (\$" . number_format($this->payTotal, 2, ',', '.') . ") excede lo asignado al código de gasto (\$" . number_format($maxAmount, 2, ',', '.') . ").", type: 'error');
                            return;
                        }
                    }
                }

                if ($this->paymentMode === 'split') {
                    $hasAmount = false;
                    foreach ($this->expenseLines as $line) {
                        if ((float) ($line['subtotal'] ?? 0) > 0) {
                            $hasAmount = true;
                        }
                        $lineTotal = (float) ($line['subtotal'] ?? 0) + (float) ($line['iva'] ?? 0);
                        $maxAmount = (float) ($line['max_amount'] ?? 0);
                        if ($lineTotal > $maxAmount && $maxAmount > 0) {
                            $this->dispatch('toast', message: "El monto de '{$line['expense_code_name']}' (\$" . number_format($lineTotal, 2, ',', '.') . ") excede lo asignado en la convocatoria (\$" . number_format($maxAmount, 2, ',', '.') . ").", type: 'error');
                            return;
                        }
                    }
                    if (!$hasAmount) {
                        $this->dispatch('toast', message: 'Debe asignar montos a al menos un código de gasto.', type: 'error');
                        return;
                    }

                    $linesTotal = 0;
                    foreach ($this->expenseLines as $line) {
                        $linesTotal += (float) ($line['subtotal'] ?? 0) + (float) ($line['iva'] ?? 0);
                    }
                    if (abs($linesTotal - (float) $this->payTotal) > 1) {
                        $this->dispatch('toast', message: 'La suma de las líneas de gasto no coincide con el total del pago.', type: 'error');
                        return;
                    }
                }
            }

            // Validar que no exceda el saldo pendiente
            $remaining = $this->contractData['remaining'] ?? 0;
            if ((float) $this->payTotal > $remaining) {
                $formattedTotal = number_format($this->payTotal, 2, ',', '.');
                $formattedRemaining = number_format($remaining, 2, ',', '.');
                $this->dispatch('toast', message: "El monto (\${$formattedTotal}) excede el saldo pendiente (\${$formattedRemaining}).", type: 'error');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;

            $paymentData = [
                'school_id'                  => $this->schoolId,
                'payment_type'               => $this->paymentType,
                'payment_number'             => PaymentOrder::getNextPaymentNumber($this->schoolId, $year),
                'fiscal_year'                => $year,
                'invoice_number'             => $this->invoiceNumber,
                'invoice_date'               => $this->invoiceDate,
                'payment_date'               => $this->paymentDate,
                'is_full_payment'            => $this->paymentType === 'direct' ? true : $this->isFullPayment,
                'subtotal'                   => (float) $this->paySubtotal,
                'iva'                        => (float) ($this->payIva ?? 0),
                'total'                      => (float) $this->payTotal,
                'retention_concept'          => $this->paymentMode === 'single' ? ($this->retentionConcept ?: null) : null,
                'supplier_declares_rent'     => $this->paymentMode === 'single' ? $this->supplierDeclaresRent : false,
                'retention_percentage'       => $this->paymentMode === 'single' ? $this->retentionPercentage : 0,
                'retefuente'                 => $this->retefuente,
                'reteiva'                    => $this->reteiva,
                'estampilla_produlto_mayor'  => $this->estampillaProdultoMayor,
                'estampilla_procultura'      => $this->estampillaProcultura,
                'retencion_ica'              => $this->retencionIca,
                'other_taxes_total'          => $this->otherTaxesTotal,
                'total_retentions'           => $this->totalRetentions,
                'net_payment'                => $this->netPayment,
                'observations'               => $this->observations ?: null,
                'supplier_bank_name'         => null,
                'supplier_account_type'      => null,
                'supplier_account_number'    => null,
                'status'                     => 'draft',
                'created_by'                 => auth()->id(),
            ];

            if ($this->paymentType === 'contract') {
                $paymentData['contract_id'] = $this->selectedContractId;
            } else {
                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
                $paymentData['cdp_id'] = $this->selectedCdpId ?: null;
                $paymentData['contract_rp_id'] = $this->selectedRpId ?: null;
                $paymentData['budget_item_id'] = !empty($this->cdpData['budget_item_id']) ? $this->cdpData['budget_item_id'] : null;
            }

            $paymentOrder = PaymentOrder::create($paymentData);

            // Guardar snapshot de cuenta bancaria seleccionada
            if ($this->selectedBankAccountId) {
                $bankAccount = SupplierBankAccount::find($this->selectedBankAccountId);
                if ($bankAccount) {
                    $paymentOrder->update([
                        'supplier_bank_name'      => $bankAccount->bank_name,
                        'supplier_account_type'   => $bankAccount->account_type_name,
                        'supplier_account_number' => $bankAccount->account_number,
                    ]);
                }
            }

            // Guardar líneas de distribución por código de gasto (solo para pagos con contrato)
            if ($this->paymentType === 'contract' && !empty($this->expenseDistributions)) {
                if ($this->paymentMode === 'single' && $this->selectedExpenseDistributionId) {
                    $dist = collect($this->expenseDistributions)->firstWhere('id', $this->selectedExpenseDistributionId);
                    if ($dist) {
                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $paymentOrder->id,
                            'expense_distribution_id' => $dist['id'],
                            'expense_code_id'         => $dist['expense_code_id'],
                            'subtotal'                => (float) $this->paySubtotal,
                            'iva'                     => (float) ($this->payIva ?? 0),
                            'total'                   => (float) $this->payTotal,
                            'retention_concept'       => $this->retentionConcept ?: null,
                            'supplier_declares_rent'  => $this->supplierDeclaresRent,
                            'retention_percentage'    => $this->retentionPercentage,
                            'retefuente'              => $this->retefuente,
                            'reteiva'                 => $this->reteiva,
                            'estampilla_produlto_mayor' => $this->estampillaProdultoMayor,
                            'estampilla_procultura'   => $this->estampillaProcultura,
                            'retencion_ica'           => $this->retencionIca,
                            'total_retentions'        => $this->totalRetentions,
                            'net_payment'             => $this->netPayment,
                        ]);
                    }
                } elseif ($this->paymentMode === 'split') {
                    foreach ($this->expenseLines as $line) {
                        $lineSubtotal = (float) ($line['subtotal'] ?? 0);
                        if ($lineSubtotal <= 0 && (float) ($line['iva'] ?? 0) <= 0) continue;

                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $paymentOrder->id,
                            'expense_distribution_id' => $line['expense_distribution_id'],
                            'expense_code_id'         => $line['expense_code_id'],
                            'subtotal'                => $lineSubtotal,
                            'iva'                     => (float) ($line['iva'] ?? 0),
                            'total'                   => (float) ($line['total'] ?? 0),
                            'retention_concept'       => $line['retention_concept'] ?: null,
                            'supplier_declares_rent'  => (bool) ($line['supplier_declares_rent'] ?? false),
                            'retention_percentage'    => (float) ($line['retention_percentage'] ?? 0),
                            'retefuente'              => (float) ($line['retefuente'] ?? 0),
                            'reteiva'                 => (float) ($line['reteiva'] ?? 0),
                            'estampilla_produlto_mayor' => (float) ($line['estampilla_produlto_mayor'] ?? 0),
                            'estampilla_procultura'   => (float) ($line['estampilla_procultura'] ?? 0),
                            'retencion_ica'           => (float) ($line['retencion_ica'] ?? 0),
                            'total_retentions'        => (float) ($line['total_retentions'] ?? 0),
                            'net_payment'             => (float) ($line['net_payment'] ?? 0),
                        ]);
                    }
                }
            }

            DB::commit();

            $this->dispatch('toast', message: 'Orden de pago creada exitosamente.', type: 'success');
            $this->viewDetail($paymentOrder->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al crear orden de pago: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // CAMBIO DE ESTADO
    // ══════════════════════════════════════════════════════════

    public function openStatusModal()
    {
        if (!auth()->user()->can('postcontractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->newStatus = '';
        $this->showStatusModal = true;
    }

    public function changeStatus()
    {
        if (!$this->paymentOrder || !$this->newStatus) return;

        $allowed = $this->getAllowedStatuses($this->paymentOrder->status);
        if (!in_array($this->newStatus, $allowed)) {
            $this->dispatch('toast', message: 'Cambio de estado no permitido.', type: 'error');
            return;
        }

        $this->paymentOrder->update(['status' => $this->newStatus]);
        $this->showStatusModal = false;
        $this->dispatch('toast', message: 'Estado actualizado a ' . PaymentOrder::STATUSES[$this->newStatus] . '.', type: 'success');
        $this->viewDetail($this->paymentOrder->id);
    }

    public function getAllowedStatuses(string $current): array
    {
        return match ($current) {
            'draft'    => ['approved', 'cancelled'],
            'approved' => ['paid', 'cancelled'],
            default    => [],
        };
    }

    // ══════════════════════════════════════════════════════════
    // ELIMINAR
    // ══════════════════════════════════════════════════════════

    public function confirmDelete()
    {
        if (!auth()->user()->can('postcontractual.delete')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->showDeleteModal = true;
    }

    public function deletePaymentOrder()
    {
        if (!$this->paymentOrder) return;

        if ($this->paymentOrder->status !== 'draft') {
            $this->dispatch('toast', message: 'Solo se pueden eliminar órdenes en estado borrador.', type: 'error');
            $this->showDeleteModal = false;
            return;
        }

        $this->paymentOrder->delete();
        $this->dispatch('toast', message: 'Orden de pago eliminada.', type: 'success');
        $this->showDeleteModal = false;
        $this->backToList();
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    public function resetCreateForm()
    {
        $this->paymentType = 'contract';
        $this->selectedContractId = '';
        $this->contractData = [];
        $this->supplierData = [];
        $this->fundingSourcesData = [];
        $this->invoiceNumber = '';
        $this->invoiceDate = '';
        $this->paymentDate = '';
        $this->isFullPayment = true;
        $this->paySubtotal = '';
        $this->payIva = '';
        $this->payTotal = '';
        $this->retentionConcept = '';
        $this->supplierDeclaresRent = false;
        $this->retentionPercentage = 0;
        $this->retefuente = 0;
        $this->reteiva = 0;
        $this->totalRetentionsDian = 0;
        $this->estampillaProdultoMayor = 0;
        $this->estampillaProcultura = 0;
        $this->retencionIca = 0;
        $this->otherTaxesTotal = 0;
        $this->totalRetentions = 0;
        $this->netPayment = 0;
        $this->observations = '';
        $this->availableContracts = [];
        $this->supplierBankAccounts = [];
        $this->selectedBankAccountId = '';
        $this->showNewBankAccountForm = false;
        $this->newBankName = '';
        $this->newAccountType = 'ahorros';
        $this->newAccountNumber = '';
        $this->expenseDistributions = [];
        $this->paymentMode = 'single';
        $this->selectedExpenseDistributionId = '';
        $this->expenseLines = [];
        // Direct payment
        $this->selectedSupplierId = '';
        $this->directDescription = '';
        $this->availableSuppliers = [];
        $this->availableCdps = [];
        $this->selectedCdpId = '';
        $this->cdpData = [];
        $this->availableRps = [];
        $this->selectedRpId = '';
        $this->rpData = [];
        $this->directBudgetItemName = '';
    }

    // ══════════════════════════════════════════════════════════
    // IMPRIMIR DOCUMENTOS
    // ══════════════════════════════════════════════════════════

    public function openPrintModal()
    {
        $this->printDocuments = [
            'comprobante_egreso' => false,
            'orden_pago' => false,
            'constancia_recibido' => false,
            'certificado_retenciones' => false,
            'documento_soporte' => false,
        ];
        $this->showPrintModal = true;
    }

    public function closePrintModal()
    {
        $this->showPrintModal = false;
    }

    public function printSelectedDocuments()
    {
        $selected = array_filter($this->printDocuments);

        if (empty($selected)) {
            $this->dispatch('toast', message: 'Seleccione al menos un documento para imprimir.', type: 'error');
            return;
        }

        if (!empty($selected['comprobante_egreso'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.comprobante-egreso.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['orden_pago'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.orden-pago.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['constancia_recibido'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.constancia-recibido.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['certificado_retenciones'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.certificado-retenciones.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['documento_soporte'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.documento-soporte.pdf', $this->paymentOrderId));
        }

        $this->closePrintModal();
    }

    // ══════════════════════════════════════════════════════════
    // RENDER
    // ══════════════════════════════════════════════════════════

    #[Layout('layouts.app')]
    public function render()
    {
        // Recargar orden de pago fresco en cada render para evitar problemas de serialización
        if ($this->currentView === 'detail' && $this->paymentOrderId) {
            $this->paymentOrder = PaymentOrder::with([
                'contract.supplier.department',
                'contract.supplier.municipality',
                'contract.rps.fundingSources.fundingSource',
                'supplier.department',
                'supplier.municipality',
                'cdp.budgetItem',
                'cdp.fundingSources.fundingSource',
                'contractRp.fundingSources.fundingSource',
                'budgetItem',
                'expenseLines.expenseCode',
                'creator',
            ])->forSchool($this->schoolId)->find($this->paymentOrderId);
        }

        return view('livewire.postcontractual-management', [
            'paymentOrders' => $this->paymentOrders,
            'summary'       => $this->summary,
        ]);
    }
}
