<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Inicio - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 8px; color: #555; }
        .school-muni { font-size: 9px; color: #444; }
        .doc-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 10px; letter-spacing: 2px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 180px; text-transform: uppercase; font-size: 9px; }

        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 10px; }
        .sig-line { border-top: 1px solid #333; width: 220px; margin: 0 auto; padding-top: 3px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; text-transform: uppercase; }
        .sig-detail { font-size: 8px; color: #444; }

        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            @if($school->nit)
                <div class="school-dane">{{ $school->nit }}</div>
            @endif
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="doc-title">Acta de Inicio del Contrato No. {{ $contract->formatted_number }}</div>
        </div>

        {{-- DATOS DEL CONTRATO --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Fecha de Suscripción:</td>
                <td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Contratista:</td>
                <td>{{ $supplier->full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Objeto:</td>
                <td>{{ $contract->object }}</td>
            </tr>
            <tr>
                <td class="info-label">Valor de la Orden:</td>
                <td class="bold">${{ number_format($contract->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Fecha de Inicio:</td>
                <td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Plazo:</td>
                <td>{{ $contract->duration_days ?? 'N/A' }} DÍAS</td>
            </tr>
            <tr>
                <td class="info-label">Fecha de Terminación:</td>
                <td>{{ $contract->end_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Supervisor:</td>
                <td>{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</td>
            </tr>
            <tr>
                <td class="info-label">Entidad Aseguradora:</td>
                <td>NO GENERA</td>
            </tr>
            <tr>
                <td class="info-label">Riesgos Amparados:</td>
                <td>NO GENERA</td>
            </tr>
        </table>

        {{-- CONCEPTO --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Concepto:</td>
                <td>
                    Se reunieron, a los {{ $contract->start_date?->format('d/m/Y') ?? '' }}, Los señores
                    <span class="bold">{{ $school->ordenador_gasto_display_name }}</span> Como contratante Y
                    <span class="bold">{{ $supplier->full_name ?? '' }}</span> contratista, con el objeto de dar inicio a la ejecución a la presente orden,
                    @if($rpNumbers)
                        que cuenta con registro presupuestal # {{ $rpNumbers }}.
                    @endif
                </td>
            </tr>
        </table>

        {{-- FECHA ACTA DE INICIO --}}
        <div class="text-block" style="border-top: 1px solid #ddd;">
            <span class="bold">FECHA ACTA DE INICIO:</span>
            {{ $contract->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}
        </div>

        {{-- TEXTO FIRMAS --}}
        <div class="text-block" style="border-top: 1px solid #ddd;">
            FIRMAN A CONTINUACIÓN QUIENES EN ELLA INTERVINIERON.
        </div>

        {{-- FIRMAS --}}
        <div style="padding: 12px 15px;">
            {{-- Supervisor --}}
            <div style="text-align: center; margin-top: 25px;">
                <div class="sig-line" style="margin: 0 auto;">
                    <div class="sig-name">{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</div>
                    <div class="sig-role">SUPERVISOR:</div>
                    <div class="sig-detail">{{ $contract->supervisor?->identification_number ?? $school->rector_display_document ?? '' }}</div>
                </div>
            </div>

            {{-- Contratante y Contratista --}}
            <table class="firma-table" style="margin-top: 30px;">
                <tr>
                    <td style="width: 50%;">
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_display_name }}</div>
                            <div class="sig-role">RECTOR</div>
                            <div class="sig-role">CONTRATANTE</div>
                            @if($school->rector_display_document)
                                <div class="sig-detail">{{ $school->rector_display_document }}</div>
                            @endif
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            <div class="sig-detail">{{ $supplier->document_number ?? '' }}</div>
                            <div class="sig-role">CONTRATISTA</div>
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
