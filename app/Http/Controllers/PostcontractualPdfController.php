<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\PaymentOrder;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PostcontractualPdfController extends Controller
{
    /**
     * Generar PDF de Comprobante de Egreso
     */
    public function comprobanteEgreso(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'contract.supplier',
                'contract.convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'contract.rps.cdp.budgetItem.accountingAccount.parent.parent.parent.parent',
                'contract.rps.fundingSources.fundingSource',
                'contract.rps.fundingSources.bank',
                'contract.rps.fundingSources.bankAccount',
                'supplier',
                'cdp.budgetItem.accountingAccount.parent.parent.parent.parent',
                'contractRp.fundingSources.fundingSource',
                'contractRp.fundingSources.bank',
                'contractRp.fundingSources.bankAccount',
                'budgetItem.accountingAccount.parent.parent.parent.parent',
                'expenseLines.expenseCode',
                'creator',
            ])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        $amount = (float) $po->total;
        $netPayment = (float) $po->net_payment;
        $amountInWords = PrecontractualPdfController::amountToWords($netPayment);

        // Imputación contable - Débito (cuenta de gasto del rubro)
        $debitEntries = [];
        if ($po->payment_type === 'contract' && $po->contract) {
            foreach ($po->contract->rps->where('status', 'active') as $rp) {
                $account = $rp->cdp?->budgetItem?->accountingAccount;
                if ($account) {
                    $debitEntries[] = [
                        'hierarchy' => $this->buildAccountHierarchy($account),
                        'amount' => (float) $po->total,
                    ];
                    break; // Solo necesitamos una entrada de débito
                }
            }
        } elseif ($po->budgetItem?->accountingAccount) {
            $debitEntries[] = [
                'hierarchy' => $this->buildAccountHierarchy($po->budgetItem->accountingAccount),
                'amount' => (float) $po->total,
            ];
        }

        // Crédito: Bancos (1110/111005)
        $creditBankAccount = AccountingAccount::where('code', 'like', '1110%')
            ->where('allows_movement', true)->first();
        $creditBankHierarchy = $creditBankAccount ? $this->buildAccountHierarchy($creditBankAccount) : [];

        // Retenciones (créditos adicionales)
        $retentionRows = [];
        if ((float) $po->retefuente > 0) {
            $retentionRows[] = ['code' => '2436', 'name' => 'RETENCIÓN EN LA FUENTE', 'amount' => 0, 'is_parent' => true];
            $concept = $po->retention_concept ?? 'servicios';
            $codeMap = ['servicios' => '243605', 'compras' => '243608', 'honorarios' => '243603'];
            $nameMap = ['servicios' => 'Servicios', 'compras' => 'Compras', 'honorarios' => 'Honorarios'];
            $retentionRows[] = [
                'code' => $codeMap[$concept] ?? '243605',
                'name' => $nameMap[$concept] ?? 'Retenciones',
                'amount' => (float) $po->retefuente,
                'is_parent' => false,
            ];
        }
        if ((float) $po->reteiva > 0) {
            $retentionRows[] = ['code' => '243625', 'name' => 'Impuesto a las Ventas Retenido 15%', 'amount' => (float) $po->reteiva, 'is_parent' => false];
        }
        if ((float) $po->estampilla_procultura > 0 || (float) $po->estampilla_produlto_mayor > 0 || (float) $po->retencion_ica > 0) {
            $retentionRows[] = ['code' => '2407', 'name' => 'IMPUESTOS TASAS, CONTRIBUCIONES', 'amount' => 0, 'is_parent' => true];
            if ((float) $po->estampilla_procultura > 0) {
                $retentionRows[] = ['code' => '24072202', 'name' => 'OTROS IMP. MUNICIPALES (2% Procultura)', 'amount' => (float) $po->estampilla_procultura, 'is_parent' => false];
            }
            if ((float) $po->estampilla_produlto_mayor > 0) {
                $retentionRows[] = ['code' => '24408004', 'name' => 'OTROS IMP. MUNICIPALES (2% Produlto may)', 'amount' => (float) $po->estampilla_produlto_mayor, 'is_parent' => false];
            }
            if ((float) $po->retencion_ica > 0) {
                $retentionRows[] = ['code' => '244004', 'name' => 'OTROS IMPUESTOS MUNICIPALES (ReteICA)', 'amount' => (float) $po->retencion_ica, 'is_parent' => false];
            }
        }

        // Imputación presupuestal
        $rpData = [];
        $bankName = '';
        $accountNumber = '';
        $fundingSourceName = '';

        // Obtener códigos de gasto desde la convocatoria del contrato
        $expenseCodeMap = [];
        if ($po->payment_type === 'contract' && $po->contract?->convocatoria) {
            foreach ($po->contract->convocatoria->distributionDetails as $dd) {
                $ec = $dd->expenseDistribution?->expenseCode;
                if ($ec) {
                    $expenseCodeMap[] = [
                        'code' => $ec->code ?? '',
                        'name' => $ec->name ?? '',
                    ];
                }
            }
        }

        if ($po->payment_type === 'contract' && $po->contract) {
            $rpIndex = 0;
            foreach ($po->contract->rps->where('status', 'active') as $rp) {
                $sources = [];
                foreach ($rp->fundingSources as $rpFs) {
                    $sources[] = $rpFs->fundingSource?->name ?? '';
                    if (!$bankName && $rpFs->bank) {
                        $bankName = $rpFs->bank->name ?? '';
                        $accountNumber = $rpFs->bankAccount?->account_number ?? '';
                    }
                }
                $fundingSourceName = implode(' Y ', array_filter($sources));

                $ecData = $expenseCodeMap[$rpIndex] ?? null;

                $rpData[] = [
                    'rp_number' => $rp->formatted_number,
                    'expense_code' => $ecData['code'] ?? $rp->cdp?->budgetItem?->code ?? '',
                    'expense_name' => $ecData['name'] ?? $rp->cdp?->budgetItem?->name ?? '',
                    'total_amount' => (float) $rp->total_amount,
                ];
                $rpIndex++;
                break;
            }
        } elseif ($po->contractRp) {
            $rp = $po->contractRp;
            foreach ($rp->fundingSources as $rpFs) {
                if (!$bankName && $rpFs->bank) {
                    $bankName = $rpFs->bank->name ?? '';
                    $accountNumber = $rpFs->bankAccount?->account_number ?? '';
                }
                $fundingSourceName = $rpFs->fundingSource?->name ?? '';
            }
            $rpData[] = [
                'rp_number' => $rp->formatted_number,
                'expense_code' => $po->cdp?->budgetItem?->code ?? '',
                'expense_name' => $po->cdp?->budgetItem?->name ?? '',
                'total_amount' => (float) $po->total,
            ];
        }

        // Banco del proveedor (si no se encontró del RP)
        if (!$bankName && $po->supplier_bank_name) {
            $bankName = $po->supplier_bank_name;
            $accountNumber = $po->supplier_account_number ?? '';
        }

        $pdf = Pdf::loadView('pdf.comprobante-egreso', [
            'po' => $po,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'netPayment' => $netPayment,
            'amountInWords' => $amountInWords,
            'debitEntries' => $debitEntries,
            'creditBankHierarchy' => $creditBankHierarchy,
            'retentionRows' => $retentionRows,
            'rpData' => $rpData,
            'fundingSourceName' => $fundingSourceName,
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("comprobante-egreso-{$po->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Orden de Pago
     */
    public function ordenPago(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'contract.supplier',
                'contract.rps.cdp.budgetItem',
                'supplier',
                'cdp.budgetItem',
                'budgetItem',
            ])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        // Rubro presupuestal
        $budgetItemCode = '';
        $budgetItemName = '';
        if ($po->payment_type === 'contract' && $po->contract) {
            $rp = $po->contract->rps->where('status', 'active')->first();
            $budgetItemCode = $rp?->cdp?->budgetItem?->code ?? '';
            $budgetItemName = $rp?->cdp?->budgetItem?->name ?? '';
        } elseif ($po->cdp?->budgetItem) {
            $budgetItemCode = $po->cdp->budgetItem->code;
            $budgetItemName = $po->cdp->budgetItem->name;
        } elseif ($po->budgetItem) {
            $budgetItemCode = $po->budgetItem->code;
            $budgetItemName = $po->budgetItem->name;
        }

        $amount = (float) $po->total;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.orden-pago', [
            'po' => $po,
            'school' => $school,
            'supplier' => $supplier,
            'budgetItemCode' => $budgetItemCode,
            'budgetItemName' => $budgetItemName,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("orden-pago-{$po->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Constancia de Recibido a Satisfacción
     */
    public function constanciaRecibido(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with(['contract.supplier', 'supplier'])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        $pdf = Pdf::loadView('pdf.constancia-recibido', [
            'po' => $po,
            'school' => $school,
            'supplier' => $supplier,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("constancia-recibido-{$po->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Retenciones
     */
    public function certificadoRetenciones(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with(['contract.supplier', 'supplier'])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        // Desglose de retención por concepto
        $honorarios = 0;
        $servicios = 0;
        $compras = 0;
        $concept = $po->retention_concept ?? '';
        $retefuente = (float) $po->retefuente;

        if ($concept === 'honorarios') $honorarios = $retefuente;
        elseif ($concept === 'servicios') $servicios = $retefuente;
        elseif ($concept === 'compras') $compras = $retefuente;

        $pdf = Pdf::loadView('pdf.certificado-retenciones', [
            'po' => $po,
            'school' => $school,
            'supplier' => $supplier,
            'honorarios' => $honorarios,
            'servicios' => $servicios,
            'compras' => $compras,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-retenciones-{$po->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Documento Soporte (no obligados a facturar)
     */
    public function documentoSoporte(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with(['contract.supplier', 'supplier'])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        $amount = (float) $po->total;
        $subtotal = (float) $po->subtotal;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.documento-soporte', [
            'po' => $po,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'subtotal' => $subtotal,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("documento-soporte-{$po->formatted_number}.pdf");
    }

    private function buildAccountHierarchy(?AccountingAccount $account): array
    {
        if (!$account) return [];
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
}
