<?php

namespace App\Livewire;

use App\Models\BankAccount;
use App\Models\PaymentOrder;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaTaxPaymentsReport extends Component
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

        $orders = PaymentOrder::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->where('payment_type', 'direct')
            ->whereNull('cdp_id')
            ->whereNull('contract_rp_id')
            ->whereIn('status', ['approved', 'paid'])
            ->with(['supplier', 'bankLines.bankAccount.bank'])
            ->orderBy('payment_date')
            ->orderBy('payment_number')
            ->get();

        // Pre-load egress bank accounts in bulk to avoid N+1
        $egressIds = $orders
            ->whereNull('bankLines[0]')
            ->pluck('egress_bank_account_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Simpler: just collect all egress IDs from orders that have no bankLines
        $egressMap = collect();
        $ordersNeedingEgress = $orders->filter(
            fn($o) => $o->bankLines->isEmpty() && $o->egress_bank_account_id
        );
        if ($ordersNeedingEgress->isNotEmpty()) {
            $ids = $ordersNeedingEgress->pluck('egress_bank_account_id')->unique()->values();
            $egressMap = BankAccount::with('bank')->whereIn('id', $ids)->get()->keyBy('id');
        }

        $this->rows = $orders->map(function (PaymentOrder $po) use ($egressMap) {
            // Bank info: from bankLines first, then egress_bank_account_id
            $banco    = 'N/D';
            $noCuenta = 'N/D';

            $firstLine = $po->bankLines->first();
            if ($firstLine?->bankAccount) {
                $banco    = $firstLine->bankAccount->bank?->name ?? 'N/D';
                $noCuenta = $firstLine->bankAccount->account_number ?? 'N/D';
            } elseif ($po->egress_bank_account_id && $egressMap->has($po->egress_bank_account_id)) {
                $acc      = $egressMap->get($po->egress_bank_account_id);
                $banco    = $acc->bank?->name ?? 'N/D';
                $noCuenta = $acc->account_number ?? 'N/D';
            }

            $supplier     = $po->resolved_supplier;
            $beneficiario = $supplier?->full_name ?? ($po->description ?? 'N/D');
            $nit          = $supplier?->document_number ?? '';

            return [
                'fecha'          => $po->payment_date?->format('Y/m/d') ?? 'N/D',
                'no_comprobante' => $po->formatted_number,
                'beneficiario'   => $beneficiario,
                'nit'            => $nit,
                'detalle'        => $po->description ?? '',
                'valor'          => (float) $po->total,
                'descuentos'     => (float) $po->total_retentions,
                'neto'           => (float) $po->net_payment,
                'banco'          => $banco,
                'no_cuenta'      => $noCuenta,
                'no_cheque'      => 'Nd',
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.contraloria-tax-payments-report');
    }
}
