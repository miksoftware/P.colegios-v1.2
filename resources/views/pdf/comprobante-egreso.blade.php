<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Egreso No. {{ $po->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
        .container { padding: 12px 20px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 6px 10px; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-sub { font-size: 7px; color: #555; }
        .doc-title { font-size: 10px; font-weight: bold; color: #1e3a5f; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 10px; font-size: 9px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; text-transform: uppercase; font-size: 8px; }

        .section-title { background: #1e3a5f; color: #fff; padding: 3px 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        .acct-table { width: 100%; border-collapse: collapse; }
        .acct-table th { background: #e8edf3; font-size: 7px; text-transform: uppercase; padding: 3px 6px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .acct-table td { padding: 3px 6px; border: 1px solid #ccc; font-size: 8px; }
        .acct-table .code-col { width: 10%; }
        .acct-table .name-col { width: 50%; }
        .acct-table .debit-col { width: 20%; text-align: right; }
        .acct-table .credit-col { width: 20%; text-align: right; }
        .acct-table .level-1 { font-weight: bold; }
        .acct-table .level-2 { padding-left: 12px; font-weight: bold; }
        .acct-table .level-3 { padding-left: 20px; font-size: 7.5px; }
        .acct-table .level-4 { padding-left: 28px; font-size: 7.5px; }
        .acct-table .level-5 { padding-left: 36px; font-size: 7.5px; color: #444; }
        .acct-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        .pres-table { width: 100%; border-collapse: collapse; }
        .pres-table td { padding: 3px 10px; font-size: 9px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .pres-label { font-weight: bold; color: #1e3a5f; width: 150px; font-size: 8px; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 8px; }
        .sig-line { border-top: 1px solid #333; width: 190px; margin: 0 auto; padding-top: 2px; }
        .sig-name { font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .sig-role { font-size: 7px; color: #666; }

        .footer { margin-top: 6px; text-align: center; font-size: 6px; color: #999; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td>
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="school-sub">{{ $school->municipality ?? '' }}</div>
                    @if($school->dane_code)<div class="school-sub">{{ $school->dane_code }}</div>@endif
                </td>
                <td style="text-align: right;">
                    <div class="doc-title">COMPROBANTE DE EGRESO No. {{ $po->formatted_number }}</div>
                </td>
            </tr>
        </table>

        {{-- CIUDAD, FECHA, MONTO --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 110px;">Ciudad y Fecha:</td>
                <td>{{ $school->municipality ?? '' }} &nbsp; {{ $po->payment_date?->format('d/m/Y') ?? $po->created_at?->format('d/m/Y') ?? '' }}</td>
                <td class="bold" style="text-align: right; width: 150px;">${{ number_format($netPayment, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Pagado a:</td>
                <td colspan="2">{{ $supplier->full_name ?? 'N/A' }}</td>
            </tr>
            @if($po->invoice_number)
            <tr>
                <td class="info-label">Factura N.:</td>
                <td colspan="2">{{ $po->invoice_number }}</td>
            </tr>
            @endif
            <tr>
                <td class="info-label">Por concepto de:</td>
                <td colspan="2">{{ $po->contract?->object ?? $po->description ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">La suma de (en letras):</td>
                <td colspan="2" class="bold" style="text-transform: uppercase;">{{ $amountInWords }}</td>
            </tr>
        </table>

        {{-- IMPUTACIÓN CONTABLE --}}
        <div class="section-title">Imputación Contable</div>
        <table class="acct-table">
            <thead>
                <tr>
                    <th class="code-col">Código</th>
                    <th class="name-col">Denominación</th>
                    <th class="debit-col">Débitos</th>
                    <th class="credit-col">Créditos</th>
                </tr>
            </thead>
            <tbody>
                {{-- Débito: cuenta de gasto --}}
                @foreach($debitEntries as $entry)
                    @foreach($entry['hierarchy'] as $acct)
                    <tr>
                        <td class="code-col">{{ $acct['code'] }}</td>
                        <td class="name-col level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td class="debit-col">@if($acct['show_amount'])${{ number_format($entry['amount'], 2, ',', '.') }}@endif</td>
                        <td class="credit-col"></td>
                    </tr>
                    @endforeach
                @endforeach

                <tr><td colspan="4" style="padding: 1px;">&nbsp;</td></tr>

                {{-- Crédito: Bancos --}}
                @if(count($creditBankHierarchy) > 0)
                    @foreach($creditBankHierarchy as $acct)
                    <tr>
                        <td class="code-col">{{ $acct['code'] }}</td>
                        <td class="name-col level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td class="debit-col"></td>
                        <td class="credit-col">@if($acct['show_amount'])${{ number_format($netPayment, 2, ',', '.') }}@endif</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="code-col">1110</td>
                        <td class="name-col">BANCOS Y CORPORACIONES</td>
                        <td class="debit-col"></td>
                        <td class="credit-col"></td>
                    </tr>
                    <tr>
                        <td class="code-col">111005</td>
                        <td class="name-col level-2">CUENTA CORRIENTE BANCARIA</td>
                        <td class="debit-col"></td>
                        <td class="credit-col">${{ number_format($netPayment, 2, ',', '.') }}</td>
                    </tr>
                @endif

                {{-- Retenciones (créditos) --}}
                @foreach($retentionRows as $row)
                <tr>
                    <td class="code-col">{{ $row['code'] }}</td>
                    <td class="name-col {{ $row['is_parent'] ? 'bold' : 'level-2' }}">{{ $row['name'] }}</td>
                    <td class="debit-col"></td>
                    <td class="credit-col">@if(!$row['is_parent'] && $row['amount'] > 0)${{ number_format($row['amount'], 2, ',', '.') }}@else 0.00 @endif</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; padding-right: 10px;">SUMAS IGUALES</td>
                    <td class="debit-col">${{ number_format($amount, 2, ',', '.') }}</td>
                    <td class="credit-col">${{ number_format($amount, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- IMPUTACIÓN PRESUPUESTAL --}}
        <div class="section-title">Imputación Presupuestal</div>
        @foreach($rpData as $rp)
        <table class="pres-table">
            <tr>
                <td class="pres-label">Registro No.:</td>
                <td>{{ $rp['rp_number'] }}</td>
                <td class="pres-label">Valor:</td>
                <td class="bold">${{ number_format($po->total, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="pres-label">Código:</td>
                <td>{{ $rp['expense_code'] }}</td>
                <td class="pres-label">Rubro:</td>
                <td>{{ $rp['expense_name'] }}</td>
            </tr>
        </table>
        @endforeach
        <table class="pres-table">
            <tr>
                <td class="pres-label">Fte. Financiación:</td>
                <td>{{ $fundingSourceName ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="pres-label">Auxiliar Administrativo</td>
                <td></td>
                <td class="pres-label">Banco:</td>
                <td>{{ $bankName ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td></td><td></td>
                <td class="pres-label">Cuenta No.:</td>
                <td>{{ $accountNumber ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td></td><td></td>
                <td class="pres-label">Pago con:</td>
                <td>TRANSFERENCIA</td>
            </tr>
        </table>

        {{-- FIRMAS --}}
        <div style="padding: 8px 10px;">
            <table class="firma-table">
                <tr>
                    <td style="width: 33%;">
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                            <div class="sig-role">Auxiliar Administrativo</div>
                        </div>
                    </td>
                    <td style="width: 34%;">
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            <div class="sig-role">Firma y Sello del Beneficiario</div>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="firma-table" style="margin-top: 15px;">
                <tr>
                    <td style="width: 33%;">
                        <div style="font-size: 7px; font-weight: bold; color: #1e3a5f;">Ordenador del Pago</div>
                        <div class="sig-line" style="margin-top: 20px;">
                            <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                        </div>
                    </td>
                    <td style="width: 34%;">
                        <div class="sig-line" style="margin-top: 20px;">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            @if($supplier)
                                <div style="font-size: 7px; color: #444;">{{ $supplier->document_number ?? '' }}</div>
                            @endif
                            <div style="font-size: 7px; color: #444;">DV: {{ $supplier->dv ?? '' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
