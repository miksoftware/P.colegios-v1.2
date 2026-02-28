<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\PaymentOrder;
use App\Models\School;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

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

    // Datos auxiliares
    public $availableContracts = [];
    public $schoolMunicipality = '';

    // Modales
    public $showStatusModal = false;
    public $newStatus = '';
    public $showDeleteModal = false;

    protected $queryString = [
        'filterYear'   => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    // ── Mount ────────────────────────────────────────────────

    public function mount()
    {
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->filterYear = date('Y');

        // Obtener municipio del colegio para impuestos locales
        $school = School::find($this->schoolId);
        $this->schoolMunicipality = strtolower(trim($school->municipality ?? ''));
    }

    public function updatingSearch()      { $this->resetPage(); }
    public function updatingFilterYear()  { $this->resetPage(); }
    public function updatingFilterStatus(){ $this->resetPage(); }

    // ══════════════════════════════════════════════════════════
    // LISTADO
    // ══════════════════════════════════════════════════════════

    public function getPaymentOrdersProperty()
    {
        return PaymentOrder::with(['contract.supplier', 'creator'])
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
        $this->currentView = 'create';
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

    public function onContractSelected()
    {
        if (!$this->selectedContractId) {
            $this->contractData = [];
            $this->supplierData = [];
            $this->fundingSourcesData = [];
            $this->paySubtotal = '';
            $this->payIva = '';
            $this->payTotal = '';
            return;
        }

        $contract = Contract::with([
            'supplier.department',
            'supplier.municipality',
            'rps.fundingSources.fundingSource',
        ])->findOrFail($this->selectedContractId);

        $totalPaid = PaymentOrder::getTotalPaidForContract($contract->id);
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
                'municipality'   => $supplier->city ?? 'No registrado',
                'phone'          => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
                'tax_regime'     => $supplier->tax_regime ?? '',
                'tax_regime_name'=> $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
                'bank_name'      => $supplier->bank_name ?? '',
                'account_type'   => $supplier->account_type ? (Supplier::ACCOUNT_TYPES[$supplier->account_type] ?? $supplier->account_type) : '',
                'account_number' => $supplier->account_number ?? '',
                'person_type'    => $supplier->person_type ?? '',
            ];
        }

        // Fuentes de financiación desde los RPs
        $sources = [];
        foreach ($contract->rps as $rp) {
            foreach ($rp->fundingSources as $fs) {
                $sources[] = [
                    'name'   => $fs->fundingSource->name ?? 'N/A',
                    'amount' => (float) $fs->amount,
                ];
            }
        }
        $this->fundingSourcesData = $sources;

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

    /**
     * Calcula todas las retenciones según las reglas del Excel:
     *
     * RETENCIONES DIAN:
     * - Retefuente: % sobre subtotal, solo si subtotal >= base mínima del concepto
     * - ReteIVA: 15% del IVA, solo para responsables de IVA (régimen común y gran contribuyente)
     *   y si la base sobrepasa para la retención en la fuente de renta
     *
     * OTROS IMPUESTOS (según municipio del colegio):
     * - Estampilla Produlto Mayor: 2% del subtotal si >= $1 (solo Bucaramanga)
     * - Estampilla Procultura: 2% del subtotal si >= $35,018,010 (solo Bucaramanga)
     * - Retención ICA: solo Piedecuesta y Villanueva
     *
     * REGLAS DE RÉGIMEN:
     * - Régimen Simplificado: son personas naturales, NO responsables de IVA,
     *   hay unos que declaran y otros que no declara
     * - Régimen Común: responsables de IVA y declarantes de renta
     * - Gran Contribuyente: responsables de IVA y declarantes de renta
     * - No Responsable de IVA: personas naturales, NO responsables de IVA
     *
     * Solo se aplica ReteIVA a régimen común y gran contribuyente (responsables de IVA)
     */
    public function calculateRetentions()
    {
        $subtotal = (float) ($this->paySubtotal ?? 0);
        $iva = (float) ($this->payIva ?? 0);
        $total = $subtotal + $iva;
        $this->payTotal = $total;

        $taxRegime = $this->supplierData['tax_regime'] ?? '';

        // ── RETEFUENTE ──
        $this->retefuente = 0;
        $this->retentionPercentage = 0;

        if ($this->retentionConcept && isset(PaymentOrder::RETENTION_RATES[$this->retentionConcept])) {
            // Verificar si el subtotal supera la base mínima
            if (PaymentOrder::meetsRetentionThreshold($this->retentionConcept, $subtotal)) {
                $rate = PaymentOrder::getRetentionRate($this->retentionConcept, (bool) $this->supplierDeclaresRent);
                $this->retentionPercentage = $rate;
                $this->retefuente = round($subtotal * ($rate / 100), 2);
            }
        }

        // ── RETEIVA ──
        // 15% del IVA, solo para responsables de IVA (régimen común y gran contribuyente)
        // y solo si la base sobrepasa el umbral de retención en la fuente
        $this->reteiva = 0;
        $isIvaResponsible = in_array($taxRegime, ['comun', 'gran_contribuyente']);

        if ($isIvaResponsible && $iva > 0 && $this->retentionConcept) {
            if (PaymentOrder::meetsRetentionThreshold($this->retentionConcept, $subtotal)) {
                $this->reteiva = round($iva * 0.15, 2);
            }
        }

        $this->totalRetentionsDian = round($this->retefuente + $this->reteiva, 2);

        // ── OTROS IMPUESTOS (según municipio del colegio) ──
        $this->estampillaProdultoMayor = 0;
        $this->estampillaProcultura = 0;
        $this->retencionIca = 0;

        $municipality = $this->schoolMunicipality;

        // Estampilla Produlto Mayor: 2% si subtotal >= $1 (solo Bucaramanga)
        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 1) {
            $this->estampillaProdultoMayor = round($subtotal * 0.02, 2);
        }

        // Estampilla Procultura: 2% si subtotal >= $35,018,010 (solo Bucaramanga)
        if (str_contains($municipality, 'bucaramanga') && $subtotal >= 35018010) {
            $this->estampillaProcultura = round($subtotal * 0.02, 2);
        }

        // Retención ICA: solo Piedecuesta y Villanueva (placeholder - tasa configurable)
        // Por ahora no se aplica automáticamente, se deja en 0

        $this->otherTaxesTotal = round(
            $this->estampillaProdultoMayor + $this->estampillaProcultura + $this->retencionIca,
            2
        );

        // ── TOTALES ──
        $this->totalRetentions = round($this->totalRetentionsDian + $this->otherTaxesTotal, 2);
        $this->netPayment = round($total - $this->totalRetentions, 2);
    }

    public function savePaymentOrder()
    {
        if (!auth()->user()->can('postcontractual.create')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        $this->validate([
            'selectedContractId' => 'required|exists:contracts,id',
            'paymentDate'        => 'required|date',
            'invoiceDate'        => 'required|date',
            'invoiceNumber'      => 'required|string|max:100',
            'paySubtotal'        => 'required|numeric|min:0.01',
            'payIva'             => 'nullable|numeric|min:0',
            'payTotal'           => 'required|numeric|min:0.01',
        ], [
            'selectedContractId.required' => 'Debe seleccionar un contrato.',
            'paymentDate.required'        => 'La fecha de pago es obligatoria.',
            'invoiceDate.required'        => 'La fecha de la factura es obligatoria.',
            'invoiceNumber.required'      => 'El número de factura es obligatorio.',
            'paySubtotal.required'        => 'El subtotal es obligatorio.',
            'paySubtotal.min'             => 'El subtotal debe ser mayor a 0.',
            'payTotal.required'           => 'El total es obligatorio.',
        ]);

        // Validar que no exceda el saldo pendiente
        $remaining = $this->contractData['remaining'] ?? 0;
        if ((float) $this->payTotal > $remaining) {
            $formattedTotal = number_format($this->payTotal, 0, ',', '.');
            $formattedRemaining = number_format($remaining, 0, ',', '.');
            $this->dispatch('toast', message: "El monto (\${$formattedTotal}) excede el saldo pendiente (\${$formattedRemaining}).", type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;

            $paymentOrder = PaymentOrder::create([
                'school_id'                  => $this->schoolId,
                'contract_id'                => $this->selectedContractId,
                'payment_number'             => PaymentOrder::getNextPaymentNumber($this->schoolId, $year),
                'fiscal_year'                => $year,
                'invoice_number'             => $this->invoiceNumber,
                'invoice_date'               => $this->invoiceDate,
                'payment_date'               => $this->paymentDate,
                'is_full_payment'            => $this->isFullPayment,
                'subtotal'                   => (float) $this->paySubtotal,
                'iva'                        => (float) ($this->payIva ?? 0),
                'total'                      => (float) $this->payTotal,
                'retention_concept'          => $this->retentionConcept ?: null,
                'supplier_declares_rent'     => $this->supplierDeclaresRent,
                'retention_percentage'       => $this->retentionPercentage,
                'retefuente'                 => $this->retefuente,
                'reteiva'                    => $this->reteiva,
                'estampilla_produlto_mayor'  => $this->estampillaProdultoMayor,
                'estampilla_procultura'      => $this->estampillaProcultura,
                'retencion_ica'              => $this->retencionIca,
                'other_taxes_total'          => $this->otherTaxesTotal,
                'total_retentions'           => $this->totalRetentions,
                'net_payment'                => $this->netPayment,
                'observations'               => $this->observations ?: null,
                'supplier_bank_name'         => $this->supplierData['bank_name'] ?? null,
                'supplier_account_type'      => $this->supplierData['account_type'] ?? null,
                'supplier_account_number'    => $this->supplierData['account_number'] ?? null,
                'status'                     => 'draft',
                'created_by'                 => auth()->id(),
            ]);

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
    }

    // ══════════════════════════════════════════════════════════
    // RENDER
    // ══════════════════════════════════════════════════════════

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.postcontractual-management', [
            'paymentOrders' => $this->paymentOrders,
            'summary'       => $this->summary,
        ]);
    }
}
