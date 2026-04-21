<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Disponibilidad de Tesorería - {{ $rpNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-muni { font-size: 9px; color: #444; }

        .title-row { display: table; width: 100%; border-bottom: 2px solid #1e3a5f; }
        .title-cell { display: table-cell; padding: 8px 15px; vertical-align: middle; }
        .title-text { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .title-number { font-size: 14px; font-weight: bold; color: #1e3a5f; text-align: right; }

        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 160px; }

        .account-box { border: 1px solid #aaa; padding: 4px 10px; font-size: 11px; font-weight: bold; display: inline-block; margin-left: 10px; letter-spacing: 1px; }

        .lugar-fecha { padding: 10px 15px; border-top: 1px solid #ccc; font-size: 10px; }

        .firma-section { padding: 15px; margin-top: 40px; }
        .sig-line { border-top: 1px solid #333; width: 240px; margin: 0 auto; padding-top: 4px; text-align: center; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; font-style: italic; }

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
        </div>

        {{-- TÍTULO --}}
        <div class="title-row">
            <div class="title-cell">
                <span class="title-text">Certificado de Disponibilidad de Tesorería</span>
            </div>
            <div class="title-cell" style="text-align: right;">
                <span class="title-number">No. {{ $rpNumber }}</span>
            </div>
        </div>

        {{-- TEXTO CERTIFICACIÓN --}}
        <div class="text-block" style="border-bottom: 1px solid #ddd;">
            EL SUSCRITO PAGADOR DE LA INSTITUCIÓN EDUCATIVA CERTIFICA:
        </div>

        <div class="text-block" style="border-bottom: 1px solid #ddd;">
            Que existe disponibilidad de tesorería sin comprometer, depositados en la cuenta corriente No.
            <span class="account-box">{{ $accountNumber ?: 'N/A' }}</span>
        </div>

        {{-- DATOS --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Del banco:</td>
                <td>{{ $bankName ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Fuente de Financiación:</td>
                <td>
                    @foreach($sourcesInfo as $source)
                        {{ $source['name'] }} (${{ number_format($source['amount'], 2, ',', '.') }})
                        @if(!$loop->last)<br>@endif
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="info-label">Solicitante:</td>
                <td>{{ $school->ordenador_gasto_display_name }}</td>
            </tr>
            <tr>
                <td class="info-label">Código:</td>
                <td>{{ $budgetItemCode }}</td>
            </tr>
            <tr>
                <td class="info-label">Rubro:</td>
                <td>{{ $budgetItemName }}</td>
            </tr>
            <tr>
                <td class="info-label">Concepto:</td>
                <td>{{ $contract->object }}</td>
            </tr>
            <tr>
                <td class="info-label">Valor a Comprometer:</td>
                <td>
                    <span class="bold">${{ number_format($amount, 2, ',', '.') }}</span>
                    <br>
                    <span style="text-transform: uppercase;">{{ $amountInWords }}</span>
                </td>
            </tr>
        </table>

        {{-- LUGAR Y FECHA --}}
        <div class="lugar-fecha">
            <span class="bold">Lugar y Fecha de Expedición:</span>
            {{ $school->municipality ?? 'N/A' }} &nbsp;&nbsp;&nbsp; {{ $contract->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
        </div>

        {{-- FIRMA --}}
        <div class="firma-section">
            <div class="sig-line">
                <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                <div class="sig-role">Auxiliar Administrativo</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
