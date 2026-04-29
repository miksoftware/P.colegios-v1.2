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

        // Pagos que cuentan con CDP y RP (ya sean de contrato o directos con CDP/RP)
        $orders = PaymentOrder::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->whereNotNull('cdp_id')
            ->whereNotNull('contract_rp_id')
            ->whereIn('status', ['approved', 'paid'])
            ->with([
                'supplier',
                'contract.supplier',
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
            // Prioridad: expense line code → CDP budget item code → payment order budget item
            $codigoPresupuestal = $po->expenseLines->first()?->expenseCode?->code
                ?? $po->cdp?->budgetItem?->code
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

            // ── Banco y cuenta (desde fuentes de financiación del RP) ─
            $banco    = 'N/D';
            $noCuenta = 'N/D';

            $rpFs = $po->contractRp?->fundingSources->first();
            if ($rpFs) {
                // Intentar banco directo del RP funding source
                if ($rpFs->bank) {
                    $banco = $rpFs->bank->name;
                }
                // Cuenta bancaria del RP funding source
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
