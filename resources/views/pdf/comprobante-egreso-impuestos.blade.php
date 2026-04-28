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
        .acct-table .code-col { width: 12%; }
        .acct-table .name-col { width: 48%; }
        .acct-table .debit-col { width: 20%; text-align: right; }
        .acct-table .credit-col { width: 20%; text-align: right; }
        .acct-table .parent { font-weight: bold; }
        .acct-table .child { padding-left: 16px; }
        .acct-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }
        .pres-table { width: 100%; border-collapse: collapse; }
        .pres-table td { padding: 3px 10px; font-size: 9px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .pres-label { font-weight: bold; color: #1e3a5f; width: 160px; font-size: 8px; }
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
        <td style="text-align:right;">
            <div class="doc-title">COMPROBANTE DE EGRESO No. {{ $po->formatted_number }}</div>
        </td>
    </tr>
</table>

{{-- INFO GENERAL --}}
<table class="info-table">
    <tr>
        <td class="info-label" style="width:130px;">Ciudad y Fecha:</td>
        <td>{{ $school->municipality ?? '' }} &nbsp; {{ $po->payment_date?->format('d/m/Y') ?? $po->created_at?->format('d/m/Y') ?? '' }}</td>
        <td class="bold" style="text-align:right;width:150px;">${{ number_format($amount, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="info-label">Pagado a:</td>
        <td colspan="2">{{ $supplier?->full_name ?? 'DIAN' }}</td>
    </tr>
    <tr>
        <td class="info-label">Por concepto de:</td>
        <td colspan="2">{{ $po->description ?? 'PAGO RETENCIONES' }}</td>
    </tr>
    <tr>
        <td class="info-label">La suma de (en letras):</td>
        <td colspan="2" class="bold" style="text-transform:uppercase;">{{ $amountInWords }}</td>
    </tr>
    <tr>
        <td></td>
        <td colspan="2" class="bold">${{ number_format($amount, 2, ',', '.') }}</td>
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
        {{-- CRÉDITO: Bancos --}}
        <tr>
            <td class="code-col parent">1110</td>
            <td class="name-col parent">BANCOS Y CORPORACIONES</td>
            <td class="debit-col"></td>
            <td class="credit-col"></td>
        </tr>
        @if($po->bankLines->count() <= 1)
        <tr>
            <td class="code-col">111005</td>
            <td class="name-col child">CUENTA CORRIENTE BANCARIA</td>
            <td class="debit-col"></td>
            <td class="credit-col">${{ number_format($amount, 2, ',', '.') }}</td>
        </tr>
        @else
            @foreach($po->bankLines as $bl)
            <tr>
                <td class="code-col">111005</td>
                <td class="name-col child">CUENTA CORRIENTE – {{ $bl->bankAccount?->bank?->name ?? '' }}</td>
                <td class="debit-col"></td>
                <td class="credit-col">${{ number_format((float)$bl->amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        @endif

        <tr><td colspan="4" style="padding:2px;">&nbsp;</td></tr>

        {{-- DÉBITO: Retención en la Fuente (2436xx) --}}
        @php
            $retefuenteRows = [
                'retefuente_honorarios' => ['243603', 'Retención en la Fuente - Honorarios 10%'],
                'retefuente_servicios'  => ['243605', 'Retención en la Fuente - Servicios 6% y 4%'],
                'retefuente_compras'    => ['243608', 'Retención en la Fuente - Compras 3.5% y 2.5%'],
                'reteiva'               => ['243625', 'Impuesto a las Ventas Retenido'],
            ];
        @endphp
        @foreach($retefuenteRows as $key => [$code, $label])
        <tr>
            <td class="code-col">{{ $code }}</td>
            <td class="name-col">{{ $label }}</td>
            <td class="debit-col">
                @if(isset($taxAmounts[$key]) && $taxAmounts[$key] > 0)
                    ${{ number_format($taxAmounts[$key], 2, ',', '.') }}
                @else
                    0.00
                @endif
            </td>
            <td class="credit-col"></td>
        </tr>
        @endforeach

        {{-- DÉBITO: Impuestos municipales (2407) --}}
        <tr>
            <td class="code-col parent">2407</td>
            <td class="name-col parent">IMPUESTOS TASAS, CONTRIBUCIONES</td>
            <td class="debit-col"></td>
            <td class="credit-col"></td>
        </tr>
        @php
            $municipalRows = [
                'estampilla_procultura'     => ['24072202', 'Otros Imp. Municipales (Estampilla Procultura 2%)'],
                'estampilla_produlto_mayor' => ['24072204', 'Otros Imp. Municipales (Estampilla Produlto Mayor 2.5%)'],
                'retencion_ica'             => ['24072209', 'Otros Impuestos Municipales (Retención ICA)'],
            ];
        @endphp
        @foreach($municipalRows as $key => [$code, $label])
        <tr>
            <td class="code-col">{{ $code }}</td>
            <td class="name-col child">{{ $label }}</td>
            <td class="debit-col">
                @if(isset($taxAmounts[$key]) && $taxAmounts[$key] > 0)
                    ${{ number_format($taxAmounts[$key], 2, ',', '.') }}
                @else
                    0.00
                @endif
            </td>
            <td class="credit-col"></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align:right;padding-right:10px;">SUMAS IGUALES</td>
            <td class="debit-col">${{ number_format($amount, 2, ',', '.') }}</td>
            <td class="credit-col">${{ number_format($amount, 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

{{-- SECCIÓN SIN TÍTULO (una sola vez, valor total, banco del proveedor) --}}
@php $bl = $po->bankLines->first(); @endphp
<table class="pres-table">
    <tr>
        <td class="pres-label">Registro No.:</td>
        <td>NO APLICA</td>
        <td class="pres-label">Valor:</td>
        <td class="bold">${{ number_format($amount, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="pres-label">Código:</td>
        <td>PAGO IMPUESTO</td>
        <td class="pres-label">Concepto:</td>
        <td>{{ strtoupper($po->description ?? 'PAGO RETENCIONES ESTAMPILLAS - IMPTOS') }}</td>
    </tr>
    <tr>
        <td class="pres-label">Auxiliar Administrativo</td>
        <td></td>
        <td class="pres-label">Banco:</td>
        <td>{{ $bl?->bankAccount?->bank?->name ?? 'N/D' }}</td>
    </tr>
    <tr>
        <td></td><td></td>
        <td class="pres-label">Cuenta No.:</td>
        <td>{{ $bl?->bankAccount?->account_number ?? 'N/D' }}</td>
    </tr>
    <tr>
        <td></td><td></td>
        <td class="pres-label">Cheque No.:</td>
        <td>TRANSFERENCIA</td>
    </tr>
</table>

{{-- FIRMAS --}}
<div style="padding:8px 10px;">
    <table class="firma-table">
        <tr>
            <td style="width:33%;">
                <div class="sig-line">
                    <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                    <div class="sig-role">Auxiliar Administrativo</div>
                </div>
            </td>
            <td style="width:34%;">
                <div class="sig-line">
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-role">Firma y Sello del Beneficiario</div>
                </div>
            </td>
        </tr>
    </table>
    <table class="firma-table" style="margin-top:15px;">
        <tr>
            <td style="width:33%;">
                <div style="font-size:7px;font-weight:bold;color:#1e3a5f;">Ordenador del Pago</div>
                <div class="sig-line" style="margin-top:20px;">
                    <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                </div>
            </td>
            <td style="width:34%;">
                <div class="sig-line" style="margin-top:20px;">
                    <div class="sig-name">{{ $supplier?->full_name ?? 'DIAN' }}</div>
                    @if($supplier?->document_number)
                        <div style="font-size:7px;color:#444;">{{ $supplier->document_number }}</div>
                    @endif
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