<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ContractualPdfController extends Controller
{
    /**
     * Generar PDF de Certificado de Registro Presupuestal
     */
    public function certificadoRp(Request $request, int $contractId, int $rpId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'creator',
            ])
            ->findOrFail($contractId);

        // Buscar el RP específico
        $rp = $contract->rps->where('id', $rpId)->where('status', 'active')->first();
        abort_if(!$rp, 404, 'RP no encontrado.');

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        $rpNumber = $rp->formatted_number;

        // Obtener código de gasto desde la convocatoria
        $expenseCode = '';
        $expenseName = '';
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $expenseCode = $ec->code ?? '';
                    $expenseName = $ec->name ?? '';
                    break;
                }
            }
        }

        // Construir fila para este RP individual
        $sources = [];
        foreach ($rp->fundingSources as $rpFs) {
            $sources[] = [
                'name' => $rpFs->fundingSource?->name ?? '',
                'amount' => (float) $rpFs->amount,
            ];
        }

        $rpRows = [[
            'cdp_number' => $rp->cdp?->formatted_number ?? '',
            'expense_code' => $expenseCode ?: ($rp->cdp?->budgetItem?->code ?? ''),
            'expense_name' => $expenseName ?: ($rp->cdp?->budgetItem?->name ?? ''),
            'sources' => $sources,
            'total_amount' => (float) $rp->total_amount,
        ]];

        $grandTotal = (float) $rp->total_amount;

        $pdf = Pdf::loadView('pdf.certificado-registro-presupuestal', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'rpNumber' => $rpNumber,
            'rpRows' => $rpRows,
            'grandTotal' => $grandTotal,
            'isAddition' => (bool) $rp->is_addition,
            'additionJustification' => $rp->addition_justification,
            'otrosiDate' => $rp->otrosi_date,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-rp-{$rpNumber}-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Comprobante de Contabilidad
     */
    public function comprobanteContabilidad(Request $request, int $contractId, int $rpId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode.accountingAccount.parent.parent.parent.parent',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'creator',
            ])
            ->findOrFail($contractId);

        // Buscar el RP específico
        $rp = $contract->rps->where('id', $rpId)->where('status', 'active')->first();
        abort_if(!$rp, 404, 'RP no encontrado.');

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        $amount = (float) $rp->total_amount;
        $amountInWords = self::amountToWords($amount);

        // Imputación contable (débito: cuenta contable del código de gasto)
        $debitEntries = [];
        $account = null;

        // Buscar la cuenta contable desde el ExpenseCode de la convocatoria
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec && $ec->accountingAccount) {
                    $account = $ec->accountingAccount;
                    break;
                }
            }
        }

        // Fallback: cuenta del rubro presupuestal del CDP
        if (!$account) {
            $account = $rp->cdp?->budgetItem?->accountingAccount;
        }

        if ($account) {
            $hierarchy = $this->buildAccountHierarchy($account);
            $debitEntries[] = [
                'hierarchy' => $hierarchy,
                'amount' => $amount,
            ];
        }

        // Crédito: cuenta 2401
        $creditAccount = \App\Models\AccountingAccount::where('code', 'like', '2401%')
            ->where('allows_movement', true)
            ->first();
        $creditHierarchy = $creditAccount ? $this->buildAccountHierarchy($creditAccount) : [];

        // Código de gasto desde la convocatoria
        $expenseCode = '';
        $expenseName = '';
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $expenseCode = $ec->code ?? '';
                    $expenseName = $ec->name ?? '';
                    break;
                }
            }
        }

        $sources = [];
        foreach ($rp->fundingSources as $rpFs) {
            $sources[] = [
                'name' => $rpFs->fundingSource?->name ?? '',
                'amount' => (float) $rpFs->amount,
            ];
        }

        $rpRows = [[
            'rp_number' => $rp->formatted_number,
            'expense_code' => $expenseCode ?: ($rp->cdp?->budgetItem?->code ?? ''),
            'expense_name' => $expenseName ?: ($rp->cdp?->budgetItem?->name ?? ''),
            'sources' => $sources,
            'total_amount' => $amount,
        ]];

        $pdf = Pdf::loadView('pdf.comprobante-contabilidad', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'debitEntries' => $debitEntries,
            'creditHierarchy' => $creditHierarchy,
            'rpRows' => $rpRows,
            'isAddition' => (bool) $rp->is_addition,
            'additionJustification' => $rp->addition_justification,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("comprobante-contabilidad-rp-{$rp->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Disponibilidad de Tesorería
     */
    public function certificadoTesoreria(Request $request, int $contractId, int $rpId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'rps.fundingSources.bank',
                'rps.fundingSources.bankAccount',
            ])
            ->findOrFail($contractId);

        $rp = $contract->rps->where('id', $rpId)->where('status', 'active')->first();
        abort_if(!$rp, 404, 'RP no encontrado.');

        $school = School::findOrFail($schoolId);

        $rpNumber = $rp->formatted_number;

        $bankName = '';
        $accountNumber = '';
        $sourcesInfo = [];

        foreach ($rp->fundingSources as $rpFs) {
            if ($rpFs->bank && !$bankName) {
                $bankName = $rpFs->bank->name ?? '';
                $accountNumber = $rpFs->bankAccount?->account_number ?? '';
            }
            $sourcesInfo[] = [
                'name' => $rpFs->fundingSource?->name ?? '',
                'amount' => (float) $rpFs->amount,
            ];
        }

        // Fallback: si este RP no tiene banco, buscar en otros RPs del contrato
        if (!$bankName) {
            foreach ($contract->rps as $otherRp) {
                if ($otherRp->id === $rp->id) continue;
                foreach ($otherRp->fundingSources as $otherFs) {
                    if ($otherFs->bank) {
                        $bankName = $otherFs->bank->name ?? '';
                        $accountNumber = $otherFs->bankAccount?->account_number ?? '';
                        break 2;
                    }
                }
            }
        }

        $expenseCode = '';
        $expenseName = '';
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $expenseCode = $ec->code ?? '';
                    $expenseName = $ec->name ?? '';
                    break;
                }
            }
        }
        if (!$expenseCode) {
            $expenseCode = $rp->cdp?->budgetItem?->code ?? '';
            $expenseName = $rp->cdp?->budgetItem?->name ?? '';
        }

        $amount = (float) $rp->total_amount;
        $amountInWords = self::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.certificado-tesoreria', [
            'contract' => $contract,
            'school' => $school,
            'rpNumber' => $rpNumber,
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'sourcesInfo' => $sourcesInfo,
            'budgetItemCode' => $expenseCode,
            'budgetItemName' => $expenseName,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-tesoreria-rp-{$rpNumber}.pdf");
    }

    /**
     * Generar PDF de Acta de Inicio del Contrato
     */
    public function actaInicio(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'supervisor',
                'rps',
                'creator',
            ])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        // Números de RP activos
        $rpNumbers = $contract->rps
            ->where('status', 'active')
            ->map(fn($rp) => $rp->formatted_number)
            ->implode(', ');

        $pdf = Pdf::loadView('pdf.acta-inicio-contrato', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'rpNumbers' => $rpNumbers,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("acta-inicio-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Acta de Finalización y Liquidación del Contrato
     */
    public function actaFinalizacion(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'supervisor',
                'rps',
                'paymentOrders',
            ])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        $rpNumbers = $contract->rps
            ->where('status', 'active')
            ->map(fn($rp) => $rp->formatted_number)
            ->implode(', ');

        // Pagos realizados
        $totalPaid = (float) $contract->paymentOrders
            ->whereIn('status', ['approved', 'paid'])
            ->sum('net_amount');

        $valorOrden = (float) $contract->total;
        $valorAdicional = (float) ($contract->addition_amount ?? 0);
        $valorPorPagar = $valorOrden - $totalPaid;
        if ($valorPorPagar < 0) $valorPorPagar = 0;

        $pdf = Pdf::loadView('pdf.acta-finalizacion-contrato', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'rpNumbers' => $rpNumbers,
            'valorOrden' => $valorOrden,
            'valorAdicional' => $valorAdicional,
            'totalPaid' => $totalPaid,
            'valorPorPagar' => $valorPorPagar,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("acta-finalizacion-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Informe de Supervisión
     */
    public function informeSupervision(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with(['school', 'supplier', 'supervisor'])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        $pdf = Pdf::loadView('pdf.informe-supervision', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("informe-supervision-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Inhabilidades e Incompatibilidades
     */
    public function certificadoInhabilidades(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with(['school', 'supplier'])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        $pdf = Pdf::loadView('pdf.certificado-inhabilidades', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-inhabilidades-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Informe de Actividades
     */
    public function informeActividades(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with(['school', 'supplier', 'supervisor'])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        $pdf = Pdf::loadView('pdf.informe-actividades', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("informe-actividades-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Resolución Designación de Supervisión
     */
    public function resolucionSupervision(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with(['school', 'supplier', 'supervisor', 'rps'])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        $rpNumbers = $contract->rps
            ->where('status', 'active')
            ->map(fn($rp) => $rp->formatted_number)
            ->implode(', ');

        $pdf = Pdf::loadView('pdf.resolucion-supervision', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'rpNumbers' => $rpNumbers,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("resolucion-supervision-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF del Contrato
     */
    public function contratoPdf(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier.department',
                'supplier.municipality',
                'supervisor',
                'convocatoria.cdps.budgetItem',
                'convocatoria.cdps.fundingSources.fundingSource',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'creator',
            ])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        $amount = (float) $contract->total;
        $amountInWords = self::amountToWords($amount);

        // CDPs - usar códigos de gasto
        $cdpRows = [];
        $activeCdps = $contract->convocatoria?->cdps?->where('status', '!=', 'cancelled') ?? collect();

        // Obtener códigos de gasto desde las distribuciones
        $ecMap = [];
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $ecMap[] = ['code' => $ec->code ?? '', 'name' => $ec->name ?? ''];
                }
            }
        }

        foreach ($activeCdps as $cdpIndex => $cdp) {
            $ecData = $ecMap[$cdpIndex] ?? null;
            foreach ($cdp->fundingSources as $cdpFs) {
                $cdpRows[] = [
                    'cdp_number' => $cdp->formatted_number,
                    'budget_item_code' => $ecData['code'] ?? $cdp->budgetItem?->code ?? '',
                    'budget_item_name' => $ecData['name'] ?? $cdp->budgetItem?->name ?? '',
                    'funding_source' => $cdpFs->fundingSource?->name ?? '',
                    'amount' => (float) $cdpFs->amount,
                ];
            }
        }

        // Fuentes de financiación concatenadas
        $fundingSourceNames = [];
        foreach ($activeCdps as $cdp) {
            foreach ($cdp->fundingSources as $cdpFs) {
                $name = $cdpFs->fundingSource?->name ?? '';
                if ($name && !in_array($name, $fundingSourceNames)) {
                    $fundingSourceNames[] = $name;
                }
            }
        }

        // Códigos de gasto
        $expenseCodeRows = [];
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $expenseCodeRows[] = $ec->code . ' - ' . $ec->name;
                }
            }
        }

        $pdf = Pdf::loadView('pdf.contrato', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'cdpRows' => $cdpRows,
            'expenseCodeRows' => $expenseCodeRows,
            'fundingSourceText' => implode(' Y ', $fundingSourceNames) ?: 'N/A',
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Hoja de Ruta
     */
    public function hojaRuta(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria.cdps.fundingSources.fundingSource',
                'rps.fundingSources.fundingSource',
            ])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);

        // Fuentes de financiación
        $fundingSources = [];
        if ($contract->convocatoria) {
            foreach ($contract->convocatoria->cdps->where('status', '!=', 'cancelled') as $cdp) {
                foreach ($cdp->fundingSources as $cdpFs) {
                    $fs = $cdpFs->fundingSource;
                    if ($fs) {
                        $fundingSources[] = $fs->name . ' ($' . number_format($cdpFs->amount, 2, ',', '.') . ')';
                    }
                }
            }
        }
        $fundingSourceText = implode(', ', array_unique($fundingSources)) ?: 'N/A';

        $pdf = Pdf::loadView('pdf.hoja-ruta', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $contract->supplier,
            'fundingSourceText' => $fundingSourceText,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("hoja-ruta-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Construir la jerarquía de una cuenta contable (desde la raíz hasta la cuenta).
     */
    private function buildAccountHierarchy(?\App\Models\AccountingAccount $account): array
    {
        if (!$account) {
            return [];
        }

        $hierarchy = [];
        $current = $account;

        while ($current) {
            array_unshift($hierarchy, [
                'code' => $current->code,
                'name' => $current->name,
                'level' => $current->level,
                'show_amount' => ($current->id === $account->id),
            ]);
            $current = $current->parent;
        }

        return $hierarchy;
    }

    /**
     * Convertir monto a palabras en español
     */
    public static function amountToWords(float $amount): string
    {
        return \App\Http\Controllers\PrecontractualPdfController::amountToWords($amount);
    }
}
