<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IncomePdfController extends Controller
{
    /**
     * Generar PDF de un ingreso individual
     */
    public function single(Request $request, int $id)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);

        $income = Income::where('school_id', $schoolId)
            ->with([
                'fundingSource.budgetItem.accountingAccount',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->findOrFail($id);

        $school = School::findOrFail($schoolId);

        // Monto en letras
        $amountInWords = self::amountToWords($income->amount);

        // Construir imputación contable
        // Débito: cuentas bancarias (Caja/Bancos)
        $debitAccounts = $this->buildDebitEntries($income);

        // Crédito: cuenta contable del rubro presupuestal
        $creditHierarchy = $this->buildAccountHierarchy(
            $income->fundingSource->budgetItem->accountingAccount ?? null
        );

        // Número secuencial del comprobante por colegio y año fiscal
        $incomeYear = $income->date->year;
        $receiptNumber = Income::where('school_id', $schoolId)
            ->whereYear('date', $incomeYear)
            ->where('id', '<=', $income->id)
            ->count();

        $pdf = Pdf::loadView('pdf.income-receipt', [
            'income' => $income,
            'school' => $school,
            'user' => auth()->user(),
            'amountInWords' => $amountInWords,
            'debitAccounts' => $debitAccounts,
            'creditHierarchy' => $creditHierarchy,
            'receiptNumber' => $receiptNumber,
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("ingreso-{$income->id}.pdf");
    }

    /**
     * Generar PDF con todos los ingresos de un presupuesto (rubro + fuente)
     */
    public function byBudget(Request $request, int $budgetId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);

        $budget = Budget::where('school_id', $schoolId)
            ->with(['budgetItem.accountingAccount', 'fundingSource'])
            ->findOrFail($budgetId);

        $incomes = Income::where('school_id', $schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->with([
                'fundingSource.budgetItem.accountingAccount',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date', 'asc')
            ->get();

        $totalCollected = $incomes->sum('amount');

        $school = School::findOrFail($schoolId);

        // Crédito: cuenta contable del rubro
        $creditHierarchy = $this->buildAccountHierarchy(
            $budget->budgetItem->accountingAccount ?? null
        );

        $pdf = Pdf::loadView('pdf.income-report', [
            'budget' => $budget,
            'incomes' => $incomes,
            'totalCollected' => $totalCollected,
            'school' => $school,
            'user' => auth()->user(),
            'creditHierarchy' => $creditHierarchy,
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("ingresos-rubro-{$budget->budgetItem->code}.pdf");
    }

    public function byBudgetMonth(Request $request, int $budgetId, int $month)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);
        abort_if($month < 1 || $month > 12, 404);

        $budget = Budget::where('school_id', $schoolId)
            ->with(['budgetItem.accountingAccount', 'fundingSource'])
            ->findOrFail($budgetId);

        $year = (int) ($budget->fiscal_year ?? now()->year);
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = (clone $periodStart)->endOfMonth()->endOfDay();

        $incomes = Income::where('school_id', $schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with([
                'fundingSource.budgetItem.accountingAccount',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date', 'asc')
            ->get();

        $totalCollected = (float) $incomes->sum('amount');
        $school = School::findOrFail($schoolId);

        $creditHierarchy = $this->buildAccountHierarchy(
            $budget->budgetItem->accountingAccount ?? null
        );

        $debitAccounts = $this->buildDebitEntriesFromIncomes($incomes);
        $amountInWords = self::amountToWords($totalCollected);

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $periodLabel = ($months[$month] ?? (string) $month) . ' ' . $year;

        $receiptNumber = sprintf('%d%02d-%s-%s', $year, $month, $budget->budgetItem->code ?? 'RUBRO', $budget->fundingSource->code ?? 'FUENTE');

        $pdf = Pdf::loadView('pdf.income-monthly-receipt', [
            'budget' => $budget,
            'incomes' => $incomes,
            'totalCollected' => $totalCollected,
            'school' => $school,
            'user' => auth()->user(),
            'amountInWords' => $amountInWords,
            'debitAccounts' => $debitAccounts,
            'creditHierarchy' => $creditHierarchy,
            'receiptNumber' => $receiptNumber,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodLabel' => $periodLabel,
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("comprobante-ingreso-{$year}-" . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . "-{$budgetId}.pdf");
    }

    /**
     * Construir entradas de débito a partir de las cuentas bancarias del ingreso.
     * Busca la cuenta contable 1110 (Bancos) o 1105 (Caja) según el tipo.
     */
    private function buildDebitEntries(Income $income): array
    {
        $entries = [];

        foreach ($income->bankAccounts as $ba) {
            // Buscar cuenta contable de bancos (1110) por defecto
            // Si la cuenta bancaria es tipo corriente → 111005, ahorros → 111010
            $accountCode = $ba->bankAccount?->account_type === 'corriente' ? '111005' : '111010';
            $account = AccountingAccount::where('code', $accountCode)->first();

            // Si no encuentra la específica, buscar 1110 genérica
            if (!$account) {
                $account = AccountingAccount::where('code', '1110')->first();
            }

            // Si aún no encuentra, buscar 1105 (Caja)
            if (!$account) {
                $account = AccountingAccount::where('code', '1105')->first();
            }

            $hierarchy = $account ? $this->buildAccountHierarchy($account) : [];

            $entries[] = [
                'hierarchy' => $hierarchy,
                'amount' => (float) $ba->amount,
                'bank_name' => $ba->bank->name ?? '',
                'account_number' => $ba->bankAccount->account_number ?? '',
            ];
        }

        return $entries;
    }

    private function buildDebitEntriesFromIncomes(Collection $incomes): array
    {
        $groups = [];

        foreach ($incomes as $income) {
            foreach ($income->bankAccounts as $ba) {
                $accountType = $ba->bankAccount?->account_type ?? '';
                $key = implode('-', [
                    (string) ($ba->bank_id ?? ''),
                    (string) ($ba->bank_account_id ?? ''),
                    (string) $accountType,
                ]);

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'bank_name' => $ba->bank->name ?? '',
                        'account_number' => $ba->bankAccount->account_number ?? '',
                        'account_type' => $accountType,
                        'account_type_name' => $ba->bankAccount->account_type_name ?? '',
                        'amount' => 0.0,
                    ];
                }

                $groups[$key]['amount'] += (float) ($ba->amount ?? 0);
            }
        }

        $entries = [];

        foreach ($groups as $group) {
            $accountCode = ($group['account_type'] === 'corriente') ? '111005' : '111010';
            $account = AccountingAccount::where('code', $accountCode)->first();

            if (!$account) {
                $account = AccountingAccount::where('code', '1110')->first();
            }

            if (!$account) {
                $account = AccountingAccount::where('code', '1105')->first();
            }

            $hierarchy = $account ? $this->buildAccountHierarchy($account) : [];

            $entries[] = [
                'hierarchy' => $hierarchy,
                'amount' => (float) $group['amount'],
                'bank_name' => $group['bank_name'],
                'account_number' => $group['account_number'],
                'account_type_name' => $group['account_type_name'],
            ];
        }

        return $entries;
    }

    /**
     * Construir la jerarquía de una cuenta contable (desde la raíz hasta la cuenta).
     */
    private function buildAccountHierarchy(?AccountingAccount $account): array
    {
        if (!$account) {
            return [];
        }

        $hierarchy = [];
        $current = $account;

        // Recorrer hacia arriba hasta la raíz
        while ($current) {
            array_unshift($hierarchy, [
                'code' => $current->code,
                'name' => $current->name,
                'level' => $current->level,
                'show_amount' => ($current->id === $account->id), // Solo mostrar monto en la cuenta final
            ]);
            $current = $current->parent;
        }

        return $hierarchy;
    }

    /**
     * Convertir monto numérico a palabras en español.
     */
    public static function amountToWords(float $amount): string
    {
        $entero = (int) floor(abs($amount));
        $decimales = (int) round((abs($amount) - $entero) * 100);

        $texto = self::numberToWords($entero);

        if ($decimales > 0) {
            $texto .= ' pesos con ' . self::numberToWords($decimales) . ' centavos';
        } else {
            $texto .= ' pesos';
        }

        return mb_strtoupper($texto);
    }

    private static function numberToWords(int $number): string
    {
        if ($number === 0) return 'cero';

        $units = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $teens = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
        $tens = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($number < 0) return 'menos ' . self::numberToWords(abs($number));

        $result = '';

        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            if ($millions === 1) {
                $result .= 'un millón ';
            } else {
                $result .= self::numberToWords($millions) . ' millones ';
            }
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands === 1) {
                $result .= 'mil ';
            } else {
                $result .= self::numberToWords($thousands) . ' mil ';
            }
            $number %= 1000;
        }

        if ($number >= 100) {
            if ($number === 100) {
                $result .= 'cien';
                return trim($result);
            }
            $result .= $hundreds[(int) floor($number / 100)] . ' ';
            $number %= 100;
        }

        if ($number >= 20) {
            $ten = (int) floor($number / 10);
            $unit = $number % 10;
            if ($ten === 2 && $unit > 0) {
                $result .= 'veinti' . $units[$unit];
            } else {
                $result .= $tens[$ten];
                if ($unit > 0) {
                    $result .= ' y ' . $units[$unit];
                }
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $units[$number];
        }

        return trim($result);
    }
}
