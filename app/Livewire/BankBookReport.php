<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\IncomeBankAccount;
use App\Models\PaymentOrder;
use App\Models\RpFundingSource;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class BankBookReport extends Component
{
    public $schoolId;
    public $school;

    // Filtros
    public $filterYear;
    public $filterBankAccount = '';

    // Datos
    public $bankAccounts = [];
    public $movements = [];
    public $selectedAccount = null;

    public function mount()
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->loadBankAccounts();
    }

    public function loadBankAccounts()
    {
        $this->bankAccounts = BankAccount::whereHas('bank', fn($q) => $q->forSchool($this->schoolId)->active())
            ->active()
            ->with('bank')
            ->orderBy('bank_id')
            ->orderBy('account_number')
            ->get()
            ->map(fn($ba) => [
                'id' => $ba->id,
                'label' => $ba->bank->name . ' - ' . $ba->account_number . ' (' . $ba->account_type_name . ($ba->holder_name ? ' - ' . $ba->holder_name : '') . ')',
                'bank_name' => $ba->bank->name,
                'account_number' => $ba->account_number,
                'account_type' => $ba->account_type_name,
                'holder_name' => $ba->holder_name ?? '',
            ])
            ->toArray();

        if (!empty($this->bankAccounts) && empty($this->filterBankAccount)) {
            $this->filterBankAccount = $this->bankAccounts[0]['id'];
        }

        $this->loadReport();
    }

    public function updatedFilterYear()
    {
        $this->loadReport();
    }

    public function updatedFilterBankAccount()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        if (empty($this->filterBankAccount)) {
            $this->movements = [];
            $this->selectedAccount = null;
            return;
        }

        $bankAccountId = (int) $this->filterBankAccount;
        $year = (int) $this->filterYear;

        // Obtener info de la cuenta seleccionada
        $account = BankAccount::with('bank')->find($bankAccountId);
        if (!$account) {
            $this->movements = [];
            $this->selectedAccount = null;
            return;
        }

        $this->selectedAccount = [
            'bank_name' => $account->bank->name,
            'account_number' => $account->account_number,
            'account_type' => $account->account_type_name,
            'holder_name' => $account->holder_name ?? '',
        ];

        $movements = [];

        // ── INGRESOS (consignaciones) ──
        $incomeBankAccounts = IncomeBankAccount::where('bank_account_id', $bankAccountId)
            ->whereHas('income', function ($q) use ($year) {
                $q->where('school_id', $this->schoolId)
                  ->whereYear('date', $year);
            })
            ->with(['income.fundingSource.budgetItem'])
            ->get();

        foreach ($incomeBankAccounts as $iba) {
            $income = $iba->income;
            $movements[] = [
                'date' => $income->date->format('Y-m-d'),
                'date_sort' => $income->date->format('Y-m-d'),
                'detail' => $income->name . ($income->description ? ' - ' . $income->description : ''),
                'income_ref' => $income->id,
                'income_amount' => (float) $iba->amount,
                'expense_ref' => null,
                'expense_amount' => 0,
                'type' => 'income',
            ];
        }

        // ── EGRESOS (pagos) ──
        $paymentOrders = PaymentOrder::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->with(['contract.rps.fundingSources'])
            ->get();

        foreach ($paymentOrders as $po) {
            $supplier = $po->resolved_supplier;
            $supplierName = $supplier ? $supplier->full_name : '';
            $paymentAmount = 0;
            $matchesAccount = false;

            // 1. Verificar egress_bank_account_id directo (pagos sin CDP/RP)
            if ($po->egress_bank_account_id && (int) $po->egress_bank_account_id === $bankAccountId) {
                $paymentAmount = (float) $po->net_payment;
                $matchesAccount = true;
            }

            // 2. Verificar via contract_rp_id
            if (!$matchesAccount && $po->contract_rp_id) {
                $rpFs = RpFundingSource::where('contract_rp_id', $po->contract_rp_id)
                    ->where('bank_account_id', $bankAccountId)
                    ->first();
                if ($rpFs) {
                    $totalRpAmount = RpFundingSource::where('contract_rp_id', $po->contract_rp_id)->sum('amount');
                    $ratio = $totalRpAmount > 0 ? (float) $rpFs->amount / $totalRpAmount : 1;
                    $paymentAmount = (float) $po->net_payment * $ratio;
                    $matchesAccount = true;
                }
            }

            // 3. Verificar via contract_id → RPs del contrato
            if (!$matchesAccount && $po->contract_id && $po->contract) {
                foreach ($po->contract->rps->where('status', 'active') as $rp) {
                    foreach ($rp->fundingSources as $rpFs) {
                        if ((int) $rpFs->bank_account_id === $bankAccountId) {
                            $totalRpAmount = $rp->fundingSources->sum('amount');
                            $ratio = $totalRpAmount > 0 ? (float) $rpFs->amount / $totalRpAmount : 1;
                            $paymentAmount += (float) $po->net_payment * $ratio;
                            $matchesAccount = true;
                        }
                    }
                }
            }

            if ($matchesAccount && $paymentAmount > 0) {
                $movements[] = [
                    'date' => $po->payment_date ? $po->payment_date->format('Y-m-d') : ($po->created_at ? $po->created_at->format('Y-m-d') : ''),
                    'date_sort' => $po->payment_date ? $po->payment_date->format('Y-m-d') : ($po->created_at ? $po->created_at->format('Y-m-d') : '9999-12-31'),
                    'detail' => ($po->description ?? $po->contract?->object ?? 'Pago') . ($supplierName ? ' - ' . $supplierName : ''),
                    'income_ref' => null,
                    'income_amount' => 0,
                    'expense_ref' => $po->formatted_number,
                    'expense_amount' => round($paymentAmount, 2),
                    'type' => 'expense',
                ];
            }
        }

        // Ordenar por fecha
        usort($movements, fn($a, $b) => strcmp($a['date_sort'], $b['date_sort']));

        // Calcular saldo anterior (movimientos del año anterior)
        $previousBalance = $this->calculatePreviousBalance($bankAccountId, $year);

        // Calcular saldo acumulado
        $balance = $previousBalance;
        foreach ($movements as &$mov) {
            $balance += $mov['income_amount'] - $mov['expense_amount'];
            $mov['balance'] = $balance;
        }
        unset($mov);

        $this->movements = [
            'previous_balance' => $previousBalance,
            'previous_year' => $year - 1,
            'items' => $movements,
            'total_income' => array_sum(array_column($movements, 'income_amount')),
            'total_expense' => array_sum(array_column($movements, 'expense_amount')),
            'final_balance' => $balance,
        ];

        $this->dispatch('reportLoaded');
    }

    private function calculatePreviousBalance(int $bankAccountId, int $year): float
    {
        $account = BankAccount::find($bankAccountId);

        // Si la cuenta tiene saldo inicial configurado para esta vigencia o anterior, usarlo como base
        $initialBalance = 0;
        if ($account && $account->initial_balance > 0 && $account->initial_balance_year) {
            if ($account->initial_balance_year <= $year) {
                $initialBalance = (float) $account->initial_balance;
            }
        }

        // Si el saldo inicial es de la misma vigencia, es el saldo de apertura
        if ($account && $account->initial_balance_year == $year) {
            return $initialBalance;
        }

        // Sumar todos los ingresos desde el año del saldo inicial hasta el año anterior
        $startYear = ($account && $account->initial_balance_year) ? $account->initial_balance_year : 0;

        $totalIncome = IncomeBankAccount::where('bank_account_id', $bankAccountId)
            ->whereHas('income', function ($q) use ($year, $startYear) {
                $q->where('school_id', $this->schoolId)
                  ->whereYear('date', '<', $year);
                if ($startYear > 0) {
                    $q->whereYear('date', '>=', $startYear);
                }
            })
            ->sum('amount');

        // Sumar todos los egresos desde el año del saldo inicial hasta el año anterior
        $totalExpense = 0;

        $rpFundingSources = RpFundingSource::where('bank_account_id', $bankAccountId)
            ->whereHas('contractRp', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->with('contractRp')
            ->get();

        $processedPOs = [];
        foreach ($rpFundingSources as $rpFs) {
            if (!$rpFs->contractRp) continue;

            $paymentOrders = PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', '<', $year)
                ->where('contract_rp_id', $rpFs->contractRp->id)
                ->whereIn('status', ['approved', 'paid'])
                ->get();

            if ($startYear > 0) {
                $paymentOrders = $paymentOrders->where('fiscal_year', '>=', $startYear);
            }

            foreach ($paymentOrders as $po) {
                if (in_array($po->id, $processedPOs)) continue;
                $processedPOs[] = $po->id;

                $totalRpAmount = RpFundingSource::where('contract_rp_id', $rpFs->contractRp->id)->sum('amount');
                $ratio = $totalRpAmount > 0 ? (float) $rpFs->amount / $totalRpAmount : 1;
                $totalExpense += (float) $po->net_payment * $ratio;
            }
        }

        return $initialBalance + (float) $totalIncome - $totalExpense;
    }

    public function getPeriodLabelProperty(): string
    {
        return "VIGENCIA {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.bank-book-report');
    }
}
