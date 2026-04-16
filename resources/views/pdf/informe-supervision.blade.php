<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Supervisión - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 8px; color: #555; }
        .school-muni { font-size: 9px; color: #444; }
        .doc-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 8px; letter-spacing: 0.5px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 170px; text-transform: uppercase; font-size: 9px; }

        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        .section-title { text-align: center; font-size: 12px; font-weight: bold; color: #1e3a5f; padding: 10px 15px; text-transform: uppercase; letter-spacing: 1px; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 15px; width: 50%; }
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
            @if($school->dane_code)
                <div class="school-dane">{{ $school->dane_code }}</div>
            @endif
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="doc-title">Informe de Supervisión de Contratos y de Órdenes de Compras y de Servicios</div>
        </div>

        {{-- CONTRATO Y FECHA --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Contrato No.:</td>
                <td>{{ $contract->formatted_number }}</td>
                <td class="info-label" style="width: 80px;">Fecha:</td>
                <td>{{ $contract->end_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? $contract->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</td>
            </tr>
        </table>

        {{-- ACTA DE SUPERVISIÓN --}}
        <div style="padding: 6px 15px; border-bottom: 1px solid #ddd;">
            <span class="bold">ACTA DE SUPERVISIÓN No.</span>
        </div>

        {{-- TÍTULO INFORME --}}
        <div class="section-title">Informe de Actividades</div>

        {{-- TEXTO NARRATIVO --}}
        <div class="text-block" style="border-bottom: 1px solid #ddd;">
            A la fecha de {{ $contract->end_date?->format('d/m/Y') ?? $contract->start_date?->format('d/m/Y') ?? '' }},
            se reunieron las siguientes personas:
            <span class="bold">{{ $contract->supervisor?->name ?? $school->rector_name ?? '' }}</span>
            en su condición de supervisor del contrato, y
            <span class="bold">{{ $supplier->full_name ?? '' }}</span>
            en su condición de contratista con el fin de revisar las actividades desarrolladas en el periodo comprendido
            {{ $contract->start_date?->format('d/m/Y') ?? '' }} hasta el {{ $contract->end_date?->format('d/m/Y') ?? '' }},
            en cumplimiento de lo establecido en el
            <span class="bold">CONTRATO No. {{ $contract->formatted_number }}</span>,
            cuyas condiciones generales se enuncian a continuación:
        </div>

        {{-- DATOS DEL CONTRATO --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Objeto:</td>
                <td>{{ $contract->object }}</td>
            </tr>
            <tr>
                <td class="info-label">Forma de Pago:</td>
                <td>{{ $contract->payment_method_name ?? 'UN PAGO' }}</td>
            </tr>
            <tr>
                <td class="info-label">Valor Contratado:</td>
                <td class="bold">${{ number_format($contract->total, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Plazo de Ejecución:</td>
                <td>{{ $contract->duration_days ?? 'N/A' }} DÍAS</td>
            </tr>
            <tr>
                <td class="info-label">Fecha de Iniciación:</td>
                <td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Fecha Prevista de Terminación:</td>
                <td>{{ $contract->end_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Supervisor:</td>
                <td>{{ $contract->supervisor?->name ?? $school->rector_name ?? '' }}</td>
            </tr>
        </table>

        {{-- TEXTO CERTIFICACIÓN --}}
        <div class="text-block" style="border-top: 1px solid #ddd; padding-top: 10px;">
            El supervisor certifica que las actividades asignadas al contratista, fueron ejecutadas a satisfacción y de conformidad con lo estipulado en el contrato, por esta razón se procede a autorizar el pago, dando cumplimiento a la forma de pago pactada. Para constancia se firma por quienes en ella intervinieron.
        </div>

        {{-- FIRMAS --}}
        <div style="padding: 12px 15px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            <div class="sig-detail">{{ $supplier->document_number ?? '' }}</div>
                            <div class="sig-role">Contratista</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $contract->supervisor?->name ?? $school->rector_name ?? '' }}</div>
                            @if($school->rector_document)
                                <div class="sig-detail">{{ $school->rector_document }}</div>
                            @endif
                            <div class="sig-role">Supervisor</div>
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
