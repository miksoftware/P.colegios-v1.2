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
    public function certificadoRp(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'creator',
            ])
            ->findOrFail($contractId);

        $activeRps = $contract->rps->where('status', 'active');
        abort_if($activeRps->isEmpty(), 404, 'No hay RPs asignados.');

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        // Primer RP para el número principal
        $firstRp = $activeRps->first();
        $rpNumber = $firstRp->formatted_number;

        // Construir filas: CDP, código rubro, nombre rubro, fuentes financiación, valor
        $rpRows = [];
        foreach ($activeRps as $rp) {
            $sources = [];
            foreach ($rp->fundingSources as $rpFs) {
                $sources[] = [
                    'name' => $rpFs->fundingSource?->name ?? '',
                    'amount' => (float) $rpFs->amount,
                ];
            }
            $rpRows[] = [
                'cdp_number' => $rp->cdp?->formatted_number ?? '',
                'budget_item_code' => $rp->cdp?->budgetItem?->code ?? '',
                'budget_item_name' => $rp->cdp?->budgetItem?->name ?? '',
                'sources' => $sources,
                'total_amount' => (float) $rp->total_amount,
            ];
        }

        $grandTotal = collect($rpRows)->sum('total_amount');

        $pdf = Pdf::loadView('pdf.certificado-registro-presupuestal', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'rpNumber' => $rpNumber,
            'rpRows' => $rpRows,
            'grandTotal' => $grandTotal,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-rp-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Comprobante de Contabilidad
     */
    public function comprobanteContabilidad(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'convocatoria',
                'rps.cdp.budgetItem.accountingAccount.parent.parent.parent.parent',
                'rps.fundingSources.fundingSource',
                'creator',
            ])
            ->findOrFail($contractId);

        $school = School::findOrFail($schoolId);
        $supplier = $contract->supplier;

        $amount = (float) $contract->total;
        $amountInWords = self::amountToWords($amount);

        // Construir imputación contable (débito: cuentas de gasto del rubro)
        $debitEntries = [];
        $activeRps = $contract->rps->where('status', 'active');
        foreach ($activeRps as $rp) {
            $account = $rp->cdp?->budgetItem?->accountingAccount;
            if ($account) {
                $hierarchy = $this->buildAccountHierarchy($account);
                $debitEntries[] = [
                    'hierarchy' => $hierarchy,
                    'amount' => (float) $rp->total_amount,
                ];
            }
        }

        // Crédito: cuenta 2401 (Adquisición de bienes y servicios nacionales) / 240101 (Bienes y servicios)
        // Buscar cuenta contable genérica de pasivos para crédito
        $creditAccount = \App\Models\AccountingAccount::where('code', 'like', '2401%')
            ->where('allows_movement', true)
            ->first();
        $creditHierarchy = $creditAccount ? $this->buildAccountHierarchy($creditAccount) : [];

        // Imputación presupuestal
        $rpRows = [];
        foreach ($activeRps as $rp) {
            $sources = [];
            foreach ($rp->fundingSources as $rpFs) {
                $sources[] = [
                    'name' => $rpFs->fundingSource?->name ?? '',
                    'amount' => (float) $rpFs->amount,
                ];
            }
            $rpRows[] = [
                'rp_number' => $rp->formatted_number,
                'budget_item_code' => $rp->cdp?->budgetItem?->code ?? '',
                'budget_item_name' => $rp->cdp?->budgetItem?->name ?? '',
                'sources' => $sources,
                'total_amount' => (float) $rp->total_amount,
            ];
        }

        $pdf = Pdf::loadView('pdf.comprobante-contabilidad', [
            'contract' => $contract,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'debitEntries' => $debitEntries,
            'creditHierarchy' => $creditHierarchy,
            'rpRows' => $rpRows,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("comprobante-contabilidad-contrato-{$contract->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Disponibilidad de Tesorería
     */
    public function certificadoTesoreria(Request $request, int $contractId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $contract = Contract::forSchool($schoolId)
            ->with([
                'school',
                'supplier',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
                'rps.fundingSources.bank',
                'rps.fundingSources.bankAccount',
            ])
            ->findOrFail($contractId);

        $activeRps = $contract->rps->where('status', 'active');
        abort_if($activeRps->isEmpty(), 404, 'No hay RPs asignados.');

        $school = School::findOrFail($schoolId);

        $firstRp = $activeRps->first();
        $rpNumber = $firstRp->formatted_number;

        // Datos bancarios (del primer RP funding source que tenga banco)
        $bankName = '';
        $accountNumber = '';
        $sourcesInfo = [];

        foreach ($activeRps as $rp) {
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
        }

        // Rubro del primer CDP
        $budgetItemCode = $firstRp->cdp?->budgetItem?->code ?? '';
        $budgetItemName = $firstRp->cdp?->budgetItem?->name ?? '';

        $amount = (float) collect($activeRps)->sum('total_amount');
        $amountInWords = self::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.certificado-tesoreria', [
            'contract' => $contract,
            'school' => $school,
            'rpNumber' => $rpNumber,
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'sourcesInfo' => $sourcesInfo,
            'budgetItemCode' => $budgetItemCode,
            'budgetItemName' => $budgetItemName,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-tesoreria-contrato-{$contract->formatted_number}.pdf");
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

        // CDPs
        $cdpRows = [];
        $activeCdps = $contract->convocatoria?->cdps?->where('status', '!=', 'cancelled') ?? collect();
        foreach ($activeCdps as $cdp) {
            foreach ($cdp->fundingSources as $cdpFs) {
                $cdpRows[] = [
                    'cdp_number' => $cdp->formatted_number,
                    'budget_item_code' => $cdp->budgetItem?->code ?? '',
                    'budget_item_name' => $cdp->budgetItem?->name ?? '',
                    'funding_source' => $cdpFs->fundingSource?->name ?? '',
                    'amount' => (float) $cdpFs->amount,
                ];
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
