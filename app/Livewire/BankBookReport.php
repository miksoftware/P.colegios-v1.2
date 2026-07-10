<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Models\IncomeBankAccount;
use App\Models\PaymentOrder;
use App\Models\RpFundingSource;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class BankBookReport extends Component
{
    use WithFileUploads;

    public $schoolId;
    public $school;

    // Filtros
    public $filterYear;
    public $filterBankAccount = '';

    // Extractos bancarios (PDF por mes)
    public $statementMonth;
    public $statementFile;
    public $statementByMonth = [];
    public $selectedStatement = null;
    public $isAdmin = false;

    // Datos
    public $bankAccounts = [];
    public $movements = [];
    public $selectedAccount = null;

    public function mount()
    {
        $user = Auth::user();
        abort_if(!($user instanceof User), 403);
        abort_if(!Gate::forUser($user)->allows('reports.view'), 403);
        /** @var User $user */

        $this->isAdmin = $user->isAdmin();

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->statementMonth = (int) now()->month;
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

    public function updatedStatementMonth()
    {
        $this->statementFile = null;
        $this->loadStatementState();
    }

    public function loadReport()
    {
        if (empty($this->filterBankAccount)) {
            $this->movements = [];
            $this->selectedAccount = null;
            $this->statementByMonth = [];
            $this->selectedStatement = null;
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

        $this->loadStatementState();

        $this->dispatch('reportLoaded');
    }

    private function loadStatementState(): void
    {
        $bankAccountId = (int) $this->filterBankAccount;
        $year = (int) $this->filterYear;
        $month = (int) $this->statementMonth;

        if ($bankAccountId <= 0 || $year <= 0 || $month < 1 || $month > 12) {
            $this->statementByMonth = [];
            $this->selectedStatement = null;
            return;
        }

        $statements = BankAccountStatement::where('school_id', $this->schoolId)
            ->where('bank_account_id', $bankAccountId)
            ->where('year', $year)
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $this->statementByMonth = $statements->map(function (BankAccountStatement $statement) {
            return [
                'id' => $statement->id,
                'month' => (int) $statement->month,
                'file_name' => $statement->file_name,
                'file_size' => (int) $statement->file_size,
                'uploaded_at' => optional($statement->created_at)?->format('d/m/Y H:i'),
            ];
        })->toArray();

        $this->selectedStatement = $this->statementByMonth[$month] ?? null;
    }

    public function uploadStatement(): void
    {
        $this->validate([
            'filterBankAccount' => 'required|integer',
            'filterYear' => 'required|integer|min:2000|max:2100',
            'statementMonth' => 'required|integer|min:1|max:12',
            'statementFile' => 'required|file|mimes:pdf|max:10240',
        ], [
            'statementFile.mimes' => 'Solo se permiten archivos PDF.',
            'statementFile.max' => 'El PDF no puede superar los 10 MB.',
        ]);

        $bankAccountId = (int) $this->filterBankAccount;
        $year = (int) $this->filterYear;
        $month = (int) $this->statementMonth;

        $account = BankAccount::whereHas('bank', fn($q) => $q->forSchool($this->schoolId))
            ->find($bankAccountId);

        if (!$account) {
            $this->dispatch('toast', message: 'La cuenta bancaria seleccionada no es válida.', type: 'error');
            return;
        }

        $alreadyExists = BankAccountStatement::where('school_id', $this->schoolId)
            ->where('bank_account_id', $bankAccountId)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($alreadyExists) {
            $this->dispatch('toast', message: 'Este mes ya tiene un extracto cargado. Solo un administrador puede eliminarlo para habilitar una nueva carga.', type: 'error');
            $this->statementFile = null;
            $this->loadStatementState();
            return;
        }

        $originalName = $this->statementFile->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = Str::slug($baseName) ?: 'extracto-bancario';
        $extension = strtolower($this->statementFile->getClientOriginalExtension() ?: 'pdf');
        $storedName = sprintf('mes-%02d-%s-%s.%s', $month, now()->format('YmdHis'), Str::random(8), $extension);
        $directory = "bank-statements/{$this->schoolId}/{$year}/{$bankAccountId}";

        $path = $this->statementFile->storeAs($directory, $storedName, 'local');

        BankAccountStatement::create([
            'school_id' => $this->schoolId,
            'bank_account_id' => $bankAccountId,
            'year' => $year,
            'month' => $month,
            'file_name' => $safeBaseName . '.pdf',
            'file_path' => $path,
            'file_size' => (int) $this->statementFile->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->statementFile = null;
        $this->loadStatementState();
        $this->dispatch('toast', message: 'Extracto bancario cargado correctamente.', type: 'success');
    }

    public function downloadStatement(int $statementId)
    {
        if (!$this->isAdmin) {
            $this->dispatch('toast', message: 'Solo los administradores pueden descargar extractos.', type: 'error');
            return;
        }

        $statement = BankAccountStatement::where('school_id', $this->schoolId)
            ->where('id', $statementId)
            ->first();

        if (!$statement || !Storage::disk('local')->exists($statement->file_path)) {
            $this->dispatch('toast', message: 'No se encontró el archivo solicitado.', type: 'error');
            return;
        }

        return response()->download(
            Storage::disk('local')->path($statement->file_path),
            $statement->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function deleteStatement(int $statementId): void
    {
        if (!$this->isAdmin) {
            $this->dispatch('toast', message: 'Solo los administradores pueden eliminar extractos.', type: 'error');
            return;
        }

        $statement = BankAccountStatement::where('school_id', $this->schoolId)
            ->where('id', $statementId)
            ->first();

        if (!$statement) {
            $this->dispatch('toast', message: 'No se encontró el extracto seleccionado.', type: 'error');
            return;
        }

        if (Storage::disk('local')->exists($statement->file_path)) {
            Storage::disk('local')->delete($statement->file_path);
        }

        $statement->delete();
        $this->loadStatementState();
        $this->dispatch('toast', message: 'Extracto eliminado. El mes quedó habilitado para volver a cargarlo.', type: 'success');
    }

    public function getMonthOptionsProperty(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    private function calculatePreviousBalance(int $bankAccountId, int $year): float
    {
        // El libro de bancos NO considera saldo anterior. Todo movimiento arranca en 0
        // y los recaudos/pagos del periodo determinan el saldo acumulado.
        // Si el colegio necesita traer arrastre, debe registrarlo como un ingreso
        // real con fecha dentro del periodo (ej. "Recaudo Superávit" 01/01).
        return 0;
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
