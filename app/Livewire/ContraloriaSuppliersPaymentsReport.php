<?php

namespace App\Livewire;

use App\Models\PaymentOrder;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaSuppliersPaymentsReport extends Component
{
    public $schoolId;
    public $school;

    public $filterYear;

    public $rows = [];

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school     = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->loadReport();
    }

    public function updatedFilterYear(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $year = (int) $this->filterYear;

        // Pagos que cuentan con CDP y RP (de contrato o directos con CDP/RP).
        // Un pago de contrato tiene cdp/rp a través de contract → rps.
        // Un pago directo con CDP/RP tiene cdp_id/contract_rp_id en la propia orden.
        $orders = PaymentOrder::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->where(function ($q) {
                // A) Pago de contrato: tiene contract_id y el contrato tiene al menos un RP activo
                $q->where(function ($sub) {
                    $sub->whereNotNull('contract_id')
                        ->whereHas('contract.rps', fn($rq) => $rq->where('status', 'active'));
                });
                // B) Pago directo con CDP y RP
                $q->orWhere(function ($sub) {
                    $sub->whereNotNull('cdp_id')->whereNotNull('contract_rp_id');
                });
            })
            ->with([
                'supplier',
                'contract.supplier',
                'contract.rps.cdp.budgetItem',
                'contract.rps.fundingSources.bank',
                'contract.rps.fundingSources.bankAccount',
                'cdp.budgetItem',
                'contractRp.fundingSources.bank',
                'contractRp.fundingSources.bankAccount',
                'expenseLines.expenseCode',
            ])
            ->orderBy('payment_date')
            ->orderBy('payment_number')
            ->get();

        $this->rows = $orders->map(function (PaymentOrder $po) {
            // ── Código Presupuestal ─────────────────────────────────
            // Prioridad: expense line code → CDP budget item code → contract.rps.cdp → payment order budget item
            $codigoPresupuestal = $po->expenseLines->first()?->expenseCode?->code
                ?? $po->cdp?->budgetItem?->code
                ?? $po->contract?->rps->first()?->cdp?->budgetItem?->code
                ?? $po->budgetItem?->code
                ?? '';

            // ── Tipo de Pago ────────────────────────────────────────
            // Basado en el concepto de retención (uppercased)
            $tipoPago = $po->retention_concept
                ? strtoupper(PaymentOrder::RETENTION_CONCEPTS[$po->retention_concept] ?? $po->retention_concept)
                : 'N/D';

            // ── Beneficiario y NIT ──────────────────────────────────
            $supplier     = $po->resolved_supplier;
            $beneficiario = $supplier?->full_name ?? ($po->description ?? 'N/D');
            $nit          = $supplier?->document_number ?? '';

            // ── Banco y cuenta (desde fuentes del RP — directo o del contrato) ─
            $banco    = 'N/D';
            $noCuenta = 'N/D';

            $rpFs = $po->contractRp?->fundingSources->first()
                ?? $po->contract?->rps->first()?->fundingSources->first();
            if ($rpFs) {
                if ($rpFs->bank) {
                    $banco = $rpFs->bank->name;
                }
                if ($rpFs->bankAccount) {
                    $banco    = $rpFs->bankAccount->bank?->name ?? $banco;
                    $noCuenta = $rpFs->bankAccount->account_number;
                }
            }

            // Fallback: datos directos del proveedor en la orden
            if ($banco === 'N/D' && $po->supplier_bank_name) {
                $banco = $po->supplier_bank_name;
            }
            if ($noCuenta === 'N/D' && $po->supplier_account_number) {
                $noCuenta = $po->supplier_account_number;
            }

            // ── Descuentos desglosados ──────────────────────────────
            $descRetenciones = (float) $po->retefuente + (float) $po->reteiva;
            $otrosDescuentos = (float) $po->estampilla_produlto_mayor
                + (float) $po->estampilla_procultura
                + (float) $po->retencion_ica
                + (float) $po->other_taxes_total;

            return [
                'fecha'            => $po->payment_date?->format('Y/m/d') ?? 'N/D',
                'codigo_presup'    => $codigoPresupuestal,
                'tipo_pago'        => $tipoPago,
                'no_comprobante'   => $po->formatted_number,
                'beneficiario'     => $beneficiario,
                'nit'              => $nit,
                'detalle'          => $po->description ?? '',
                'valor'            => (float) $po->total,
                'desc_seg_social'  => 0.0,
                'desc_retenciones' => $descRetenciones,
                'otros_descuentos' => $otrosDescuentos,
                'neto'             => (float) $po->net_payment,
                'banco'            => $banco,
                'no_cuenta'        => $noCuenta,
                'no_cheque'        => 'Nd',
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.contraloria-suppliers-payments-report');
    }
}
