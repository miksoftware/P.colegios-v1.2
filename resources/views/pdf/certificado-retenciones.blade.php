<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Retenciones - {{ $po->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-muni { font-size: 9px; color: #444; }
        .school-nit { font-size: 9px; color: #555; }
        .doc-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 10px; letter-spacing: 1px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 140px; text-transform: uppercase; font-size: 9px; }

        .section-title { text-align: center; font-size: 11px; font-weight: bold; color: #1e3a5f; padding: 10px 15px; text-transform: uppercase; letter-spacing: 0.5px; }

        .ret-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .ret-table th { background: #e8edf3; font-size: 8px; text-transform: uppercase; padding: 5px 6px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .ret-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9px; text-align: center; }

        .lugar-fecha { padding: 10px 15px; border-top: 1px solid #ccc; font-size: 10px; }
        .bold { font-weight: bold; }

        .nota { padding: 10px 15px; font-size: 9px; color: #666; margin-top: 30px; }
        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="school-nit">{{ $school->nit ?? '' }}</div>
            <div class="doc-title">Certificado de Retenciones</div>
        </div>

        {{-- DATOS DEL PROVEEDOR --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Practicada a:</td>
                <td class="bold">{{ $supplier->full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">NIT:</td>
                <td>{{ $supplier->document_number ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Año Gravable:</td>
                <td>{{ $po->fiscal_year ?? date('Y') }}</td>
            </tr>
        </table>

        {{-- RETENCIONES DE RENTA --}}
        <div class="section-title">Retenciones de Renta</div>
        <div style="padding: 4px 15px;">
            <table class="ret-table">
                <thead>
                    <tr>
                        <th rowspan="2">Fecha</th>
                        <th rowspan="2">Factura N.</th>
                        <th colspan="3">Concepto</th>
                        <th rowspan="2">Base Gravable</th>
                        <th rowspan="2">% Aplicado</th>
                        <th rowspan="2">Valor Retenido</th>
                    </tr>
                    <tr>
                        <th>Honorarios</th>
                        <th>Servicios</th>
                        <th>Compras</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $po->payment_date?->format('d-M-y') ?? '' }}</td>
                        <td>{{ $po->invoice_number ?? '' }}</td>
                        <td>{{ number_format($honorarios, 2) }}</td>
                        <td>{{ number_format($servicios, 2) }}</td>
                        <td>{{ number_format($compras, 2) }}</td>
                        <td>{{ number_format($po->subtotal, 2, ',', '.') }}</td>
                        <td>{{ number_format($po->retention_percentage, 2) }}</td>
                        <td>{{ number_format($po->retefuente, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- RETENCIÓN DE IVA --}}
        <div class="section-title">Retención de IVA</div>
        <div style="padding: 4px 15px;">
            <table class="ret-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Factura N.</th>
                        <th>Base Gravable</th>
                        <th>% Aplicado</th>
                        <th>Valor Retenido</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $po->payment_date?->format('d-M-y') ?? '' }}</td>
                        <td>{{ $po->invoice_number ?? '' }}</td>
                        <td>{{ number_format($po->iva > 0 ? $po->iva / 0.15 : 0, 2, ',', '.') }}</td>
                        <td>15</td>
                        <td>{{ number_format($po->reteiva, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- LUGAR Y FECHA --}}
        <div class="lugar-fecha">
            <span class="bold">LUGAR Y FECHA DE EXPEDICIÓN:</span>
            {{ $school->municipality ?? '' }} &nbsp;&nbsp;&nbsp; {{ $po->payment_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
        </div>

        {{-- NOTA --}}
        <div class="nota">
            NO REQUIERE FIRMA AUTÓGRAFA.
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
