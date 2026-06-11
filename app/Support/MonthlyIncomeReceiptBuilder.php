<?php

namespace App\Support;

use App\Models\AccountingAccount;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlyIncomeReceiptBuilder
{
    public static function collectRelevantAccountingAccounts(Collection $incomes): Collection
    {
        $codes = $incomes
            ->map(fn (Income $income) => self::extractAccountingCode($income->name))
            ->filter()
            ->values();

        foreach ($incomes as $income) {
            foreach ($income->bankAccounts as $bankLine) {
                $codes->push(($bankLine->bankAccount?->account_type ?? '') === 'corriente' ? '111005' : '111010');
            }
        }

        $codes = $codes
            ->push('1110')
            ->push('1105')
            ->filter()
            ->unique()
            ->values();

        return AccountingAccount::query()
            ->whereIn('code', $codes->all())
            ->with('parent')
            ->get()
            ->unique('code')
            ->values();
    }

    public function build(Collection $allIncomes, Collection $accountingAccounts, int $year, int $month): array
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = (clone $periodStart)->endOfMonth()->endOfDay();

        $incomes = $allIncomes
            ->filter(function (Income $income) use ($year, $month) {
                return (int) $income->date?->format('Y') === $year
                    && (int) $income->date?->format('n') === $month;
            })
            ->sortBy(fn (Income $income) => [
                $income->date?->format('Y-m-d') ?? '',
                $income->id ?? 0,
            ])
            ->values();

        $accountsByCode = $accountingAccounts->keyBy('code');
        $validationErrors = $this->validateAccountingCodes($incomes, $accountsByCode);

        $detailGroups = $this->buildDetailGroups($incomes);
        $creditEntries = $this->buildCreditEntries($incomes, $accountsByCode);
        $debitEntries = $this->buildDebitEntries($incomes, $accountsByCode);

        $totalCollected = round((float) $incomes->sum(fn (Income $income) => (float) $income->amount), 2);
        $detailTotal = round((float) collect($detailGroups)->sum('amount'), 2);
        $debitTotal = round((float) collect($debitEntries)->sum('amount'), 2);
        $creditTotal = round((float) collect($creditEntries)->sum('amount'), 2);

        if (abs($detailTotal - $totalCollected) > 0.01) {
            $validationErrors[] = 'El total consolidado por rubro/fuente no coincide con el total mensual de ingresos.';
        }

        if (abs($debitTotal - $totalCollected) > 0.01) {
            $validationErrors[] = 'La distribución en cuentas bancarias no coincide con el total mensual del comprobante.';
        }

        if (abs($creditTotal - $totalCollected) > 0.01) {
            $validationErrors[] = 'La imputación por códigos contables no coincide con el total mensual del comprobante.';
        }

        return [
            'receipt_number' => $month,
            'year' => $year,
            'month' => $month,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'period_label' => self::monthLabel($month) . ' ' . $year,
            'incomes' => $incomes,
            'detail_groups' => $detailGroups,
            'debit_entries' => $debitEntries,
            'credit_entries' => $creditEntries,
            'movement_count' => $incomes->count(),
            'total_collected' => $totalCollected,
            'amount_in_words' => self::amountToWords($totalCollected),
            'validation_errors' => array_values(array_unique($validationErrors)),
            'has_errors' => !empty($validationErrors),
        ];
    }

    public function validate(Collection $allIncomes, Collection $accountingAccounts, int $year, int $month): array
    {
        return $this->build($allIncomes, $accountingAccounts, $year, $month)['validation_errors'];
    }

    public static function extractAccountingCode(?string $concept): ?string
    {
        if (!$concept) {
            return null;
        }

        if (preg_match('/^\s*(\d{4,})\s*[-:]/', $concept, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function monthLabel(int $month): string
    {
        $months = [
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

        return $months[$month] ?? (string) $month;
    }

    private function validateAccountingCodes(Collection $incomes, Collection $accountsByCode): array
    {
        $errors = [];

        foreach ($incomes as $income) {
            $code = self::extractAccountingCode($income->name);
            $budgetItemLabel = trim(($income->fundingSource?->budgetItem?->code ?? 'Sin rubro') . ' - ' . ($income->fundingSource?->budgetItem?->name ?? ''));
            $fundingSourceLabel = trim(($income->fundingSource?->code ?? 'Sin fuente') . ' - ' . ($income->fundingSource?->name ?? ''));

            if (!$code) {
                $errors[] = "El ingreso \"{$income->name}\" del rubro {$budgetItemLabel} / fuente {$fundingSourceLabel} no tiene un codigo contable valido.";
            }
        }

        return $errors;
    }

    private function buildDetailGroups(Collection $incomes): array
    {
        return $incomes
            ->groupBy(function (Income $income) {
                return implode('|', [
                    (string) ($income->fundingSource?->budgetItem?->id ?? ''),
                    (string) ($income->fundingSource_id ?? ''),
                    (string) (self::extractAccountingCode($income->name) ?? 'SIN-CODIGO'),
                ]);
            })
            ->map(function (Collection $group) {
                /** @var Income $first */
                $first = $group->first();
                $budgetItem = $first->fundingSource?->budgetItem;
                $fundingSource = $first->fundingSource;
                $accountingCode = self::extractAccountingCode($first->name);

                return [
                    'budget_item_code' => $budgetItem?->code,
                    'budget_item_name' => $budgetItem?->name,
                    'funding_source_code' => $fundingSource?->code,
                    'funding_source_name' => $fundingSource?->name,
                    'accounting_code' => $accountingCode,
                    'concepts' => $group->pluck('name')->unique()->values()->all(),
                    'movement_count' => $group->count(),
                    'amount' => round((float) $group->sum(fn (Income $income) => (float) $income->amount), 2),
                ];
            })
            ->sortBy([
                fn (array $item) => $item['budget_item_code'] ?? '',
                fn (array $item) => $item['funding_source_code'] ?? '',
                fn (array $item) => $item['accounting_code'] ?? '',
            ])
            ->values()
            ->all();
    }

    private function buildCreditEntries(Collection $incomes, Collection $accountsByCode): array
    {
        return $incomes
            ->groupBy(function (Income $income) {
                $code = self::extractAccountingCode($income->name) ?? 'SIN-CODIGO';
                $label = $this->extractConceptLabel($income->name) ?? $income->name;

                return $code . '|' . $label;
            })
            ->map(function (Collection $group, string $groupKey) use ($accountsByCode) {
                [$accountingCode, $conceptLabel] = array_pad(explode('|', $groupKey, 2), 2, null);
                $accountingCode = $accountingCode ?: 'SIN-CODIGO';
                $conceptLabel = $conceptLabel ?: $this->extractConceptLabel($group->first()?->name);

                $account = $accountsByCode->get($accountingCode);
                $fallbackName = $conceptLabel;

                $hierarchy = $account
                    ? $this->buildAccountHierarchy($account)
                    : $this->buildFallbackHierarchy($accountingCode !== 'SIN-CODIGO' ? $accountingCode : null, $fallbackName);

                if ($account && !empty($hierarchy) && $fallbackName) {
                    $hierarchy[count($hierarchy) - 1]['name'] = $fallbackName;
                }

                return [
                    'accounting_code' => $accountingCode !== 'SIN-CODIGO' ? $accountingCode : null,
                    'concept_label' => $fallbackName,
                    'hierarchy' => $hierarchy,
                    'amount' => round((float) $group->sum(fn (Income $income) => (float) $income->amount), 2),
                    'movement_count' => $group->count(),
                    'concepts' => $group->pluck('name')->unique()->values()->all(),
                ];
            })
            ->sortBy([
                fn (array $entry) => $entry['accounting_code'] ?? 'ZZZZ',
                fn (array $entry) => $entry['concept_label'] ?? '',
            ])
            ->values()
            ->all();
    }

    private function buildDebitEntries(Collection $incomes, Collection $accountsByCode): array
    {
        $groups = [];

        foreach ($incomes as $income) {
            foreach ($income->bankAccounts as $bankLine) {
                $accountType = (string) ($bankLine->bankAccount?->account_type ?? '');
                $key = implode('|', [
                    (string) ($bankLine->bank_id ?? ''),
                    (string) ($bankLine->bank_account_id ?? ''),
                    $accountType,
                ]);

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'bank_name' => $bankLine->bank?->name ?? '',
                        'account_number' => $bankLine->bankAccount?->account_number ?? '',
                        'account_type_name' => $bankLine->bankAccount?->account_type_name ?? '',
                        'account_type' => $accountType,
                        'amount' => 0.0,
                    ];
                }

                $groups[$key]['amount'] += (float) ($bankLine->amount ?? 0);
            }
        }

        $entries = [];

        foreach ($groups as $group) {
            $account = $this->resolveDebitAccount($group['account_type'], $accountsByCode);

            $entries[] = [
                'hierarchy' => $account ? $this->buildAccountHierarchy($account) : [],
                'amount' => round((float) $group['amount'], 2),
                'bank_name' => $group['bank_name'],
                'account_number' => $group['account_number'],
                'account_type_name' => $group['account_type_name'],
            ];
        }

        return $entries;
    }

    private function resolveDebitAccount(string $accountType, Collection $accountsByCode): ?AccountingAccount
    {
        $preferredCode = $accountType === 'corriente' ? '111005' : '111010';

        return $accountsByCode->get($preferredCode)
            ?? $accountsByCode->get('1110')
            ?? $accountsByCode->get('1105');
    }

    private function buildAccountHierarchy(AccountingAccount $account): array
    {
        $hierarchy = [];
        $current = $account;

        while ($current) {
            array_unshift($hierarchy, [
                'code' => $current->code,
                'name' => $current->name,
                'level' => $current->level,
                'show_amount' => $current->id === $account->id,
            ]);

            $current = $current->parent;
        }

        return $hierarchy;
    }

    private function buildFallbackHierarchy(?string $code, ?string $name): array
    {
        return [[
            'code' => $code ?? 'N/A',
            'name' => $name ?: 'Concepto contable',
            'level' => 5,
            'show_amount' => true,
        ]];
    }

    private function extractConceptLabel(?string $concept): ?string
    {
        if (!$concept) {
            return null;
        }

        if (preg_match('/^\s*\d{4,}\s*[-:]\s*(.+)$/', $concept, $matches)) {
            return trim($matches[1]);
        }

        return trim($concept);
    }

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
        if ($number === 0) {
            return 'cero';
        }

        $units = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $teens = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciseis', 'diecisiete', 'dieciocho', 'diecinueve'];
        $tens = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($number < 0) {
            return 'menos ' . self::numberToWords(abs($number));
        }

        $result = '';

        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            $result .= $millions === 1 ? 'un millon ' : self::numberToWords($millions) . ' millones ';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            $result .= $thousands === 1 ? 'mil ' : self::numberToWords($thousands) . ' mil ';
            $number %= 1000;
        }

        if ($number >= 100) {
            if ($number === 100) {
                return trim($result . 'cien');
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
