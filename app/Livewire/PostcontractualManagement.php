<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractRp;
use App\Models\Cdp;
use App\Models\CdpFundingSource;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BudgetItem;
use App\Models\FundingSource;
use App\Models\Budget;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderExpenseLine;
use App\Models\RetentionConfig;
use App\Models\RpFundingSource;
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
    public $estampillaProdeporte = 0;
    public $retencionIca = 0;
    public $otherTaxesTotal = 0;
    public $otherTaxesBreakdown = [];

    // Flags manuales para estampillas
    public $applyEstampillaProdulto = false;
    public $applyEstampillaProcultura = false;
    public $applyEstampillaProdeporte = false;
    public $applyOtherTaxes = [];
    public $applyRetencionIca = false;
    public $retencionIcaPercentage = 0;

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
    // CDP inline para pago directo
    public $directBudgetItems = [];
    public $directBudgetItemId = '';
    public $directFundingSources = []; // fuentes disponibles para el rubro seleccionado
    public $directSelectedSources = []; // fuentes seleccionadas con montos [{id, name, amount, available, budget_id}]
    public $skipCdpRp = false; // Para pagos que no requieren CDP/RP (retenciones, gastos financieros)

    // Líneas de impuestos para pago directo sin CDP/RP
    public $skipTaxLines = [];   // [{tax_type, amount}]  — impuestos seleccionados con montos
    public $skipTaxTotal = 0;    // suma en tiempo real de skipTaxLines
    // Líneas bancarias para pago directo sin CDP/RP
    public $skipBankLines = [];  // [{bank_id, bank_account_id, amount, bank_label, account_label}]
    public $skipBankTotal = 0;   // suma en tiempo real de skipBankLines
    public $skipBanks = [];      // bancos del colegio [{id, name, accounts:[{id, label}]}]

    // Códigos de gasto para pago directo (nuevo flujo)
    public $directExpenseCodes = []; // códigos de gasto disponibles con disponibilidad
    public $directExpenseCodeSearch = ''; // filtro de búsqueda para códigos de gasto
    public $directSelectedExpenseCodes = []; // códigos seleccionados [{id, code, name, budget_items: [{id, name, sources: [...]}]}]
    public $directExpenseAllocations = []; // asignaciones [{expense_code_id, budget_item_id, funding_source_id, amount, budget_id, bank_id, bank_account_id}]
    public $directBanks = []; // Bancos del colegio para selección en pago directo
    public $directBankAccounts = []; // Cuentas por banco para pago directo

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

    /**
     * Configuraciones de retención del colegio para la vigencia actual.
     * Se usa en las vistas informativas (listado de tarifas, etc.).
     * Se indexa por concepto para acceso rápido: $configs['compras']->rate_declares.
     */
    public function getRetentionConfigsProperty()
    {
        return RetentionConfig::forSchool($this->schoolId)
            ->where('fiscal_year', (int) ($this->filterYear ?: now()->year))
            ->get()
            ->keyBy('concept');
    }

    public function getRetefuenteConceptOptionsProperty()
    {
        return $this->retentionConfigs
            ->filter(fn($config) => $config->category === 'retefuente')
            ->sortBy('display_name')
            ->values();
    }

    public function getRetentionApplicabilityContextProperty(): array
    {
        return [
            'person_type' => $this->supplierData['person_type'] ?? null,
            'payment_type' => $this->paymentType,
            'is_service_contract' => (bool) ($this->contractData['is_service_contract'] ?? false),
        ];
    }

    protected function retentionApplies(?RetentionConfig $config, array $context = []): bool
    {
        return $config && $config->appliesToContext($context ?: $this->retentionApplicabilityContext);
    }

    public function getOtherTaxConfigsProperty()
    {
        return $this->retentionConfigs
            ->filter(fn($config) => in_array($config->category, ['estampilla', 'ica'], true))
            ->sortBy('display_name')
            ->values();
    }

    public function getRetencionIcaConfigProperty(): ?RetentionConfig
    {
        $config = $this->retentionConfigs['retencion_ica'] ?? null;

        return $config instanceof RetentionConfig ? $config : null;
    }

    public function getCanApplyRetencionIcaProperty(): bool
    {
        $config = $this->retencionIcaConfig;

        return $config
            && (bool) ($config->is_active ?? false)
            && $this->retentionApplies($config, $this->retentionApplicabilityContext);
    }

    protected function syncApplyOtherTaxesDefaults(): void
    {
        foreach ($this->otherTaxConfigs as $cfg) {
            $concept = (string) ($cfg->concept ?? '');
            if ($concept === '') {
                continue;
            }
            if (!array_key_exists($concept, $this->applyOtherTaxes)) {
                $this->applyOtherTaxes[$concept] = (bool) ($cfg->is_active ?? false);
            }
        }
    }

    protected function calculateOtherTaxesBreakdown(float $subtotal, array $context): array
    {
        $this->syncApplyOtherTaxesDefaults();

        $breakdown = [];
        foreach ($this->otherTaxConfigs as $cfg) {
            $concept = (string) ($cfg->concept ?? '');
            if ($concept === '') {
                continue;
            }

            $amount = 0.0;

            if ($concept === 'retencion_ica') {
                if ($this->canApplyRetencionIca && $this->applyRetencionIca && (float) $this->retencionIcaPercentage > 0) {
                    $amount = $this->roundRetention($subtotal * ((float) $this->retencionIcaPercentage / 100));
                }

                $breakdown[$concept] = $amount;
                continue;
            }

            if ($cfg->category === 'estampilla' && ($context['payment_type'] ?? '') === 'direct') {
                $breakdown[$concept] = 0.0;
                continue;
            }

            $applyFlag = (bool) ($this->applyOtherTaxes[$concept] ?? true);
            if (($context['payment_type'] ?? '') === 'accounts_payable' && !$applyFlag) {
                $breakdown[$concept] = 0.0;
                continue;
            }

            if ($this->retentionApplies($cfg, $context)
                && (bool) ($cfg->is_active ?? false)
                && (float) ($cfg->rate ?? 0) > 0
                && $subtotal >= (float) ($cfg->min_base ?? 0)
            ) {
                $amount = $this->roundRetention($subtotal * ((float) $cfg->rate / 100));
            }

            $breakdown[$concept] = $amount;
        }

        return $breakdown;
    }

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
            if ($this->paymentType === 'accounts_payable') {
                $this->loadSkipBanks();
            }
        }
    }

    public function updatedSkipCdpRp(bool $value)
    {
        // Precargar bancos cuando el usuario activa "sin CDP/RP"
        if ($value && empty($this->skipBanks)) {
            $this->loadSkipBanks();
        }
        // Limpiar líneas si desactiva la opción
        if (!$value) {
            $this->skipTaxLines  = [];
            $this->skipTaxTotal  = 0;
            $this->skipBankLines = [];
            $this->skipBankTotal = 0;
        }
    }

    public function loadSkipBanks()
    {
        $this->skipBanks = Bank::forSchool($this->schoolId)->active()
            ->with(['accounts' => fn($q) => $q->active()])
            ->orderBy('name')
            ->get()
            ->map(fn($bank) => [
                'id'       => $bank->id,
                'name'     => $bank->name,
                'accounts' => $bank->accounts->map(fn($a) => [
                    'id'    => $a->id,
                    'label' => $a->account_number . ' - ' . $a->account_type_name
                               . ($a->holder_name ? ' (' . $a->holder_name . ')' : ''),
                ])->toArray(),
            ])
            ->toArray();
    }

    // ── Líneas de impuestos (skipCdpRp) ──────────────────────

    public function toggleSkipTaxLine(string $taxType)
    {
        $idx = collect($this->skipTaxLines)->search(fn($l) => $l['tax_type'] === $taxType);

        if ($idx !== false) {
            unset($this->skipTaxLines[$idx]);
            $this->skipTaxLines = array_values($this->skipTaxLines);
        } else {
            $this->skipTaxLines[] = ['tax_type' => $taxType, 'amount' => ''];
        }

        $this->recalculateSkipTotals();
    }

    public function updatedSkipTaxLines($value, $key)
    {
        $this->recalculateSkipTotals();
    }

    // ── Líneas bancarias (skipCdpRp) ─────────────────────────

    public function addSkipBankLine()
    {
        if (empty($this->skipBanks)) {
            $this->loadSkipBanks();
        }
        $this->skipBankLines[] = ['bank_id' => '', 'bank_account_id' => '', 'amount' => ''];
    }

    public function removeSkipBankLine(int $index)
    {
        unset($this->skipBankLines[$index]);
        $this->skipBankLines = array_values($this->skipBankLines);
        $this->recalculateSkipTotals();
    }

    public function updatedSkipBankLines($value, $key)
    {
        // Cuando cambia el banco de una línea, resetear la cuenta
        if (str_ends_with($key, '.bank_id')) {
            $index = (int) explode('.', $key)[0];
            $this->skipBankLines[$index]['bank_account_id'] = '';
        }
        $this->recalculateSkipTotals();
    }

    private function recalculateSkipTotals(): void
    {
        $this->skipTaxTotal  = collect($this->skipTaxLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
        $this->skipBankTotal = collect($this->skipBankLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
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
            'name'                => $supplier->full_name,
            'document'            => $supplier->full_document,
            'address'             => $supplier->address ?? 'No registrada',
            'municipality'        => $supplier->municipality?->name ?? 'No registrado',
            'phone'               => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
            'tax_regime'          => $supplier->tax_regime ?? '',
            'tax_regime_name'     => $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
            'person_type'         => $supplier->person_type ?? '',
            'electronic_invoicing'=> $supplier->electronic_invoicing,
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

        // Para cuentas por pagar, cargar bancos de egreso; para pago directo, cargar rubros
        if ($this->paymentType === 'accounts_payable') {
            if (empty($this->skipBanks)) {
                $this->loadSkipBanks();
            }
        } else {
            $this->loadDirectBudgetItems();
        }

        $this->calculateRetentions();
    }

    public function loadDirectBudgetItems()
    {
        // Cargar rubros presupuestales que tienen presupuesto de gasto activo con saldo disponible
        $year = (int) $this->filterYear;

        $this->directBudgetItems = BudgetItem::active()
            ->whereHas('budgets', function ($q) use ($year) {
                $q->where('school_id', $this->schoolId)
                  ->where('fiscal_year', $year)
                  ->where('type', 'expense')
                  ->where('is_active', true)
                  ->where('current_amount', '>', 0);
            })
            ->orderBy('code')
            ->get()
            ->filter(function ($item) use ($year) {
                // Verificar que tenga saldo disponible real (presupuesto - CDPs reservados)
                $budgets = Budget::where('school_id', $this->schoolId)
                    ->where('budget_item_id', $item->id)
                    ->where('fiscal_year', $year)
                    ->where('type', 'expense')
                    ->where('is_active', true)
                    ->with('fundingSource')
                    ->get();

                foreach ($budgets as $budget) {
                    $source = $budget->fundingSource;
                    if (!$source || !$source->is_active) continue;
                    $reserved = Cdp::getTotalReservedForFundingSource($source->id, $year, $this->schoolId);
                    $available = (float) $budget->current_amount - $reserved;
                    if ($available > 0) return true;
                }
                return false;
            })
            ->map(fn($item) => ['id' => $item->id, 'name' => "{$item->code} - {$item->name}"])
            ->values()
            ->toArray();

        // Cargar códigos de gasto permitidos para pagos directos
        $this->loadDirectExpenseCodes();
    }

    public function loadDirectExpenseCodes()
    {
        $year = (int) $this->filterYear;
        $expenseCodes = \App\Models\ExpenseCode::active()
            ->with('accountingAccount')
            ->get();

        $this->directExpenseCodes = [];
        foreach ($expenseCodes as $ec) {
            $distributions = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
                ->where('expense_code_id', $ec->id)
                ->where('is_active', true)
                ->with(['budget.budgetItem', 'budget.fundingSource'])
                ->whereHas('budget', fn($q) => $q->where('fiscal_year', $year)->where('type', 'expense'))
                ->get();

            if ($distributions->isEmpty()) continue;

            $totalAvailable = 0;
            foreach ($distributions as $dist) {
                $totalAvailable += max(0, $dist->available_balance);
            }
            if ($totalAvailable <= 0) continue;

            $this->directExpenseCodes[] = [
                'id' => $ec->id,
                'code' => $ec->code,
                'name' => $ec->name,
                'sifse_code' => $ec->sifse_code,
                'available' => round($totalAvailable, 2),
            ];
        }
    }

    public function toggleDirectExpenseCode($expenseCodeId)
    {
        $expenseCodeId = (int) $expenseCodeId;
        $idx = collect($this->directSelectedExpenseCodes)->search(fn($s) => $s['id'] === $expenseCodeId);

        if ($idx !== false) {
            unset($this->directSelectedExpenseCodes[$idx]);
            $this->directSelectedExpenseCodes = array_values($this->directSelectedExpenseCodes);
            $this->directExpenseAllocations = array_values(
                array_filter($this->directExpenseAllocations, fn($a) => $a['expense_code_id'] !== $expenseCodeId)
            );
            return;
        }

        $ec = collect($this->directExpenseCodes)->firstWhere('id', $expenseCodeId);
        if (!$ec) return;

        $year = (int) $this->filterYear;
        $distributions = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
            ->where('expense_code_id', $expenseCodeId)
            ->where('is_active', true)
            ->with(['budget.budgetItem', 'budget.fundingSource'])
            ->whereHas('budget', fn($q) => $q->where('fiscal_year', $year)->where('type', 'expense'))
            ->get();

        $sources = [];
        foreach ($distributions as $dist) {
            $budget = $dist->budget;
            if (!$budget || !$budget->budgetItem || !$budget->fundingSource) continue;
            // Usar el saldo disponible del rubro (código de gasto), no de la fuente de financiación
            $distAvailable = max(0, $dist->available_balance);
            if ($distAvailable <= 0) continue;

            $sources[] = [
                'budget_item_id' => $budget->budget_item_id,
                'budget_item_name' => $budget->budgetItem->code . ' - ' . $budget->budgetItem->name,
                'funding_source_id' => $budget->fundingSource->id,
                'funding_source_name' => $budget->fundingSource->code . ' - ' . $budget->fundingSource->name,
                'budget_id' => $budget->id,
                'available' => round($distAvailable, 2),
            ];
        }

        if (empty($sources)) {
            $this->dispatch('toast', message: 'No hay rubros con disponibilidad para este código.', type: 'warning');
            return;
        }

        $this->directSelectedExpenseCodes[] = [
            'id' => $ec['id'], 'code' => $ec['code'], 'name' => $ec['name'], 'sources' => $sources,
        ];

        // Auto-agregar si solo hay una fuente
        if (count($sources) === 1) {
            $s = $sources[0];
            $this->directExpenseAllocations[] = [
                'expense_code_id' => $ec['id'],
                'budget_item_id' => $s['budget_item_id'],
                'funding_source_id' => $s['funding_source_id'],
                'funding_source_name' => $s['funding_source_name'],
                'budget_id' => $s['budget_id'],
                'available' => $s['available'],
                'amount' => '',
                'bank_id' => '',
                'bank_account_id' => '',
            ];
        }

        if (empty($this->directBanks)) $this->loadDirectBanks();
    }

    public function addDirectExpenseAllocation($expenseCodeId, $sourceIndex)
    {
        $selected = collect($this->directSelectedExpenseCodes)->firstWhere('id', (int) $expenseCodeId);
        if (!$selected || !isset($selected['sources'][$sourceIndex])) return;
        $s = $selected['sources'][$sourceIndex];

        $exists = collect($this->directExpenseAllocations)->contains(fn($a) =>
            $a['expense_code_id'] === (int) $expenseCodeId && $a['funding_source_id'] === $s['funding_source_id']
        );
        if ($exists) { $this->dispatch('toast', message: 'Ya fue agregada.', type: 'warning'); return; }

        $this->directExpenseAllocations[] = [
            'expense_code_id' => (int) $expenseCodeId,
            'budget_item_id' => $s['budget_item_id'],
            'funding_source_id' => $s['funding_source_id'],
            'funding_source_name' => $s['funding_source_name'],
            'budget_id' => $s['budget_id'],
            'available' => $s['available'],
            'amount' => '',
            'bank_id' => '',
            'bank_account_id' => '',
        ];
        if (empty($this->directBanks)) $this->loadDirectBanks();
    }

    public function removeDirectExpenseAllocation($index)
    {
        unset($this->directExpenseAllocations[$index]);
        $this->directExpenseAllocations = array_values($this->directExpenseAllocations);
    }

    public function updatedDirectBudgetItemId($value)
    {
        $this->directFundingSources = [];
        $this->directSelectedSources = [];
        $this->fundingSourcesData = [];

        if (empty($value)) return;

        // Obtener fuentes de financiación con presupuesto de gasto para este rubro/colegio/año
        $budgets = Budget::where('school_id', $this->schoolId)
            ->where('budget_item_id', $value)
            ->where('fiscal_year', (int) $this->filterYear)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->with('fundingSource')
            ->get();

        $this->directFundingSources = $budgets->map(function ($budget) {
            $source = $budget->fundingSource;
            if (!$source || !$source->is_active) return null;

            // Saldo disponible = presupuesto actual - reservado por CDPs
            $reserved = Cdp::getTotalReservedForFundingSource($source->id, (int) $this->filterYear, $this->schoolId);
            $available = max(0, (float) $budget->current_amount - $reserved);

            return [
                'id'        => $source->id,
                'name'      => $source->code . ' - ' . $source->name,
                'type'      => $source->type_name,
                'available' => round($available, 2),
                'budget_id' => $budget->id,
            ];
        })->filter()->values()->toArray();

        // Auto-agregar la primera fuente si solo hay una
        if (count($this->directFundingSources) === 1) {
            $fs = $this->directFundingSources[0];

            // Cargar bancos si no están cargados
            if (empty($this->directBanks)) {
                $this->loadDirectBanks();
            }

            $this->directSelectedSources = [[
                'id'              => $fs['id'],
                'name'            => $fs['name'],
                'amount'          => '',
                'available'       => $fs['available'],
                'budget_id'       => $fs['budget_id'],
                'bank_id'         => '',
                'bank_account_id' => '',
            ]];
        }
    }

    public function addDirectFundingSource($fundingSourceId)
    {
        // Verificar que no esté ya agregada
        foreach ($this->directSelectedSources as $s) {
            if ($s['id'] == $fundingSourceId) {
                $this->dispatch('toast', message: 'Esta fuente ya fue agregada.', type: 'warning');
                return;
            }
        }

        $fs = collect($this->directFundingSources)->firstWhere('id', (int) $fundingSourceId);
        if (!$fs) return;

        // Cargar bancos del colegio si no están cargados
        if (empty($this->directBanks)) {
            $this->loadDirectBanks();
        }

        $this->directSelectedSources[] = [
            'id'              => $fs['id'],
            'name'            => $fs['name'],
            'amount'          => '',
            'available'       => $fs['available'],
            'budget_id'       => $fs['budget_id'],
            'bank_id'         => '',
            'bank_account_id' => '',
        ];
    }

    public function loadDirectBanks()
    {
        $this->directBanks = Bank::forSchool($this->schoolId)->active()
            ->with(['accounts' => fn($q) => $q->active()])
            ->orderBy('name')
            ->get()
            ->map(fn($bank) => [
                'id' => $bank->id,
                'name' => $bank->name,
                'accounts' => $bank->accounts->map(fn($a) => [
                    'id' => $a->id,
                    'label' => $a->account_number . ' - ' . $a->account_type_name,
                ])->toArray(),
            ])
            ->toArray();
    }

    public function updatedDirectSelectedSources($value, $key)
    {
        // Cuando cambia el banco, resetear la cuenta
        if (str_contains($key, '.bank_id')) {
            $index = (int) explode('.', $key)[0];
            $this->directSelectedSources[$index]['bank_account_id'] = '';
        }
    }

    public function removeDirectFundingSource($index)
    {
        unset($this->directSelectedSources[$index]);
        $this->directSelectedSources = array_values($this->directSelectedSources);
    }

    private function resetCreateFormData()
    {
        $this->selectedContractId = '';
        $this->contractData = [];
        $this->supplierData = [];
        $this->fundingSourcesData = [];
        $this->selectedSupplierId = '';
        $this->directDescription = '';
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
        $this->estampillaProdeporte = 0;
        $this->retencionIca = 0;
        $this->applyRetencionIca = false;
        $this->retencionIcaPercentage = 0;
        $this->otherTaxesTotal = 0;
        $this->otherTaxesBreakdown = [];
        $this->totalRetentions = 0;
        $this->netPayment = 0;
        $this->observations = '';
        $this->applyEstampillaProdulto = false;
        $this->applyEstampillaProcultura = false;
        $this->applyEstampillaProdeporte = false;
        $this->applyOtherTaxes = [];
        $this->selectedBankAccountId = '';
        $this->showNewBankAccountForm = false;
        $this->newBankName = '';
        $this->newAccountType = 'ahorros';
        $this->newAccountNumber = '';
        $this->expenseDistributions = [];
        $this->paymentMode = 'single';
        $this->selectedExpenseDistributionId = '';
        $this->expenseLines = [];
        $this->skipCdpRp = false;
        $this->skipTaxLines = [];
        $this->skipTaxTotal = 0;
        $this->skipBankLines = [];
        $this->skipBankTotal = 0;
        $this->skipBanks = [];
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
            'is_service_contract' => (bool) $contract->is_service_contract,
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
                'phone'          => $supplier->phone ?? 'No registrado',
                'tax_regime'     => $supplier->tax_regime ?? '',
                'tax_regime_name'=> $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
                'person_type'    => $supplier->person_type ?? '',
                'electronic_invoicing' => $supplier->electronic_invoicing,
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

            // Calcular adiciones por código de gasto (RPs de adición del contrato)
            $additionByBudget = [];
            foreach ($contract->rps->where('status', 'active')->where('is_addition', true) as $addRp) {
                foreach ($addRp->fundingSources as $rpFs) {
                    $bId = $rpFs->budget_id;
                    $additionByBudget[$bId] = ($additionByBudget[$bId] ?? 0) + (float) $rpFs->amount;
                }
            }

            foreach ($distributions as $dist) {
                $ed = $dist->expenseDistribution;
                if ($ed && $ed->expenseCode) {
                    $budgetId = $ed->budget_id;
                    $rpInfo = $rpInfoByBudgetId[$budgetId] ?? [];
                    $baseAmount = (float) $dist->amount;
                    $additionAmount = $additionByBudget[$budgetId] ?? 0;

                    $this->expenseDistributions[] = [
                        'id'                      => $ed->id,
                        'expense_code_id'         => $ed->expenseCode->id,
                        'expense_code_name'       => $ed->expenseCode->code . ' - ' . $ed->expenseCode->name,
                        'convocatoria_amount'     => $baseAmount + $additionAmount,
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

    public function updatedApplyEstampillaProdulto()
    {
        $this->calculateRetentions();
    }

    public function updatedApplyEstampillaProcultura()
    {
        $this->calculateRetentions();
    }

    public function updatedApplyOtherTaxes()
    {
        $this->calculateRetentions();
    }

    public function updatedApplyRetencionIca($value)
    {
        if (!(bool) $value) {
            $this->retencionIcaPercentage = 0;
        }

        $this->calculateRetentions();
    }

    public function updatedRetencionIcaPercentage()
    {
        $this->retencionIcaPercentage = max(0, min(100, (float) $this->retencionIcaPercentage));
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
            'estampilla_prodeporte'   => 0,
            'retencion_ica'           => 0,
            'other_taxes_breakdown'   => [],
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
            $this->estampillaProdeporte = 0;
            $this->retencionIca = 0;
            $this->applyRetencionIca = false;
            $this->retencionIcaPercentage = 0;
            $this->otherTaxesTotal = 0;
            $this->otherTaxesBreakdown = [];
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
        $retentionContext = $this->retentionApplicabilityContext;

        $fiscalYear = (int) $this->filterYear;

        // ── Retefuente ──
        $line['retefuente'] = 0;
        $line['retention_percentage'] = 0;

        if ($taxRegime !== 'simple' && $concept) {
            $config = RetentionConfig::getForSchoolYearConcept($this->schoolId, $fiscalYear, $concept);
            if ($this->retentionApplies($config, $retentionContext) && $contractSubtotal >= (float) $config->min_base) {
                $rate = $declaresRent ? (float) $config->rate_declares : (float) $config->rate_not_declares;
                $line['retention_percentage'] = $rate;
                $line['retefuente'] = $this->roundRetention($subtotal * ($rate / 100));
            }
        }

        // ── ReteIVA ──
        // - Régimen Simple: aplica directamente sin requerir concepto de retefuente.
        // - Otros regímenes: requiere concepto seleccionado y base mínima de retefuente.
        $line['reteiva'] = 0;
        $isIvaResponsible = in_array($taxRegime, ['comun', 'gran_contribuyente', 'simple']);
        if ($isIvaResponsible && $iva > 0) {
            $reteivaConfig = RetentionConfig::getForSchoolYearConcept($this->schoolId, $fiscalYear, 'reteiva');
            if ($taxRegime === 'simple') {
                // Para Régimen Simple: ReteIVA aplica directamente (no requiere concepto de retefuente)
                if ($this->retentionApplies($reteivaConfig, $retentionContext) && $iva >= (float) $reteivaConfig->min_base) {
                    $rate = (float) $reteivaConfig->rate;
                    $line['reteiva'] = $this->roundRetention($iva * ($rate / 100));
                }
            } elseif ($concept) {
                $retefuenteConfig = RetentionConfig::getForSchoolYearConcept($this->schoolId, $fiscalYear, $concept);
                if ($retefuenteConfig
                    && $reteivaConfig
                    && $this->retentionApplies($retefuenteConfig, $retentionContext)
                    && $this->retentionApplies($reteivaConfig, $retentionContext)
                    && $contractSubtotal >= (float) $retefuenteConfig->min_base
                    && $iva >= (float) $reteivaConfig->min_base
                ) {
                    $rate = (float) $reteivaConfig->rate;
                    $line['reteiva'] = $this->roundRetention($iva * ($rate / 100));
                }
            }
        }

        // ── Otros impuestos (dinámico) ──
        $otherBreakdown = $this->calculateOtherTaxesBreakdown($subtotal, $retentionContext);
        $line['other_taxes_breakdown'] = $otherBreakdown;
        $line['estampilla_produlto_mayor'] = (float) ($otherBreakdown['estampilla_produlto_mayor'] ?? 0);
        $line['estampilla_procultura'] = (float) ($otherBreakdown['estampilla_procultura'] ?? 0);
        $line['estampilla_prodeporte'] = (float) ($otherBreakdown['estampilla_prodeporte'] ?? 0);
        $line['retencion_ica'] = (float) ($otherBreakdown['retencion_ica'] ?? 0);

        $line['total_retentions'] = $line['retefuente'] + $line['reteiva'] + array_sum($otherBreakdown);
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
        $otherBreakdown = [];

        foreach ($this->expenseLines as $line) {
            $totalSubtotal += (float) ($line['subtotal'] ?? 0);
            $totalIva += (float) ($line['iva'] ?? 0);
            $totalRetefuente += (float) ($line['retefuente'] ?? 0);
            $totalReteiva += (float) ($line['reteiva'] ?? 0);

            $lineOther = is_array($line['other_taxes_breakdown'] ?? null) ? $line['other_taxes_breakdown'] : [];
            foreach ($lineOther as $concept => $amount) {
                $otherBreakdown[$concept] = (float) ($otherBreakdown[$concept] ?? 0) + (float) $amount;
            }
        }

        $this->paySubtotal = $totalSubtotal;
        $this->payIva = $totalIva;
        $this->payTotal = $totalSubtotal + $totalIva;
        $this->retefuente = $totalRetefuente;
        $this->reteiva = $totalReteiva;
        $this->otherTaxesBreakdown = $otherBreakdown;
        $this->estampillaProdultoMayor = (float) ($otherBreakdown['estampilla_produlto_mayor'] ?? 0);
        $this->estampillaProcultura = (float) ($otherBreakdown['estampilla_procultura'] ?? 0);
        $this->estampillaProdeporte = (float) ($otherBreakdown['estampilla_prodeporte'] ?? 0);
        $this->retencionIca = (float) ($otherBreakdown['retencion_ica'] ?? 0);
        $this->totalRetentionsDian = $totalRetefuente + $totalReteiva;
        $this->otherTaxesTotal = array_sum($otherBreakdown);
        $this->totalRetentions = $this->totalRetentionsDian + $this->otherTaxesTotal;
        $this->netPayment = $this->payTotal - $this->totalRetentions;
    }

    /**
     * Calcula todas las retenciones según las reglas colombianas.
     *
     * Todas las tarifas y bases mínimas provienen del módulo "Bases de Retenciones"
     * (tabla `retention_configs`) para el colegio y la vigencia del pago.
     *
     * RETENCIONES DIAN:
     * - Retefuente: % (declara / no declara) sobre el subtotal del pago, solo si el
     *   subtotal del CONTRATO >= base mínima del concepto. Para pagos parciales, si
     *   el contrato cumple la base, TODOS los pagos descuentan proporcionalmente.
     * - ReteIVA: % del IVA, solo para responsables de IVA (régimen común, gran
     *   contribuyente, o régimen simple responsable de IVA) y si la base del
     *   contrato supera el umbral del concepto de retefuente.
     *
     * OTROS IMPUESTOS (estampillas, ICA):
     * - Solo aplican si la configuración del colegio/vigencia está activa y el
     *   subtotal cumple la base mínima. Los colegios de municipios que no cobran
     *   estas retenciones simplemente dejan la configuración inactiva.
     *
     * REGLAS DE RÉGIMEN:
     * - Régimen Simplificado: personas naturales, NO responsables de IVA,
     *   pueden declarar o no declarar renta.
     * - Régimen Simple: solo ReteIVA si son responsables de IVA.
     * - Régimen Común: responsables de IVA y declarantes de renta.
     * - Gran Contribuyente: responsables de IVA y declarantes de renta.
     * - No Responsable de IVA: personas naturales, NO responsables de IVA.
     *
     * NOTA: Los pagos directos (payment_type = 'direct') NO generan retenciones
     * automáticas. El flujo "cuentas por pagar" sí aplica retenciones pero permite
     * al usuario activar/desactivar estampillas manualmente.
     */
    public function calculateRetentions()
    {
        // Pago directo sin CDP/RP (retenciones DIAN, servicios públicos, gastos
        // financieros): no se calculan descuentos automáticos — el usuario captura
        // manualmente las líneas de impuestos y bancos.
        if ($this->paymentType === 'direct' && $this->skipCdpRp) {
            $this->retefuente            = 0;
            $this->reteiva               = 0;
            $this->totalRetentionsDian   = 0;
            $this->estampillaProdultoMayor = 0;
            $this->estampillaProcultura  = 0;
            $this->estampillaProdeporte  = 0;
            $this->retencionIca          = 0;
            $this->otherTaxesTotal       = 0;
            $this->otherTaxesBreakdown   = [];
            $this->totalRetentions       = 0;
            $this->netPayment            = (float) ($this->paySubtotal ?? 0);
            return;
        }

        if (!$this->canApplyRetencionIca) {
            $this->applyRetencionIca = false;
            $this->retencionIcaPercentage = 0;
        }

        // In split mode, retentions are calculated per-line
        if ($this->paymentMode === 'split' && !empty($this->expenseLines)) {
            foreach (array_keys($this->expenseLines) as $index) {
                $this->calculateLineRetentions((int) $index);
            }
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

        $fiscalYear = (int) $this->filterYear;
        $retentionContext = $this->retentionApplicabilityContext;

        // ── RETEFUENTE ──
        // Proveedores en Régimen Simple de Tributación NO están sujetos a retención en la fuente de renta
        $this->retefuente = 0;
        $this->retentionPercentage = 0;

        $retefuenteConfig = null;
        if ($taxRegime !== 'simple' && $this->retentionConcept) {
            $retefuenteConfig = RetentionConfig::getForSchoolYearConcept(
                $this->schoolId, $fiscalYear, $this->retentionConcept
            );

            if ($this->retentionApplies($retefuenteConfig, $retentionContext) && $contractSubtotal >= (float) $retefuenteConfig->min_base) {
                $rate = $this->supplierDeclaresRent
                    ? (float) $retefuenteConfig->rate_declares
                    : (float) $retefuenteConfig->rate_not_declares;
                $this->retentionPercentage = $rate;
                $this->retefuente = $this->roundRetention($subtotal * ($rate / 100));
            }
        }

        // ── RETEIVA ──
        // Solo para responsables de IVA. La tarifa sale de la configuración activa.
        // - Régimen Simple: aplica directamente sin requerir concepto de retefuente.
        // - Otros regímenes: requiere que el concepto de retefuente esté seleccionado
        //   y que la base del contrato supere el umbral del concepto.
        $this->reteiva = 0;
        $isIvaResponsible = in_array($taxRegime, ['comun', 'gran_contribuyente', 'simple']);

        if ($isIvaResponsible && $iva > 0) {
            $reteivaConfig = RetentionConfig::getForSchoolYearConcept($this->schoolId, $fiscalYear, 'reteiva');

            if ($taxRegime === 'simple') {
                // Para Régimen Simple: ReteIVA aplica directamente (no requiere concepto de retefuente)
                if ($this->retentionApplies($reteivaConfig, $retentionContext) && $iva >= (float) $reteivaConfig->min_base) {
                    $rate = (float) $reteivaConfig->rate;
                    $this->reteiva = $this->roundRetention($iva * ($rate / 100));
                }
            } elseif ($this->retentionConcept && $retefuenteConfig && $reteivaConfig
                && $this->retentionApplies($retefuenteConfig, $retentionContext)
                && $this->retentionApplies($reteivaConfig, $retentionContext)
                && $contractSubtotal >= (float) $retefuenteConfig->min_base
                && $iva >= (float) $reteivaConfig->min_base
            ) {
                $rate = (float) $reteivaConfig->rate;
                $this->reteiva = $this->roundRetention($iva * ($rate / 100));
            }
        }

        $this->totalRetentionsDian = $this->retefuente + $this->reteiva;

        // ── OTROS IMPUESTOS (dinámico) ──
        $this->otherTaxesBreakdown = $this->calculateOtherTaxesBreakdown($subtotal, $retentionContext);
        $this->estampillaProdultoMayor = (float) ($this->otherTaxesBreakdown['estampilla_produlto_mayor'] ?? 0);
        $this->estampillaProcultura = (float) ($this->otherTaxesBreakdown['estampilla_procultura'] ?? 0);
        $this->estampillaProdeporte = (float) ($this->otherTaxesBreakdown['estampilla_prodeporte'] ?? 0);
        $this->retencionIca = (float) ($this->otherTaxesBreakdown['retencion_ica'] ?? 0);
        $this->otherTaxesTotal = array_sum($this->otherTaxesBreakdown);

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
        if (in_array($this->paymentType, ['direct', 'accounts_payable']) && $this->selectedSupplierId) {
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

        // Determinar si el proveedor factura electrónicamente
        $supplierInvoices = $this->supplierData['electronic_invoicing'] ?? true;

        // Validación base
        $rules = [
            'paymentDate' => 'required|date',
        ];
        $messages = [
            'paymentDate.required' => 'La fecha de pago es obligatoria.',
        ];

        // Los campos de factura/subtotal/total NO aplican para pago de impuestos (skipCdpRp)
        if (!$this->skipCdpRp) {
            $rules['paySubtotal'] = 'required|numeric|min:0.01';
            $rules['payIva']      = 'nullable|numeric|min:0';
            $rules['payTotal']    = 'required|numeric|min:0.01';
            $messages['paySubtotal.required'] = 'El subtotal es obligatorio.';
            $messages['paySubtotal.min']      = 'El subtotal debe ser mayor a 0.';
            $messages['payTotal.required']    = 'El total es obligatorio.';

            // Solo requerir factura si el proveedor factura electrónicamente
            if ($supplierInvoices) {
                $rules['invoiceDate']   = 'required|date';
                $rules['invoiceNumber'] = 'required|string|max:100';
                $messages['invoiceDate.required']   = 'La fecha de la factura es obligatoria.';
                $messages['invoiceNumber.required'] = 'El número de factura es obligatorio.';
            }
        }

        if ($this->canApplyRetencionIca && $this->applyRetencionIca) {
            $rules['retencionIcaPercentage'] = 'required|numeric|min:0.01|max:100';
            $messages['retencionIcaPercentage.required'] = 'Debe indicar el porcentaje de ReteICA.';
            $messages['retencionIcaPercentage.min'] = 'El porcentaje de ReteICA debe ser mayor a 0.';
            $messages['retencionIcaPercentage.max'] = 'El porcentaje de ReteICA no puede ser mayor a 100.';
        }

        if ($this->paymentType === 'contract') {
            $rules['selectedContractId'] = 'required|exists:contracts,id';
            $messages['selectedContractId.required'] = 'Debe seleccionar un contrato.';
        } else {
            $rules['selectedSupplierId']  = 'required|exists:suppliers,id';
            $rules['directDescription']   = 'required|string|min:5|max:1000';
            $messages['selectedSupplierId.required']  = 'Debe seleccionar un proveedor.';
            $messages['directDescription.required']   = 'La descripción del pago es obligatoria.';
            $messages['directDescription.min']        = 'La descripción debe tener al menos 5 caracteres.';
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

            // Validar que no exceda el saldo pendiente del contrato
            $remaining = $this->contractData['remaining'] ?? 0;
            if ((float) $this->payTotal > $remaining) {
                $formattedTotal = number_format($this->payTotal, 2, ',', '.');
                $formattedRemaining = number_format($remaining, 2, ',', '.');
                $this->dispatch('toast', message: "El monto (\${$formattedTotal}) excede el saldo pendiente (\${$formattedRemaining}).", type: 'error');
                return;
            }

            // ─── Validar saldo disponible en el presupuesto (apropiación) ───
            // Evita que los pagos acumulados superen el Budget.current_amount del rubro.
            $fmt = fn($v) => number_format((float) $v, 2, ',', '.');

            $distIdsToCheck = [];  // [expense_distribution_id => amount_to_pay]

            if ($this->paymentMode === 'single' && $this->selectedExpenseDistributionId) {
                $distIdsToCheck[(int) $this->selectedExpenseDistributionId] = (float) $this->payTotal;
            } elseif ($this->paymentMode === 'split') {
                foreach ($this->expenseLines as $line) {
                    $lineTotal = (float) ($line['total'] ?? 0);
                    if ($lineTotal <= 0 || empty($line['expense_distribution_id'])) continue;
                    $dId = (int) $line['expense_distribution_id'];
                    $distIdsToCheck[$dId] = ($distIdsToCheck[$dId] ?? 0) + $lineTotal;
                }
            }

            if (!empty($distIdsToCheck)) {
                $distributions = \App\Models\ExpenseDistribution::with('budget')
                    ->whereIn('id', array_keys($distIdsToCheck))
                    ->get()
                    ->keyBy('id');

                $budgetPaymentCache = []; // budget_id → total_ya_pagado

                foreach ($distIdsToCheck as $distId => $newAmount) {
                    $dist = $distributions[$distId] ?? null;
                    if (!$dist || !$dist->budget) continue;

                    $budget = $dist->budget;

                    // 1) Saldo restante del código de gasto (solo para pagos directos).
                    // Para pagos de contratos, el saldo del RP ya fue validado arriba
                    // ($this->contractData['remaining']). Aplicar aquí daría falsos negativos
                    // porque $convAmt usa el valor estimado de la convocatoria (no el RP real)
                    // y $alreadyPaidOnDist acumula pagos de todos los contratos de la misma distribución.
                    if ($this->paymentType !== 'contract') {
                        $convAmt = (float) (collect($this->expenseDistributions)->firstWhere('id', $distId)['convocatoria_amount'] ?? $dist->amount);
                        $alreadyPaidOnDist = \App\Models\PaymentOrderExpenseLine::where('expense_distribution_id', $distId)
                            ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['approved', 'paid']))
                            ->sum('total');
                        $remainingOnDist = $convAmt - (float) $alreadyPaidOnDist;

                        if ($newAmount > $remainingOnDist + 0.01) {
                            $codeName = $dist->expenseCode?->name ?? "distribución #{$distId}";
                            $this->dispatch('toast', message: "El pago para '{$codeName}' (\${$fmt($newAmount)}) excede el saldo pendiente del código de gasto (\${$fmt(max(0, $remainingOnDist))}).", type: 'error');
                            return;
                        }
                    }

                    // 2) Saldo disponible en el presupuesto (apropiación definitiva)
                    if (!isset($budgetPaymentCache[$budget->id])) {
                        $paidViaLines = (float) \App\Models\PaymentOrderExpenseLine::whereHas('expenseDistribution', fn($q) => $q->where('budget_id', $budget->id))
                            ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['approved', 'paid']))
                            ->sum('total');
                        // Solo sumar pagos directos que NO tienen expense_lines (skipCdpRp sin distribución),
                        // para evitar doble conteo con los que ya están sumados en $paidViaLines.
                        $paidDirect = (float) \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                            ->where('payment_type', 'direct')
                            ->where('budget_item_id', $budget->budget_item_id)
                            ->whereIn('status', ['approved', 'paid'])
                            ->whereDoesntHave('expenseLines')
                            ->sum('total');
                        $budgetPaymentCache[$budget->id] = $paidViaLines + $paidDirect;
                    }

                    // Agregar el nuevo pago al acumulado del presupuesto para este ciclo
                    $budgetPaymentCache[$budget->id] += $newAmount;
                    $remainingBudget = (float) $budget->current_amount - ($budgetPaymentCache[$budget->id] - $newAmount);

                    if ($newAmount > $remainingBudget + 0.01) {
                        $this->dispatch('toast', message: "El pago (\${$fmt($newAmount)}) excede el saldo disponible del presupuesto (\${$fmt(max(0, $remainingBudget))}).", type: 'error');
                        return;
                    }
                }
            }
            // ────────────────────────────────────────────────────────────────
        }

        // Validaciones específicas para pago directo sin CDP/RP
        if ($this->paymentType === 'direct' && $this->skipCdpRp) {
            $this->recalculateSkipTotals();

            if (empty($this->skipTaxLines)) {
                $this->dispatch('toast', message: 'Debe seleccionar al menos un impuesto a pagar.', type: 'error');
                return;
            }

            foreach ($this->skipTaxLines as $tl) {
                if ((float) ($tl['amount'] ?? 0) <= 0) {
                    $this->dispatch('toast', message: 'Todos los impuestos seleccionados deben tener un monto mayor a 0.', type: 'error');
                    return;
                }
            }

            if (empty($this->skipBankLines)) {
                $this->dispatch('toast', message: 'Debe agregar al menos una cuenta bancaria de egreso.', type: 'error');
                return;
            }

            foreach ($this->skipBankLines as $bl) {
                if (empty($bl['bank_account_id'])) {
                    $this->dispatch('toast', message: 'Todas las líneas bancarias deben tener una cuenta seleccionada.', type: 'error');
                    return;
                }
                if ((float) ($bl['amount'] ?? 0) <= 0) {
                    $this->dispatch('toast', message: 'Todos los montos bancarios deben ser mayores a 0.', type: 'error');
                    return;
                }
            }

            if (abs($this->skipTaxTotal - $this->skipBankTotal) > 0.01) {
                $this->dispatch('toast', message: 'El total de impuestos ($' . number_format($this->skipTaxTotal, 2, ',', '.') . ') debe ser igual al total de las cuentas bancarias ($' . number_format($this->skipBankTotal, 2, ',', '.') . ').', type: 'error');
                return;
            }
        }

        // Validaciones específicas para Cuentas por Pagar
        if ($this->paymentType === 'accounts_payable') {
            if (empty($this->skipBankLines)) {
                $this->dispatch('toast', message: 'Debe agregar al menos una cuenta bancaria de egreso.', type: 'error');
                return;
            }

            foreach ($this->skipBankLines as $bl) {
                if (empty($bl['bank_account_id'])) {
                    $this->dispatch('toast', message: 'Todas las líneas bancarias deben tener una cuenta seleccionada.', type: 'error');
                    return;
                }
                if ((float) ($bl['amount'] ?? 0) <= 0) {
                    $this->dispatch('toast', message: 'Todos los montos bancarios deben ser mayores a 0.', type: 'error');
                    return;
                }
            }

            $this->recalculateSkipTotals();
            if (abs($this->skipBankTotal - (float) $this->netPayment) > 0.01) {
                $this->dispatch('toast', message: 'El total de cuentas bancarias ($' . number_format($this->skipBankTotal, 2, ',', '.') . ') debe ser igual al Neto a Pagar ($' . number_format($this->netPayment, 2, ',', '.') . ').', type: 'error');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;

            // Para pago directo sin CDP/RP, los totales vienen de las líneas de impuestos
            $skipTotal = $this->skipCdpRp ? $this->skipTaxTotal : 0;

            $paymentData = [
                'school_id'                  => $this->schoolId,
                'payment_type'               => $this->paymentType,
                'payment_number'             => PaymentOrder::getNextPaymentNumber($this->schoolId, $year),
                'fiscal_year'                => $year,
                'invoice_number'             => $this->invoiceNumber ?: null,
                'document_support_number'    => null,
                'invoice_date'               => $this->invoiceDate ?: null,
                'payment_date'               => $this->paymentDate,
                'is_full_payment'            => in_array($this->paymentType, ['direct', 'accounts_payable']) ? true : $this->isFullPayment,
                'subtotal'                   => $this->skipCdpRp ? $skipTotal : (float) $this->paySubtotal,
                'iva'                        => $this->skipCdpRp ? 0 : (float) ($this->payIva ?? 0),
                'total'                      => $this->skipCdpRp ? $skipTotal : (float) $this->payTotal,
                'retention_concept'          => $this->skipCdpRp ? null : ($this->paymentMode === 'single' ? ($this->retentionConcept ?: null) : null),
                'supplier_declares_rent'     => $this->skipCdpRp ? false : ($this->paymentMode === 'single' ? $this->supplierDeclaresRent : false),
                'retention_percentage'       => $this->skipCdpRp ? 0 : ($this->paymentMode === 'single' ? $this->retentionPercentage : 0),
                'retefuente'                 => $this->skipCdpRp ? 0 : $this->retefuente,
                'reteiva'                    => $this->skipCdpRp ? 0 : $this->reteiva,
                'estampilla_produlto_mayor'  => $this->skipCdpRp ? 0 : $this->estampillaProdultoMayor,
                'estampilla_procultura'      => $this->skipCdpRp ? 0 : $this->estampillaProcultura,
                'estampilla_prodeporte'      => $this->skipCdpRp ? 0 : $this->estampillaProdeporte,
                'retencion_ica'              => $this->skipCdpRp ? 0 : $this->retencionIca,
                'apply_retencion_ica'        => $this->skipCdpRp ? false : ((bool) $this->applyRetencionIca),
                'retencion_ica_percentage'   => $this->skipCdpRp ? 0 : ((float) $this->retencionIcaPercentage),
                'other_taxes_total'          => $this->skipCdpRp ? 0 : $this->otherTaxesTotal,
                'other_taxes_breakdown'      => $this->skipCdpRp ? null : $this->otherTaxesBreakdown,
                'total_retentions'           => $this->skipCdpRp ? 0 : $this->totalRetentions,
                'net_payment'                => $this->skipCdpRp ? $skipTotal : $this->netPayment,
                'observations'               => $this->observations ?: null,
                'supplier_bank_name'         => null,
                'supplier_account_type'      => null,
                'supplier_account_number'    => null,
                'status'                     => 'draft',
                'created_by'                 => auth()->id(),
            ];

            if ($this->paymentType === 'contract') {
                $paymentData['contract_id'] = $this->selectedContractId;
            } elseif ($this->paymentType === 'accounts_payable') {
                // Cuentas por Pagar: proveedor con retenciones, sin CDP/RP
                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
            } elseif ($this->skipCdpRp) {
                // Pago directo sin CDP/RP (retenciones, impuestos)
                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
            } else {
                // Pago directo con CDP/RP — usar asignaciones de códigos de gasto
                $allocations = !empty($this->directExpenseAllocations) ? $this->directExpenseAllocations : [];

                // Fallback al flujo antiguo si hay directSelectedSources (compatibilidad)
                if (empty($allocations) && !empty($this->directSelectedSources)) {
                    $allocations = collect($this->directSelectedSources)->map(fn($src) => [
                        'expense_code_id' => null,
                        'budget_item_id' => null,
                        'funding_source_id' => $src['id'],
                        'budget_id' => $src['budget_id'],
                        'available' => $src['available'],
                        'amount' => $src['amount'],
                        'bank_id' => $src['bank_id'] ?? '',
                        'bank_account_id' => $src['bank_account_id'] ?? '',
                    ])->toArray();
                }

                if (empty($allocations)) {
                    throw new \Exception('Debe seleccionar al menos un código de gasto y asignar montos.');
                }

                $totalSources = 0;
                foreach ($allocations as $alloc) {
                    $amt = (float) ($alloc['amount'] ?? 0);
                    if ($amt <= 0) {
                        throw new \Exception('Todas las asignaciones deben tener un monto mayor a 0.');
                    }
                    if ($amt > (float) ($alloc['available'] ?? 0)) {
                        throw new \Exception('El monto excede el saldo disponible de una fuente.');
                    }
                    $totalSources += $amt;
                }

                // Usar el budget_item_id de la primera asignación para el CDP
                $firstBudgetItemId = $allocations[0]['budget_item_id'] ?? $this->directBudgetItemId;

                // Crear CDP
                $cdp = Cdp::create([
                    'school_id'      => $this->schoolId,
                    'convocatoria_id'=> null,
                    'cdp_number'     => Cdp::getNextCdpNumber($this->schoolId, $year),
                    'fiscal_year'    => $year,
                    'budget_item_id' => $firstBudgetItemId,
                    'total_amount'   => $totalSources,
                    'status'         => 'used',
                    'created_by'     => auth()->id(),
                ]);

                foreach ($allocations as $alloc) {
                    // Para pagos directos, guardar el saldo disponible del RUBRO (código de gasto)
                    // en lugar del saldo de la fuente de financiación, para que el CDP sea correcto.
                    // En este punto PaymentOrderExpenseLine aún no existe → saldo pre-pago.
                    $balance = 0;
                    if (!empty($alloc['budget_id']) && !empty($alloc['expense_code_id'])) {
                        $dist = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
                            ->where('budget_id', $alloc['budget_id'])
                            ->where('expense_code_id', $alloc['expense_code_id'])
                            ->first();
                        $balance = $dist ? (float) $dist->available_balance : 0;
                    }
                    if ($balance <= 0) {
                        $source = FundingSource::find($alloc['funding_source_id']);
                        $balance = $source ? $source->getAvailableBalanceForYear($year, $this->schoolId) : 0;
                    }

                    CdpFundingSource::create([
                        'cdp_id'                      => $cdp->id,
                        'funding_source_id'           => $alloc['funding_source_id'],
                        'budget_id'                   => $alloc['budget_id'],
                        'amount'                      => (float) $alloc['amount'],
                        'available_balance_at_creation'=> $balance,
                    ]);
                }

                // Crear RP
                $rp = ContractRp::create([
                    'contract_id'  => null,
                    'cdp_id'       => $cdp->id,
                    'rp_number'    => ContractRp::getNextRpNumber($this->schoolId, $year),
                    'fiscal_year'  => $year,
                    'total_amount' => $totalSources,
                    'status'       => 'active',
                    'created_by'   => auth()->id(),
                ]);

                foreach ($allocations as $alloc) {
                    RpFundingSource::create([
                        'contract_rp_id'    => $rp->id,
                        'funding_source_id' => $alloc['funding_source_id'],
                        'budget_id'         => $alloc['budget_id'],
                        'amount'            => (float) $alloc['amount'],
                        'bank_id'           => !empty($alloc['bank_id']) ? $alloc['bank_id'] : null,
                        'bank_account_id'   => !empty($alloc['bank_account_id']) ? $alloc['bank_account_id'] : null,
                    ]);
                }

                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
                $paymentData['cdp_id'] = $cdp->id;
                $paymentData['contract_rp_id'] = $rp->id;
                $paymentData['budget_item_id'] = $firstBudgetItemId ?: null;
            }

            $paymentOrder = PaymentOrder::create($paymentData);

            // Si el proveedor NO factura electrónicamente, asignar número de documento soporte
            $resolvedSupplier = null;
            if ($this->paymentType === 'contract' && $this->selectedContractId) {
                $resolvedSupplier = Contract::find($this->selectedContractId)?->supplier;
            } elseif ($this->selectedSupplierId) {
                $resolvedSupplier = Supplier::find($this->selectedSupplierId);
            }

            if ($resolvedSupplier && !$resolvedSupplier->electronic_invoicing) {
                $docSupportNumber = PaymentOrder::getNextDocumentSupportNumber($this->schoolId);
                $paymentOrder->update(['document_support_number' => (string) $docSupportNumber]);
            }

            // Para pagos directos con códigos de gasto, crear líneas de gasto
            if ($this->paymentType === 'direct' && !empty($this->directExpenseAllocations)) {
                foreach ($this->directExpenseAllocations as $alloc) {
                    if (empty($alloc['expense_code_id']) || empty($alloc['budget_id'])) continue;

                    // Buscar la distribución de gasto correspondiente
                    $dist = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
                        ->where('budget_id', $alloc['budget_id'])
                        ->whereHas('expenseCode', fn($q) => $q->where('id', $alloc['expense_code_id']))
                        ->first();

                    if ($dist) {
                        $lineTotal = (float) ($alloc['amount'] ?? 0);
                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $paymentOrder->id,
                            'expense_distribution_id' => $dist->id,
                            'expense_code_id'         => $alloc['expense_code_id'],
                            'subtotal'                => $lineTotal,
                            'iva'                     => 0,
                            'total'                   => $lineTotal,
                            'retefuente'              => 0,
                            'reteiva'                 => 0,
                            'estampilla_produlto_mayor' => 0,
                            'estampilla_procultura'   => 0,
                            'estampilla_prodeporte'   => 0,
                            'retencion_ica'           => 0,
                            'total_retentions'        => 0,
                            'net_payment'             => $lineTotal,
                        ]);
                    }
                }
            }

            // Guardar snapshot de cuenta bancaria seleccionada (flujos con proveedor)
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

            // Guardar líneas de impuestos y bancarias (pago directo sin CDP/RP)
            if ($this->paymentType === 'direct' && $this->skipCdpRp) {
                foreach ($this->skipTaxLines as $tl) {
                    \App\Models\PaymentOrderTaxLine::create([
                        'payment_order_id' => $paymentOrder->id,
                        'tax_type'         => $tl['tax_type'],
                        'amount'           => (float) $tl['amount'],
                    ]);
                }
                foreach ($this->skipBankLines as $bl) {
                    \App\Models\PaymentOrderBankLine::create([
                        'payment_order_id' => $paymentOrder->id,
                        'bank_account_id'  => (int) $bl['bank_account_id'],
                        'amount'           => (float) $bl['amount'],
                    ]);
                }
            }

            // Guardar líneas bancarias de egreso para Cuentas por Pagar
            if ($this->paymentType === 'accounts_payable') {
                foreach ($this->skipBankLines as $bl) {
                    \App\Models\PaymentOrderBankLine::create([
                        'payment_order_id' => $paymentOrder->id,
                        'bank_account_id'  => (int) $bl['bank_account_id'],
                        'amount'           => (float) $bl['amount'],
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
                            'estampilla_prodeporte'   => $this->estampillaProdeporte,
                            'retencion_ica'           => $this->retencionIca,
                            'other_taxes_breakdown'   => $this->otherTaxesBreakdown,
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
                            'estampilla_prodeporte'   => (float) ($line['estampilla_prodeporte'] ?? 0),
                            'retencion_ica'           => (float) ($line['retencion_ica'] ?? 0),
                            'other_taxes_breakdown'   => is_array($line['other_taxes_breakdown'] ?? null) ? $line['other_taxes_breakdown'] : null,
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

    // ══════════════════════════════════════════════════════════
    // EDICIÓN
    // ══════════════════════════════════════════════════════════

    public function editPaymentOrder($id)
    {
        if (!auth()->user()->isAdmin()) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar órdenes de pago.', type: 'error');
            return;
        }

        $po = PaymentOrder::with([
            'expenseLines.expenseDistribution.budget.fundingSource', 
            'taxLines', 
            'bankLines',
            'contract'
        ])->forSchool($this->schoolId)->findOrFail($id);

        $this->paymentOrderId = $po->id;
        $this->resetCreateForm();
        
        $this->paymentType = $po->payment_type;
        $this->invoiceNumber = $po->invoice_number;
        $this->invoiceDate = $po->invoice_date ? $po->invoice_date->format('Y-m-d') : '';
        $this->paymentDate = $po->payment_date ? $po->payment_date->format('Y-m-d') : '';
        $this->isFullPayment = $po->is_full_payment;
        $this->paySubtotal = $po->subtotal;
        $this->payIva = $po->iva;
        $this->payTotal = $po->total;
        $this->retentionConcept = $po->retention_concept;
        $this->supplierDeclaresRent = $po->supplier_declares_rent;
        $this->retentionPercentage = $po->retention_percentage;
        $this->retefuente = $po->retefuente;
        $this->reteiva = $po->reteiva;
        $this->estampillaProdultoMayor = $po->estampilla_produlto_mayor;
        $this->estampillaProcultura = $po->estampilla_procultura;
        $this->estampillaProdeporte = $po->estampilla_prodeporte;
        $this->retencionIca = $po->retencion_ica;
        $this->applyRetencionIca = (bool) ($po->apply_retencion_ica ?? false);
        $this->retencionIcaPercentage = (float) ($po->retencion_ica_percentage ?? 0);
        if (!$this->applyRetencionIca && (float) $this->retencionIca > 0 && (float) $po->subtotal > 0) {
            $this->applyRetencionIca = true;
            $this->retencionIcaPercentage = ((float) $this->retencionIca / (float) $po->subtotal) * 100;
        }
        $this->otherTaxesTotal = $po->other_taxes_total;
        $this->otherTaxesBreakdown = $po->other_taxes_breakdown ?: [];
        $this->totalRetentions = $po->total_retentions;
        $this->netPayment = $po->net_payment;
        $this->observations = $po->observations;

        if ($this->paymentType === 'contract') {
            $this->loadContracts();
            $this->selectedContractId = $po->contract_id;
            $this->onContractSelected();

            if ($po->expenseLines->count() == 1 && abs($po->expenseLines->first()->total - $po->total) < 0.01) {
                $this->paymentMode = 'single';
                $this->selectedExpenseDistributionId = $po->expenseLines->first()->expense_distribution_id;
            } else {
                $this->paymentMode = 'split';
                $this->initializeExpenseLines();
                foreach ($this->expenseLines as &$line) {
                    $savedLine = $po->expenseLines->firstWhere('expense_distribution_id', $line['expense_distribution_id']);
                    if ($savedLine) {
                        $line['subtotal'] = $savedLine->subtotal;
                        $line['iva'] = $savedLine->iva;
                        $line['total'] = $savedLine->total;
                    } else {
                        $line['subtotal'] = 0;
                        $line['iva'] = 0;
                        $line['total'] = 0;
                    }
                }
                foreach ($this->expenseLines as $i => $line) {
                    $this->calculateLineRetentions($i);
                }
                $this->recalculateFromLines();
            }
        } else {
            $this->loadSuppliers();
            $this->selectedSupplierId = $po->supplier_id;
            $this->directDescription = $po->description;
            $this->onSupplierSelected();

            if ($this->paymentType === 'accounts_payable') {
                $this->skipBankLines = $po->bankLines->map(function ($bl) {
                    return [
                        'bank_id' => $bl->bankAccount->bank_id ?? '',
                        'bank_account_id' => $bl->bank_account_id,
                        'amount' => $bl->amount,
                    ];
                })->toArray();
                $this->recalculateSkipTotals();
            } elseif ($this->paymentType === 'direct') {
                if (!$po->cdp_id && !$po->contract_rp_id && $po->taxLines->count() > 0) {
                    $this->skipCdpRp = true;
                    $this->skipTaxLines = $po->taxLines->map(function ($tl) {
                        return [
                            'tax_type' => $tl->tax_type,
                            'amount' => $tl->amount,
                        ];
                    })->toArray();
                    $this->skipBankLines = $po->bankLines->map(function ($bl) {
                        return [
                            'bank_id' => $bl->bankAccount->bank_id ?? '',
                            'bank_account_id' => $bl->bank_account_id,
                            'amount' => $bl->amount,
                        ];
                    })->toArray();
                    $this->recalculateSkipTotals();
                } else {
                    $this->skipCdpRp = false;
                    $this->directBudgetItemId = $po->budget_item_id;
                    $this->updatedDirectBudgetItemId($po->budget_item_id);

                    $allocations = [];
                    foreach ($po->expenseLines as $line) {
                        if ($line->expenseDistribution) {
                            $bdg = $line->expenseDistribution->budget;
                            // Intentar deducir banco desde RpFundingSource
                            $rpSource = \App\Models\RpFundingSource::where('contract_rp_id', $po->contract_rp_id)
                                ->where('funding_source_id', $bdg->funding_source_id)
                                ->where('budget_id', $bdg->id)
                                ->first();

                            $allocations[] = [
                                'expense_code_id' => $line->expense_code_id,
                                'budget_item_id' => $bdg->budget_item_id,
                                'funding_source_id' => $bdg->funding_source_id,
                                'funding_source_name' => $bdg->fundingSource->code . ' - ' . $bdg->fundingSource->name,
                                'budget_id' => $bdg->id,
                                'available' => $line->total + max(0, $line->expenseDistribution->available_balance),
                                'amount' => $line->total,
                                'bank_id' => $rpSource ? $rpSource->bank_id : '',
                                'bank_account_id' => $rpSource ? $rpSource->bank_account_id : '',
                            ];
                        }
                    }
                    $this->directExpenseAllocations = $allocations;
                    $this->loadDirectExpenseCodes();
                    
                    // Asegurar que directSelectedExpenseCodes tenga los elementos
                    foreach ($allocations as $alloc) {
                        $exists = collect($this->directSelectedExpenseCodes)->contains('id', $alloc['expense_code_id']);
                        if (!$exists) {
                            $ec = collect($this->directExpenseCodes)->firstWhere('id', $alloc['expense_code_id']);
                            if ($ec) {
                                $this->directSelectedExpenseCodes[] = $ec;
                            }
                        }
                    }
                }
            }
        }

        if ($po->supplier_account_number) {
            $matchingAcc = collect($this->supplierBankAccounts)->firstWhere('account_number', $po->supplier_account_number);
            if ($matchingAcc) {
                $this->selectedBankAccountId = $matchingAcc['id'];
            }
        }

        $this->currentView = 'edit';
    }

    public function updatePaymentOrder()
    {
        if (!auth()->user()->isAdmin()) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar órdenes de pago.', type: 'error');
            return;
        }

        $oldPo = PaymentOrder::findOrFail($this->paymentOrderId);
        $supplierInvoices = $this->supplierData['electronic_invoicing'] ?? true;

        $rules = [ 'paymentDate' => 'required|date' ];
        $messages = [ 'paymentDate.required' => 'La fecha de pago es obligatoria.' ];

        if (!$this->skipCdpRp) {
            $rules['paySubtotal'] = 'required|numeric|min:0.01';
            $rules['payIva']      = 'nullable|numeric|min:0';
            $rules['payTotal']    = 'required|numeric|min:0.01';
            $messages['paySubtotal.required'] = 'El subtotal es obligatorio.';
            $messages['paySubtotal.min']      = 'El subtotal debe ser mayor a 0.';
            $messages['payTotal.required']    = 'El total es obligatorio.';

            if ($supplierInvoices) {
                $rules['invoiceDate']   = 'required|date';
                $rules['invoiceNumber'] = 'required|string|max:100';
                $messages['invoiceDate.required']   = 'La fecha de la factura es obligatoria.';
                $messages['invoiceNumber.required'] = 'El número de factura es obligatorio.';
            }
        }

        if ($this->canApplyRetencionIca && $this->applyRetencionIca) {
            $rules['retencionIcaPercentage'] = 'required|numeric|min:0.01|max:100';
            $messages['retencionIcaPercentage.required'] = 'Debe indicar el porcentaje de ReteICA.';
            $messages['retencionIcaPercentage.min'] = 'El porcentaje de ReteICA debe ser mayor a 0.';
            $messages['retencionIcaPercentage.max'] = 'El porcentaje de ReteICA no puede ser mayor a 100.';
        }

        if ($this->paymentType === 'contract') {
            $rules['selectedContractId'] = 'required|exists:contracts,id';
            $messages['selectedContractId.required'] = 'Debe seleccionar un contrato.';
        } else {
            $rules['selectedSupplierId']  = 'required|exists:suppliers,id';
            $rules['directDescription']   = 'required|string|min:5|max:1000';
            $messages['selectedSupplierId.required']  = 'Debe seleccionar un proveedor.';
            $messages['directDescription.required']   = 'La descripción del pago es obligatoria.';
        }

        $this->validate($rules, $messages);

        // Validaciones (abreviadas pero mantienen la esencia)
        if ($this->paymentType === 'contract') {
            $remaining = $this->contractData['remaining'] ?? 0;
            // Al editar, el saldo remaining excluye el monto previo de esta misma orden, 
            // así que le sumamos el total anterior para la validación si estamos editando el mismo contrato.
            if ($oldPo->contract_id == $this->selectedContractId) {
                $remaining += $oldPo->total;
            }
            if ((float) $this->payTotal > $remaining) {
                $this->dispatch('toast', message: "El monto excede el saldo pendiente.", type: 'error');
                return;
            }
        }

        if ($this->paymentType === 'direct' && $this->skipCdpRp) {
            $this->recalculateSkipTotals();
            if (abs($this->skipTaxTotal - $this->skipBankTotal) > 0.01) {
                $this->dispatch('toast', message: 'El total de impuestos debe ser igual al total de bancos.', type: 'error');
                return;
            }
        }

        if ($this->paymentType === 'accounts_payable') {
            $this->recalculateSkipTotals();
            if (abs($this->skipBankTotal - (float) $this->netPayment) > 0.01) {
                $this->dispatch('toast', message: 'El total de bancos debe ser igual al Neto a Pagar.', type: 'error');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;
            $skipTotal = $this->skipCdpRp ? $this->skipTaxTotal : 0;

            // Manejo de CDP y RP antiguos
            $oldCdpNumber = null;
            $oldRpNumber = null;
            if ($oldPo->payment_type === 'direct') {
                if ($oldPo->cdp_id) {
                    $oldCdp = Cdp::find($oldPo->cdp_id);
                    if ($oldCdp) {
                        $oldCdpNumber = $oldCdp->cdp_number;
                        $oldCdp->delete();
                    }
                }
                if ($oldPo->contract_rp_id) {
                    $oldRp = ContractRp::find($oldPo->contract_rp_id);
                    if ($oldRp) {
                        $oldRpNumber = $oldRp->rp_number;
                        $oldRp->delete();
                    }
                }
            }

            // Limpiar líneas antiguas
            \App\Models\PaymentOrderExpenseLine::where('payment_order_id', $oldPo->id)->delete();
            \App\Models\PaymentOrderTaxLine::where('payment_order_id', $oldPo->id)->delete();
            \App\Models\PaymentOrderBankLine::where('payment_order_id', $oldPo->id)->delete();

            $paymentData = [
                'payment_type'               => $this->paymentType,
                'invoice_number'             => $this->invoiceNumber ?: null,
                'invoice_date'               => $this->invoiceDate ?: null,
                'payment_date'               => $this->paymentDate,
                'is_full_payment'            => in_array($this->paymentType, ['direct', 'accounts_payable']) ? true : $this->isFullPayment,
                'subtotal'                   => $this->skipCdpRp ? $skipTotal : (float) $this->paySubtotal,
                'iva'                        => $this->skipCdpRp ? 0 : (float) ($this->payIva ?? 0),
                'total'                      => $this->skipCdpRp ? $skipTotal : (float) $this->payTotal,
                'retention_concept'          => $this->skipCdpRp ? null : ($this->paymentMode === 'single' ? ($this->retentionConcept ?: null) : null),
                'supplier_declares_rent'     => $this->skipCdpRp ? false : ($this->paymentMode === 'single' ? $this->supplierDeclaresRent : false),
                'retention_percentage'       => $this->skipCdpRp ? 0 : ($this->paymentMode === 'single' ? $this->retentionPercentage : 0),
                'retefuente'                 => $this->skipCdpRp ? 0 : $this->retefuente,
                'reteiva'                    => $this->skipCdpRp ? 0 : $this->reteiva,
                'estampilla_produlto_mayor'  => $this->skipCdpRp ? 0 : $this->estampillaProdultoMayor,
                'estampilla_procultura'      => $this->skipCdpRp ? 0 : $this->estampillaProcultura,
                'estampilla_prodeporte'      => $this->skipCdpRp ? 0 : $this->estampillaProdeporte,
                'retencion_ica'              => $this->skipCdpRp ? 0 : $this->retencionIca,
                'apply_retencion_ica'        => $this->skipCdpRp ? false : ((bool) $this->applyRetencionIca),
                'retencion_ica_percentage'   => $this->skipCdpRp ? 0 : ((float) $this->retencionIcaPercentage),
                'other_taxes_total'          => $this->skipCdpRp ? 0 : $this->otherTaxesTotal,
                'other_taxes_breakdown'      => $this->skipCdpRp ? null : $this->otherTaxesBreakdown,
                'total_retentions'           => $this->skipCdpRp ? 0 : $this->totalRetentions,
                'net_payment'                => $this->skipCdpRp ? $skipTotal : $this->netPayment,
                'observations'               => $this->observations ?: null,
                'supplier_bank_name'         => null,
                'supplier_account_type'      => null,
                'supplier_account_number'    => null,
                'status'                     => 'draft', // SIEMPRE BORRADOR AL EDITAR
                'contract_id'                => null,
                'supplier_id'                => null,
                'cdp_id'                     => null,
                'contract_rp_id'             => null,
                'budget_item_id'             => null,
                'description'                => null,
            ];

            if ($this->paymentType === 'contract') {
                $paymentData['contract_id'] = $this->selectedContractId;
            } elseif ($this->paymentType === 'accounts_payable') {
                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
            } elseif ($this->skipCdpRp) {
                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
            } else {
                $allocations = !empty($this->directExpenseAllocations) ? $this->directExpenseAllocations : [];
                if (empty($allocations)) throw new \Exception('Debe seleccionar al menos un código de gasto.');

                $totalSources = 0;
                foreach ($allocations as $alloc) {
                    $totalSources += (float) ($alloc['amount'] ?? 0);
                }

                $firstBudgetItemId = $allocations[0]['budget_item_id'] ?? $this->directBudgetItemId;

                // Reutilizamos el número si venía de un pago directo, o buscamos huecos
                $cdpNumberToUse = $oldCdpNumber ?: Cdp::getNextCdpNumber($this->schoolId, $year);
                $cdp = Cdp::create([
                    'school_id'      => $this->schoolId,
                    'cdp_number'     => $cdpNumberToUse,
                    'fiscal_year'    => $year,
                    'budget_item_id' => $firstBudgetItemId,
                    'total_amount'   => $totalSources,
                    'status'         => 'used',
                    'created_by'     => auth()->id(),
                ]);

                foreach ($allocations as $alloc) {
                    $balance = 0;
                    if (!empty($alloc['budget_id']) && !empty($alloc['expense_code_id'])) {
                        $dist = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
                            ->where('budget_id', $alloc['budget_id'])
                            ->where('expense_code_id', $alloc['expense_code_id'])
                            ->first();
                        $balance = $dist ? (float) $dist->available_balance : 0;
                    }
                    CdpFundingSource::create([
                        'cdp_id'                      => $cdp->id,
                        'funding_source_id'           => $alloc['funding_source_id'],
                        'budget_id'                   => $alloc['budget_id'],
                        'amount'                      => (float) $alloc['amount'],
                        'available_balance_at_creation'=> $balance,
                    ]);
                }

                $rpNumberToUse = $oldRpNumber ?: ContractRp::getNextRpNumber($this->schoolId, $year);
                $rp = ContractRp::create([
                    'cdp_id'       => $cdp->id,
                    'rp_number'    => $rpNumberToUse,
                    'fiscal_year'  => $year,
                    'total_amount' => $totalSources,
                    'status'       => 'active',
                    'created_by'   => auth()->id(),
                ]);

                foreach ($allocations as $alloc) {
                    RpFundingSource::create([
                        'contract_rp_id'    => $rp->id,
                        'funding_source_id' => $alloc['funding_source_id'],
                        'budget_id'         => $alloc['budget_id'],
                        'amount'            => (float) $alloc['amount'],
                        'bank_id'           => !empty($alloc['bank_id']) ? $alloc['bank_id'] : null,
                        'bank_account_id'   => !empty($alloc['bank_account_id']) ? $alloc['bank_account_id'] : null,
                    ]);
                }

                $paymentData['supplier_id'] = $this->selectedSupplierId;
                $paymentData['description'] = $this->directDescription;
                $paymentData['cdp_id'] = $cdp->id;
                $paymentData['contract_rp_id'] = $rp->id;
                $paymentData['budget_item_id'] = $firstBudgetItemId ?: null;
            }

            $oldPo->update($paymentData);

            if ($this->paymentType === 'direct' && !empty($this->directExpenseAllocations)) {
                foreach ($this->directExpenseAllocations as $alloc) {
                    if (empty($alloc['expense_code_id']) || empty($alloc['budget_id'])) continue;
                    $dist = \App\Models\ExpenseDistribution::where('school_id', $this->schoolId)
                        ->where('budget_id', $alloc['budget_id'])
                        ->whereHas('expenseCode', fn($q) => $q->where('id', $alloc['expense_code_id']))
                        ->first();

                    if ($dist) {
                        $lineTotal = (float) ($alloc['amount'] ?? 0);
                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $oldPo->id,
                            'expense_distribution_id' => $dist->id,
                            'expense_code_id'         => $alloc['expense_code_id'],
                            'subtotal'                => $lineTotal,
                            'iva'                     => 0,
                            'total'                   => $lineTotal,
                            'retefuente'              => 0,
                            'reteiva'                 => 0,
                            'estampilla_produlto_mayor' => 0,
                            'estampilla_procultura'   => 0,
                            'estampilla_prodeporte'   => 0,
                            'retencion_ica'           => 0,
                            'total_retentions'        => 0,
                            'net_payment'             => $lineTotal,
                        ]);
                    }
                }
            }

            if ($this->selectedBankAccountId) {
                $bankAccount = SupplierBankAccount::find($this->selectedBankAccountId);
                if ($bankAccount) {
                    $oldPo->update([
                        'supplier_bank_name'      => $bankAccount->bank_name,
                        'supplier_account_type'   => $bankAccount->account_type_name,
                        'supplier_account_number' => $bankAccount->account_number,
                    ]);
                }
            }

            if ($this->paymentType === 'direct' && $this->skipCdpRp) {
                foreach ($this->skipTaxLines as $tl) {
                    \App\Models\PaymentOrderTaxLine::create([
                        'payment_order_id' => $oldPo->id,
                        'tax_type'         => $tl['tax_type'],
                        'amount'           => (float) $tl['amount'],
                    ]);
                }
                foreach ($this->skipBankLines as $bl) {
                    \App\Models\PaymentOrderBankLine::create([
                        'payment_order_id' => $oldPo->id,
                        'bank_account_id'  => (int) $bl['bank_account_id'],
                        'amount'           => (float) $bl['amount'],
                    ]);
                }
            }

            if ($this->paymentType === 'accounts_payable') {
                foreach ($this->skipBankLines as $bl) {
                    \App\Models\PaymentOrderBankLine::create([
                        'payment_order_id' => $oldPo->id,
                        'bank_account_id'  => (int) $bl['bank_account_id'],
                        'amount'           => (float) $bl['amount'],
                    ]);
                }
            }

            if ($this->paymentType === 'contract' && !empty($this->expenseDistributions)) {
                if ($this->paymentMode === 'single' && $this->selectedExpenseDistributionId) {
                    $dist = collect($this->expenseDistributions)->firstWhere('id', $this->selectedExpenseDistributionId);
                    if ($dist) {
                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $oldPo->id,
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
                            'estampilla_prodeporte'   => $this->estampillaProdeporte,
                            'retencion_ica'           => $this->retencionIca,
                            'other_taxes_breakdown'   => $this->otherTaxesBreakdown,
                            'total_retentions'        => $this->totalRetentions,
                            'net_payment'             => $this->netPayment,
                        ]);
                    }
                } elseif ($this->paymentMode === 'split') {
                    foreach ($this->expenseLines as $line) {
                        $lineSubtotal = (float) ($line['subtotal'] ?? 0);
                        if ($lineSubtotal <= 0 && (float) ($line['iva'] ?? 0) <= 0) continue;
                        PaymentOrderExpenseLine::create([
                            'payment_order_id'        => $oldPo->id,
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
                            'estampilla_prodeporte'   => (float) ($line['estampilla_prodeporte'] ?? 0),
                            'retencion_ica'           => (float) ($line['retencion_ica'] ?? 0),
                            'other_taxes_breakdown'   => is_array($line['other_taxes_breakdown'] ?? null) ? $line['other_taxes_breakdown'] : null,
                            'total_retentions'        => (float) ($line['total_retentions'] ?? 0),
                            'net_payment'             => (float) ($line['net_payment'] ?? 0),
                        ]);
                    }
                }
            }

            DB::commit();
            $this->dispatch('toast', message: 'Orden de pago actualizada exitosamente y regresada a estado Borrador.', type: 'success');
            $this->viewDetail($oldPo->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al actualizar orden de pago: ' . $e->getMessage(), type: 'error');
        }
    }


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
        $this->applyRetencionIca = false;
        $this->retencionIcaPercentage = 0;
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
        $this->directBudgetItems = [];
        $this->directBudgetItemId = '';
        $this->directFundingSources = [];
        $this->directSelectedSources = [];
        $this->directExpenseCodes = [];
        $this->directSelectedExpenseCodes = [];
        $this->directExpenseAllocations = [];
        // Impuestos / bancos (skipCdpRp)
        $this->skipCdpRp = false;
        $this->skipTaxLines = [];
        $this->skipTaxTotal = 0;
        $this->skipBankLines = [];
        $this->skipBankTotal = 0;
        $this->skipBanks = [];
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
            'certificado_cdp' => false,
            'certificado_rp' => false,
            'comprobante_contabilidad' => false,
            'certificado_tesoreria' => false,
            'comprobante_egreso_impuestos' => false,
            'resolucion_pago_impuestos' => false,
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

        if (!empty($selected['certificado_cdp'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.certificado-cdp.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['certificado_rp'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.certificado-rp.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['comprobante_contabilidad'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.comprobante-contabilidad.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['certificado_tesoreria'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.certificado-tesoreria.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['comprobante_egreso_impuestos'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.comprobante-egreso-impuestos.pdf', $this->paymentOrderId));
        }

        if (!empty($selected['resolucion_pago_impuestos'])) {
            $this->dispatch('openPdfWindow', url: route('postcontractual.resolucion-pago-impuestos.pdf', $this->paymentOrderId));
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
                'taxLines',
                'bankLines.bankAccount.bank',
                'creator',
            ])->forSchool($this->schoolId)->find($this->paymentOrderId);
        }

        return view('livewire.postcontractual-management', [
            'paymentOrders' => $this->paymentOrders,
            'summary'       => $this->summary,
        ]);
    }
}

