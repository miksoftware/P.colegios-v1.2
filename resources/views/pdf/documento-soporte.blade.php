<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documento Soporte - {{ $po->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .doc-main-title { text-align: center; font-size: 14px; font-weight: bold; color: #1e3a5f; padding: 12px 15px; line-height: 1.3; }
        .doc-legal { text-align: center; font-size: 8px; color: #666; padding: 0 15px 8px; }

        .school-header { text-align: center; padding: 8px 15px; color: #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .school-detail { font-size: 9px; }

        .date-row { display: table; width: 100%; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
        .date-cell { display: table-cell; padding: 6px 15px; font-size: 10px; vertical-align: middle; }
        .date-label { font-weight: bold; width: 80px; }
        .date-number { text-align: right; }
        .number-value { font-size: 14px; font-weight: bold; color: #c00; }

        .resolution { text-align: right; padding: 4px 15px; font-size: 8px; color: #c00; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 10px; font-size: 10px; vertical-align: top; border: 1px solid #ccc; }
        .info-label { font-weight: bold; background: #f0f0f0; color: #333; font-size: 9px; }

        .detail-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .detail-table th { background: #e0e0e0; font-size: 9px; text-transform: uppercase; padding: 5px 8px; border: 1px solid #aaa; text-align: center; font-weight: bold; }
        .detail-table td { padding: 6px 8px; border: 1px solid #ccc; font-size: 10px; vertical-align: top; }
        .detail-table .right { text-align: right; }
        .detail-table .center { text-align: center; }

        .bold { font-weight: bold; }
        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- TÍTULO PRINCIPAL --}}
        <div class="doc-main-title">
            Documento soporte de costos y gastos en operaciones con no obligados a expedir factura o documento equivalente
        </div>
        <div class="doc-legal">
            Artículo 1.6.1.4.12 Decreto Único reglamentario en materia tributaria 1625 de 2016 - Sustituido por el Decreto 358 de 2020
        </div>

        {{-- DATOS DEL COLEGIO --}}
        <div class="school-header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-detail">{{ $school->municipality ?? '' }}</div>
            <div class="school-detail">NIT. {{ $school->nit ?? '' }}</div>
            <div class="school-detail">{{ $school->address ?? '' }}</div>
        </div>

        {{-- FECHA Y NÚMERO --}}
        <div class="date-row">
            <div class="date-cell">
                <span class="date-label">Fecha</span>
                {{ $po->payment_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
            </div>
            <div class="date-cell date-number">
                <span class="bold">No.</span>
                <span class="number-value">{{ $po->invoice_number ?? $po->formatted_number }}</span>
            </div>
        </div>

        {{-- RESOLUCIÓN --}}
        @if($school->dian_resolution_1)
        <div class="resolution">
            Resolución No.{{ $school->dian_resolution_1 }} Desde {{ $school->dian_range_1 ?? '' }}
        </div>
        @endif

        {{-- DATOS DEL PROVEEDOR --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 130px;">Vendedor o quien presta el servicio:</td>
                <td>{{ $supplier->full_name ?? 'N/A' }}</td>
                <td style="width: 130px;">{{ $supplier->full_document ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Dirección:</td>
                <td>{{ $supplier->address ?? '' }}</td>
                <td>{{ $supplier->phone ?? '' }}</td>
            </tr>
        </table>

        {{-- TABLA DE DETALLE --}}
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Cantidad</th>
                    <th style="width: 43%;">Detalle</th>
                    <th style="width: 22%;">Vr Unitario</th>
                    <th style="width: 23%;">Vr Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">1</td>
                    <td>{{ $po->contract?->object ?? $po->description ?? '' }}</td>
                    <td class="right">${{ number_format($subtotal, 2, ',', '.') }}</td>
                    <td class="right">${{ number_format($subtotal, 2, ',', '.') }}</td>
                </tr>
                {{-- Filas vacías para el formato --}}
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            </tbody>
        </table>

        {{-- VALOR EN LETRAS --}}
        <table class="detail-table" style="margin-top: 0;">
            <tr>
                <td class="info-label" style="width: 12%;">Valor en letras:</td>
                <td colspan="3" class="bold" style="text-transform: uppercase;">{{ $amountInWords }}</td>
            </tr>
        </table>

        {{-- SUBTOTAL --}}
        <table class="detail-table" style="margin-top: 0;">
            <tr>
                <td colspan="2"></td>
                <td class="bold" style="width: 22%;">SUBTOTAL</td>
                <td class="right bold" style="width: 23%;">${{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
        </table>

        {{-- FIRMA PROVEEDOR Y TOTAL --}}
        <table class="detail-table" style="margin-top: 0;">
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 15px;">
                    <div class="bold">{{ $supplier->full_name ?? '' }}</div>
                    <div style="font-size: 9px; color: #666;">ACEPTO QUE NO SOY RESPONSABLE DE IVA</div>
                </td>
                <td class="bold">TOTAL</td>
                <td class="right bold">${{ number_format($amount, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
