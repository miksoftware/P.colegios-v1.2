<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Contabilidad - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
        .container { padding: 15px 25px; }
        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 8px 12px; }
        .header-left { font-size: 10px; }
        .header-right { text-align: right; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-sub { font-size: 7px; color: #555; }
        .doc-title { font-size: 10px; font-weight: bold; color: #1e3a5f; }

        /* Info rows */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 12px; font-size: 9px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .info-label { font-weight: bold; color: #1e3a5f; text-transform: uppercase; }

        /* Tabla contable */
        .acct-table { width: 100%; border-collapse: collapse; }
        .acct-table th { background: #1e3a5f; color: #fff; font-size: 8px; text-transform: uppercase; padding: 5px 8px; border: 1px solid #1e3a5f; text-align: center; font-weight: bold; }
        .acct-table td { padding: 4px 8px; border: 1px solid #ccc; font-size: 9px; }
        .acct-table .code-col { width: 12%; }
        .acct-table .name-col { width: 48%; }
        .acct-table .debit-col { width: 20%; text-align: right; }
        .acct-table .credit-col { width: 20%; text-align: right; }
        .acct-table .level-1 { font-weight: bold; }
        .acct-table .level-2 { padding-left: 15px; font-weight: bold; }
        .acct-table .level-3 { padding-left: 25px; font-size: 8.5px; }
        .acct-table .level-4 { padding-left: 35px; font-size: 8.5px; }
        .acct-table .level-5 { padding-left: 45px; font-size: 8.5px; color: #444; }
        .acct-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        /* Sección presupuestal */
        .section-title { background: #1e3a5f; color: #fff; padding: 4px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .pres-table { width: 100%; border-collapse: collapse; }
        .pres-table td { padding: 4px 12px; font-size: 9px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .pres-label { font-weight: bold; color: #1e3a5f; width: 120px; }

        /* Firmas */
        .firma-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 10px; width: 33%; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 3px; }
        .sig-name { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .sig-role { font-size: 7px; color: #666; }

        .footer { margin-top: 8px; text-align: center; font-size: 6px; color: #999; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="school-sub">{{ $school->municipality ?? '' }}</div>
                    @if($school->dane_code)
                        <div class="school-sub">{{ $school->dane_code }}</div>
                    @endif
                </td>
                <td class="header-right">
                    <div class="doc-title">COMPROBANTE DE CONTABILIDAD No. {{ $contract->formatted_number }}</div>
                </td>
            </tr>
        </table>

        {{-- CIUDAD Y FECHA --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 140px;">Ciudad y Fecha:</td>
                <td>{{ $school->municipality ?? '' }} &nbsp;&nbsp; {{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
                <td class="bold" style="text-align: right; width: 180px;">${{ number_format($amount, 2, ',', '.') }}</td>
            </tr>
        </table>

        {{-- PAGADO A --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 140px;">Pagado a:</td>
                <td>{{ $supplier->full_name ?? 'N/A' }}</td>
            </tr>
        </table>

        {{-- POR CONCEPTO DE --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 140px;">Por concepto de:</td>
                <td>{{ $contract->object }}</td>
            </tr>
        </table>

        {{-- LA SUMA DE (EN LETRAS) --}}
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 140px;">La suma de (en letras):</td>
                <td class="bold" style="text-transform: uppercase;">{{ $amountInWords }}</td>
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
                {{-- Débito: cuentas de gasto del rubro --}}
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

                {{-- Espacio separador --}}
                <tr><td colspan="4" style="padding: 2px;">&nbsp;</td></tr>

                {{-- Crédito: cuenta de pasivos --}}
                @if(count($creditHierarchy) > 0)
                    @foreach($creditHierarchy as $acct)
                    <tr>
                        <td class="code-col">{{ $acct['code'] }}</td>
                        <td class="name-col level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td class="debit-col"></td>
                        <td class="credit-col">@if($acct['show_amount'])${{ number_format($amount, 2, ',', '.') }}@endif</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="code-col">2401</td>
                        <td class="name-col">Adq. de bienes y servicios nacionales</td>
                        <td class="debit-col"></td>
                        <td class="credit-col"></td>
                    </tr>
                    <tr>
                        <td class="code-col">240101</td>
                        <td class="name-col level-2">Bienes y servicios</td>
                        <td class="debit-col"></td>
                        <td class="credit-col">${{ number_format($amount, 2, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; padding-right: 15px;">SUMAS IGUALES</td>
                    <td class="debit-col">${{ number_format($amount, 2, ',', '.') }}</td>
                    <td class="credit-col">${{ number_format($amount, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- IMPUTACIÓN PRESUPUESTAL --}}
        <div class="section-title">Imputación Presupuestal</div>
        @foreach($rpRows as $row)
        <table class="pres-table">
            <tr>
                <td class="pres-label">Registro No.:</td>
                <td>{{ $row['rp_number'] }}</td>
                <td class="pres-label">Valor:</td>
                <td class="bold">${{ number_format($row['total_amount'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="pres-label">Código:</td>
                <td>{{ $row['expense_code'] }}</td>
                <td class="pres-label">Rubro:</td>
                <td>{{ $row['expense_name'] }}</td>
            </tr>
            <tr>
                <td class="pres-label">Fuente de Financiación:</td>
                <td colspan="3">
                    {{ collect($row['sources'])->pluck('name')->implode(' Y ') }}
                </td>
            </tr>
        </table>
        @endforeach

        {{-- FIRMAS --}}
        <div style="padding: 12px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div style="font-size: 8px; font-weight: bold; color: #1e3a5f; margin-bottom: 25px;">Auxiliar Administrativo</div>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 8px; font-weight: bold; color: #1e3a5f; margin-bottom: 25px;">Firma y Sello del Beneficiario</div>
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="firma-table" style="margin-top: 20px;">
                <tr>
                    <td>
                        <div style="font-size: 8px; font-weight: bold; color: #1e3a5f; margin-bottom: 25px;">Ordenador del Gasto</div>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
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
