<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\Income;
use App\Models\School;
use App\Support\MonthlyIncomeReceiptBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IncomePdfController extends Controller
{
    /**
     * Generar PDF de un ingreso individual
     */
    public function single(Request $request, int $id)
    {
        abort(404);
    }

    /**
     * Generar PDF con todos los ingresos de un presupuesto (rubro + fuente)
     */
    public function byBudget(Request $request, int $budgetId)
    {
        abort(404);
    }

    public function monthly(Request $request, int $year, int $month)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);
        abort_if($month < 1 || $month > 12, 404);

        $incomes = Income::where('school_id', $schoolId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with([
                'fundingSource.budgetItem',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date', 'asc')
            ->get();

        abort_if($incomes->isEmpty(), 404);

        $school = School::findOrFail($schoolId);
        $accountingAccounts = MonthlyIncomeReceiptBuilder::collectRelevantAccountingAccounts($incomes);

        $builder = new MonthlyIncomeReceiptBuilder();
        $receipt = $builder->build($incomes, $accountingAccounts, $year, $month);

        abort_if($receipt['has_errors'], 422, implode("\n", $receipt['validation_errors']));

        $pdf = Pdf::loadView('pdf.income-monthly-receipt', [
            'school' => $school,
            'user' => auth()->user(),
            'receipt' => $receipt,
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("comprobante-ingresos-{$year}-" . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . ".pdf");
    }

    public function byBudgetMonth(Request $request, int $budgetId, int $month)
    {
        abort(404);
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
