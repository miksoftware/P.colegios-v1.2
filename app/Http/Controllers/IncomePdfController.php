<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

        $pdf = Pdf::loadView('pdf.income-receipt', [
            'income' => $income,
            'school' => $school,
            'user' => auth()->user(),
            'amountInWords' => $amountInWords,
            'debitAccounts' => $debitAccounts,
            'creditHierarchy' => $creditHierarchy,
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
