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
                'contract.convocatoria.distributionDetails.expenseDistribution.expenseCode.accountingAccount.parent.parent.parent.parent',
                'contract.rps.cdp.budgetItem',
                'contract.rps.cdp.convocatoriaDistribution.expenseDistribution.expenseCode',
                'contract.rps.fundingSources.fundingSource',
                'contract.rps.fundingSources.bank',
                'contract.rps.fundingSources.bankAccount',
                'supplier',
                'cdp.budgetItem',
                'contractRp.fundingSources.fundingSource',
                'contractRp.fundingSources.bank',
                'contractRp.fundingSources.bankAccount',
                'bankLines.bankAccount.bank',
                'budgetItem',
                'expenseLines.expenseCode.accountingAccount.parent.parent.parent.parent',
                'creator',
            ])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        $amount = (float) $po->total;
        $netPayment = (float) $po->net_payment;
        $amountInWords = PrecontractualPdfController::amountToWords($netPayment);

        // Imputación contable - Débito (cuenta contable 2401 a petición del cliente)
        $debitEntries = [];
        $debitAccount = AccountingAccount::where('code', 'like', '2401%')->where('allows_movement', true)->first();

        if ($debitAccount) {
            $debitEntries[] = [
                'hierarchy' => $this->buildAccountHierarchy($debitAccount),
                'amount' => (float) $po->total,
            ];
        } else {
            // Hardcode fallback ya que en muchas bases de datos la 2401 no está creada
            $debitEntries[] = [
                'hierarchy' => [
                    [
                        'code' => '2401',
                        'name' => 'Adq. DE BIENES Y SERVICIOS NACIONALES',
                        'level' => 1,
                        'show_amount' => false,
                    ],
                    [
                        'code' => '240101',
                        'name' => 'Bienes y servicios',
                        'level' => 2,
                        'show_amount' => true,
                    ]
                ],
                'amount' => (float) $po->total,
            ];
        }

        // Crédito: Bancos (1110/111005) — buscar por nombre del banco real si está disponible
        // Se resuelve luego de determinar $bankName, por eso se inicializa como closure
        $resolveCreditBankHierarchy = function (string $bankName) {
            // Intentar encontrar la cuenta contable cuyo nombre coincida con el banco real
            if ($bankName) {
                $matched = \App\Models\AccountingAccount::where('code', 'like', '1110%')
                    ->where('allows_movement', true)
                    ->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($bankName) . '%'])
                    ->first();
                if ($matched) {
                    return $this->buildAccountHierarchy($matched);
                }
                // Si no existe la cuenta contable específica, construir jerarquía
                // con el nombre real del banco bajo 1110/111005
                return [
                    ['code' => '1110',   'name' => 'BANCOS Y CORPORACIONES', 'level' => 1, 'show_amount' => false],
                    ['code' => '111005', 'name' => 'BANCOS NACIONALES',      'level' => 2, 'show_amount' => false],
                    ['code' => '11100501','name' => strtoupper($bankName),   'level' => 3, 'show_amount' => true],
                ];
            }
            $first = \App\Models\AccountingAccount::where('code', 'like', '1110%')
                ->where('allows_movement', true)
                ->first();
            return $first ? $this->buildAccountHierarchy($first) : [];
        };

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
                $retentionRows[] = ['code' => '24072204', 'name' => 'OTROS IMP. MUNICIPALES (2% Produlto may)', 'amount' => (float) $po->estampilla_produlto_mayor, 'is_parent' => false];
            }
            if ((float) $po->retencion_ica > 0) {
                $retentionRows[] = ['code' => '24072209', 'name' => 'OTROS IMPUESTOS MUNICIPALES (ReteICA)', 'amount' => (float) $po->retencion_ica, 'is_parent' => false];
            }
        }

        // Imputación presupuestal
        $rpData = [];
        $bankName = '';
        $accountNumber = '';
        $fundingSourceName = '';
        $fundingSourceDetails = []; // Detalle por fuente [{name, amount, bank, account}]

        if ($po->payment_type === 'contract' && $po->contract) {
            $allSources = [];
            // Ratio para convertir montos brutos a neto (descuenta retenciones proporcionalmente)
            $netRatio = $amount > 0 ? $netPayment / $amount : 1.0;

            foreach ($po->contract->rps->where('status', 'active') as $rp) {
                foreach ($rp->fundingSources as $rpFs) {
                    $fsName = $rpFs->fundingSource?->name ?? '';
                    $allSources[] = $fsName;
                    if (!$bankName && $rpFs->bank) {
                        $bankName = $rpFs->bank->name ?? '';
                        $accountNumber = $rpFs->bankAccount?->account_number ?? '';
                    }
                    $fundingSourceDetails[] = [
                        'name' => $fsName,
                        'amount' => round((float) $rpFs->amount * $netRatio),
                        'bank' => $rpFs->bank?->name ?? '',
                        'account' => $rpFs->bankAccount?->account_number ?? '',
                    ];
                }

                // Obtener código de gasto desde el CDP vinculado al RP (distribución específica)
                $ecFromCdp = $rp->cdp?->convocatoriaDistribution?->expenseDistribution?->expenseCode;
                $rpData[] = [
                    'rp_number' => $rp->formatted_number,
                    'expense_code' => $ecFromCdp?->code ?? $rp->cdp?->budgetItem?->code ?? '',
                    'expense_name' => $ecFromCdp?->name ?? $rp->cdp?->budgetItem?->name ?? '',
                    'total_amount' => (float) $rp->total_amount,
                ];
            }
            $fundingSourceName = implode(' Y ', array_unique(array_filter($allSources)));
        } elseif ($po->contractRp) {
            $rp = $po->contractRp;
            foreach ($rp->fundingSources as $rpFs) {
                if (!$bankName && $rpFs->bank) {
                    $bankName = $rpFs->bank->name ?? '';
                    $accountNumber = $rpFs->bankAccount?->account_number ?? '';
                }
                $fundingSourceName = $rpFs->fundingSource?->name ?? '';
                $fundingSourceDetails[] = [
                    'name' => $rpFs->fundingSource?->name ?? '',
                    'amount' => (float) $rpFs->amount,
                    'bank' => $rpFs->bank?->name ?? '',
                    'account' => $rpFs->bankAccount?->account_number ?? '',
                ];
            }
            // Código y rubro: usar el código de gasto (expenseLines) si existe,
            // de lo contrario caer al budgetItem del CDP
            $directEc = $po->expenseLines->first()?->expenseCode;
            $rpData[] = [
                'rp_number' => $rp->formatted_number,
                'expense_code' => $directEc?->code ?? $po->cdp?->budgetItem?->code ?? '',
                'expense_name' => $directEc?->name ?? $po->cdp?->budgetItem?->name ?? '',
                'total_amount' => (float) $po->total,
            ];
        }

        // Banco del colegio desde bankLines (pagos directos / cuentas por pagar)
        if (!$bankName && $po->bankLines->isNotEmpty()) {
            $firstLine = $po->bankLines->first();
            $bankName = $firstLine->bankAccount?->bank?->name ?? '';
            $accountNumber = $firstLine->bankAccount?->account_number ?? '';
        }

        // Resolver jerarquía contable de crédito usando el banco real ya conocido
        $creditBankHierarchy = $resolveCreditBankHierarchy($bankName);

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
            'fundingSourceDetails' => $fundingSourceDetails,
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
                'contract.rps.cdp.convocatoriaDistribution.expenseDistribution.expenseCode',
                'supplier',
                'cdp.budgetItem',
                'budgetItem',
                'expenseLines.expenseCode',
            ])
            ->findOrFail($paymentOrderId);

        $school = School::findOrFail($schoolId);
        $supplier = $po->resolved_supplier;

        // Rubro presupuestal
        $budgetItemCode = '';
        $budgetItemName = '';
        if ($po->payment_type === 'contract' && $po->contract) {
            $rp = $po->contract->rps->where('status', 'active')->first();
            $ecFromCdp = $rp?->cdp?->convocatoriaDistribution?->expenseDistribution?->expenseCode;
            $budgetItemCode = $ecFromCdp?->code ?? $rp?->cdp?->budgetItem?->code ?? '';
            $budgetItemName = $ecFromCdp?->name ?? $rp?->cdp?->budgetItem?->name ?? '';
        } elseif ($po->expenseLines->isNotEmpty()) {
            // Pago directo: usar el código de gasto (expenseLines)
            $directEc = $po->expenseLines->first()?->expenseCode;
            $budgetItemCode = $directEc?->code ?? $po->cdp?->budgetItem?->code ?? '';
            $budgetItemName = $directEc?->name ?? $po->cdp?->budgetItem?->name ?? '';
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

    /**
     * Generar PDF de Certificado de Disponibilidad Presupuestal para pagos directos
     */
    public function certificadoCdp(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'cdp.budgetItem',
                'cdp.fundingSources.fundingSource',
                'cdp.fundingSources.budget.fundingSource',
                'expenseLines.expenseCode',
                'supplier',
            ])
            ->findOrFail($paymentOrderId);

        abort_if(!$po->cdp_id, 404, 'Esta orden de pago no tiene CDP asociado.');

        $cdp = $po->cdp;
        $school = School::findOrFail($schoolId);

        // Obtener código de gasto desde las líneas de la orden de pago
        $expenseCode = '';
        $expenseName = '';
        if ($po->expenseLines->isNotEmpty()) {
            $ec = $po->expenseLines->first()->expenseCode;
            if ($ec) {
                $expenseCode = $ec->code;
                $expenseName = $ec->name;
            }
        }

        // Fallback: buscar desde la distribución de gasto vinculada al budget
        if (!$expenseCode) {
            foreach ($cdp->fundingSources as $cdpFs) {
                if ($cdpFs->budget_id) {
                    $dist = \App\Models\ExpenseDistribution::where('budget_id', $cdpFs->budget_id)
                        ->with('expenseCode')
                        ->first();
                    if ($dist && $dist->expenseCode) {
                        $expenseCode = $dist->expenseCode->code;
                        $expenseName = $dist->expenseCode->name;
                        break;
                    }
                }
            }
        }

        if (!$expenseCode) {
            $expenseCode = $cdp->budgetItem?->code ?? '';
            $expenseName = $po->description ?? $cdp->budgetItem?->name ?? '';
        }

        // CDP muestra el valor disponible al momento de creación (available_balance_at_creation)
        $sources = [];
        $grandTotal = 0;
        foreach ($cdp->fundingSources as $cdpFs) {
            $availableAtCreation = (float) $cdpFs->available_balance_at_creation;
            $sources[] = [
                'name' => $cdpFs->fundingSource?->name ?? '',
                'amount' => $availableAtCreation > 0 ? $availableAtCreation : (float) $cdpFs->amount,
            ];
            $grandTotal += $availableAtCreation > 0 ? $availableAtCreation : (float) $cdpFs->amount;
        }

        $cdpRows = [[
            'cdp_number' => $cdp->formatted_number,
            'budget_item_code' => $expenseCode,
            'budget_item_name' => $expenseName,
            'sources' => $sources,
            'total_amount' => $grandTotal,
        ]];

        $convocatoriaData = new \stdClass();
        $convocatoriaData->object = $po->description ?? 'Pago directo';
        $convocatoriaData->start_date = $po->payment_date;
        $convocatoriaData->fiscal_year = $po->fiscal_year;
        $convocatoriaData->formatted_number = 'PD-' . $po->formatted_number;

        $pdf = Pdf::loadView('pdf.certificado-disponibilidad', [
            'convocatoria' => $convocatoriaData,
            'school' => $school,
            'cdpRows' => $cdpRows,
            'cdpNumber' => $cdp->formatted_number,
            'grandTotal' => $grandTotal,
            'isAddition' => false,
            'additionJustification' => null,
            'otrosiDate' => null,
            'additionContract' => null,
            'isDirectPayment' => true,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("certificado-cdp-{$cdp->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Registro Presupuestal para pagos directos
     */
    public function certificadoRp(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'contractRp.cdp.budgetItem',
                'contractRp.fundingSources.fundingSource',
                'expenseLines.expenseCode',
                'supplier',
            ])
            ->findOrFail($paymentOrderId);

        abort_if(!$po->contract_rp_id, 404, 'Esta orden de pago no tiene RP asociado.');

        $rp = $po->contractRp;
        $school = School::findOrFail($schoolId);
        $supplier = $po->supplier ?? $po->resolved_supplier;

        // Obtener código de gasto desde las líneas de la orden de pago
        $expenseCode = '';
        $expenseName = '';
        if ($po->expenseLines->isNotEmpty()) {
            $ec = $po->expenseLines->first()->expenseCode;
            if ($ec) {
                $expenseCode = $ec->code;
                $expenseName = $ec->name;
            }
        }

        // Fallback: buscar desde la distribución de gasto vinculada al budget del RP
        if (!$expenseCode) {
            foreach ($rp->fundingSources as $rpFs) {
                if ($rpFs->budget_id) {
                    $dist = \App\Models\ExpenseDistribution::where('budget_id', $rpFs->budget_id)
                        ->with('expenseCode')
                        ->first();
                    if ($dist && $dist->expenseCode) {
                        $expenseCode = $dist->expenseCode->code;
                        $expenseName = $dist->expenseCode->name;
                        break;
                    }
                }
            }
        }

        if (!$expenseCode) {
            $expenseCode = $rp->cdp?->budgetItem?->code ?? '';
            $expenseName = $po->description ?? $rp->cdp?->budgetItem?->name ?? '';
        }

        $sources = [];
        foreach ($rp->fundingSources as $rpFs) {
            $sources[] = [
                'name' => $rpFs->fundingSource?->name ?? '',
                'amount' => (float) $rpFs->amount,
            ];
        }

        $rpRows = [[
            'cdp_number' => $rp->cdp?->formatted_number ?? '',
            'expense_code' => $expenseCode,
            'expense_name' => $expenseName,
            'sources' => $sources,
            'total_amount' => (float) $rp->total_amount,
        ]];

        $contractData = new \stdClass();
        $contractData->formatted_number = 'PD-' . $po->formatted_number;
        $contractData->fiscal_year = $po->fiscal_year;
        $contractData->object = $po->description ?? 'Pago directo';
        $contractData->start_date = $po->payment_date;

        $pdf = Pdf::loadView('pdf.certificado-registro-presupuestal', [
            'contract' => $contractData,
            'school' => $school,
            'supplier' => $supplier,
            'rpNumber' => $rp->formatted_number,
            'rpRows' => $rpRows,
            'grandTotal' => (float) $rp->total_amount,
            'isAddition' => false,
            'additionJustification' => null,
            'otrosiDate' => null,
            'isDirectPayment' => true,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("certificado-rp-{$rp->formatted_number}.pdf");
    }

    /**
     * Comprobante de Contabilidad para pagos directos
     */
    public function comprobanteContabilidadDirecto(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'contractRp.cdp.budgetItem',
                'contractRp.fundingSources.fundingSource',
                'expenseLines.expenseCode.accountingAccount.parent.parent.parent.parent',
                'supplier',
            ])
            ->findOrFail($paymentOrderId);

        abort_if(!$po->contract_rp_id, 404, 'No tiene RP asociado.');

        $rp = $po->contractRp;
        $school = School::findOrFail($schoolId);
        $supplier = $po->supplier;

        $amount = (float) $rp->total_amount;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        // Débito: cuenta contable del código de gasto
        $debitEntries = [];
        $account = null;
        if ($po->expenseLines->isNotEmpty()) {
            $account = $po->expenseLines->first()->expenseCode?->accountingAccount;
        }
        if (!$account) {
            foreach ($rp->fundingSources as $rpFs) {
                if ($rpFs->budget_id) {
                    $dist = \App\Models\ExpenseDistribution::where('budget_id', $rpFs->budget_id)
                        ->with('expenseCode.accountingAccount.parent.parent.parent.parent')
                        ->first();
                    if ($dist?->expenseCode?->accountingAccount) {
                        $account = $dist->expenseCode->accountingAccount;
                        break;
                    }
                }
            }
        }
        if ($account) {
            $debitEntries[] = ['hierarchy' => $this->buildAccountHierarchy($account), 'amount' => $amount];
        }

        // Crédito: cuenta 2401
        $creditAccount = AccountingAccount::where('code', 'like', '2401%')->where('allows_movement', true)->first();
        $creditHierarchy = $creditAccount ? $this->buildAccountHierarchy($creditAccount) : [];

        // Código de gasto
        $expenseCode = '';
        $expenseName = '';
        if ($po->expenseLines->isNotEmpty()) {
            $ec = $po->expenseLines->first()->expenseCode;
            if ($ec) { $expenseCode = $ec->code; $expenseName = $ec->name; }
        }
        if (!$expenseCode) {
            foreach ($rp->fundingSources as $rpFs) {
                if ($rpFs->budget_id) {
                    $dist = \App\Models\ExpenseDistribution::where('budget_id', $rpFs->budget_id)->with('expenseCode')->first();
                    if ($dist?->expenseCode) { $expenseCode = $dist->expenseCode->code; $expenseName = $dist->expenseCode->name; break; }
                }
            }
        }

        $sources = [];
        foreach ($rp->fundingSources as $rpFs) {
            $sources[] = ['name' => $rpFs->fundingSource?->name ?? '', 'amount' => (float) $rpFs->amount];
        }

        $rpRows = [[
            'rp_number' => $rp->formatted_number,
            'expense_code' => $expenseCode ?: ($po->description ?? ''),
            'expense_name' => $expenseName ?: ($po->description ?? ''),
            'sources' => $sources,
            'total_amount' => $amount,
        ]];

        $contractData = new \stdClass();
        $contractData->formatted_number = 'PD-' . $po->formatted_number;
        $contractData->fiscal_year = $po->fiscal_year;
        $contractData->object = $po->description ?? 'Pago directo';
        $contractData->start_date = $po->payment_date;

        $pdf = Pdf::loadView('pdf.comprobante-contabilidad', [
            'contract' => $contractData,
            'school' => $school,
            'supplier' => $supplier,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'debitEntries' => $debitEntries,
            'creditHierarchy' => $creditHierarchy,
            'rpRows' => $rpRows,
            'isAddition' => false,
            'additionJustification' => null,
            'isDirectPayment' => true,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("comprobante-contabilidad-pd-{$po->formatted_number}.pdf");
    }

    /**
     * Certificado de Tesorería para pagos directos
     */
    public function certificadoTesoreriaDirecto(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with([
                'contractRp.cdp.budgetItem',
                'contractRp.fundingSources.fundingSource',
                'contractRp.fundingSources.bank',
                'contractRp.fundingSources.bankAccount',
                'expenseLines.expenseCode',
                'supplier',
            ])
            ->findOrFail($paymentOrderId);

        abort_if(!$po->contract_rp_id, 404, 'No tiene RP asociado.');

        $rp = $po->contractRp;
        $school = School::findOrFail($schoolId);

        $bankName = '';
        $accountNumber = '';
        $sourcesInfo = [];

        foreach ($rp->fundingSources as $rpFs) {
            if ($rpFs->bank && !$bankName) {
                $bankName = $rpFs->bank->name ?? '';
                $accountNumber = $rpFs->bankAccount?->account_number ?? '';
            }
            $sourcesInfo[] = ['name' => $rpFs->fundingSource?->name ?? '', 'amount' => (float) $rpFs->amount];
        }

        // Código de gasto
        $expenseCode = '';
        $expenseName = '';
        if ($po->expenseLines->isNotEmpty()) {
            $ec = $po->expenseLines->first()->expenseCode;
            if ($ec) { $expenseCode = $ec->code; $expenseName = $ec->name; }
        }
        if (!$expenseCode) {
            foreach ($rp->fundingSources as $rpFs) {
                if ($rpFs->budget_id) {
                    $dist = \App\Models\ExpenseDistribution::where('budget_id', $rpFs->budget_id)->with('expenseCode')->first();
                    if ($dist?->expenseCode) { $expenseCode = $dist->expenseCode->code; $expenseName = $dist->expenseCode->name; break; }
                }
            }
        }
        if (!$expenseCode) {
            $expenseCode = $po->description ?? '';
            $expenseName = $po->description ?? '';
        }

        $amount = (float) $rp->total_amount;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        $contractData = new \stdClass();
        $contractData->formatted_number = 'PD-' . $po->formatted_number;
        $contractData->fiscal_year = $po->fiscal_year;
        $contractData->object = $po->description ?? 'Pago directo';
        $contractData->start_date = $po->payment_date;

        $pdf = Pdf::loadView('pdf.certificado-tesoreria', [
            'contract' => $contractData,
            'school' => $school,
            'rpNumber' => $rp->formatted_number,
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'sourcesInfo' => $sourcesInfo,
            'budgetItemCode' => $expenseCode,
            'budgetItemName' => $expenseName,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'isDirectPayment' => true,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("certificado-tesoreria-pd-{$po->formatted_number}.pdf");
    }

    /**
     * Comprobante de Egreso para pago de impuestos (sin CDP/RP)
     */
    public function comprobanteEgresoImpuestos(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with(['supplier', 'taxLines', 'bankLines.bankAccount.bank', 'creator'])
            ->findOrFail($paymentOrderId);

        $school = \App\Models\School::findOrFail($schoolId);
        $supplier = $po->supplier;

        // Mapa tax_type => amount para acceso rápido en la vista
        $taxAmounts = $po->taxLines
            ->pluck('amount', 'tax_type')
            ->map(fn($a) => (float) $a)
            ->toArray();

        $amount = (float) $po->total;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.comprobante-egreso-impuestos', [
            'po'           => $po,
            'school'       => $school,
            'supplier'     => $supplier,
            'amount'       => $amount,
            'amountInWords'=> $amountInWords,
            'taxAmounts'   => $taxAmounts,
            'user'         => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("comprobante-egreso-impuestos-{$po->formatted_number}.pdf");
    }

    /**
     * Resolución de Pago para pago de impuestos (sin CDP/RP)
     */
    public function resolucionPagoImpuestos(Request $request, int $paymentOrderId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('postcontractual.view'), 403);

        $po = PaymentOrder::forSchool($schoolId)
            ->with(['supplier'])
            ->findOrFail($paymentOrderId);

        $school = \App\Models\School::findOrFail($schoolId);
        $supplier = $po->supplier;

        $amount = (float) $po->total;
        $amountInWords = PrecontractualPdfController::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.resolucion-pago-impuestos', [
            'po'           => $po,
            'school'       => $school,
            'supplier'     => $supplier,
            'amount'       => $amount,
            'amountInWords'=> $amountInWords,
            'user'         => auth()->user(),
        ]);

        $pdf->setPaper('letter');
        return $pdf->stream("resolucion-pago-impuestos-{$po->formatted_number}.pdf");
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
