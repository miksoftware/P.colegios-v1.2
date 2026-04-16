<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Actividades - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .doc-title { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; letter-spacing: 1px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 170px; text-transform: uppercase; font-size: 9px; }

        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        .objeto-box { margin: 6px 15px; padding: 10px; border: 1px solid #ccc; font-size: 10px; text-align: justify; }

        .obs-section { padding: 8px 15px; border-top: 1px solid #ddd; }
        .obs-label { font-weight: bold; color: #1e3a5f; font-size: 10px; }

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

        {{-- TÍTULO --}}
        <div class="doc-title">Informe de Actividades</div>

        {{-- DATOS DEL CONTRATO --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Contrato:</td>
                <td>CONTRATO No. {{ $contract->formatted_number }}</td>
                <td class="info-label" style="width: 80px;">Fecha:</td>
                <td>{{ $contract->end_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Contratista:</td>
                <td colspan="3">{{ $supplier->full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Plazo de Ejecución:</td>
                <td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
                <td class="bold" style="width: 20px;">A</td>
                <td>{{ $contract->end_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Avance de Ejecución:</td>
                <td colspan="3">100%</td>
            </tr>
        </table>

        {{-- TEXTO INTRODUCTORIO --}}
        <div class="text-block" style="border-top: 1px solid #ddd;">
            En cumplimiento de lo estipulado en el objeto de la
            <span class="bold">CONTRATO No. {{ $contract->formatted_number }}</span>,
            me permito informar los resultados obtenidos en virtud de la contratación por la cual el contratista se obliga a prestar a la institución educativa los servicios y/o suministrar los bienes, de la siguiente manera:
        </div>

        {{-- OBJETO --}}
        <div class="objeto-box">
            {{ $contract->object }}
        </div>

        {{-- ACTIVIDADES Y COSTO --}}
        <div class="text-block">
            De acuerdo con las actividades y objetivos cumplidos dentro del objeto de la
            <span class="bold">CONTRATO No. {{ $contract->formatted_number }}</span>
        </div>
        <div class="text-block" style="padding-top: 2px;">
            El costo de la orden, por el periodo contratado, corresponden a la suma de
            <span class="bold">${{ number_format($contract->total, 2, ',', '.') }}</span>
        </div>

        {{-- OBSERVACIONES --}}
        <div class="obs-section">
            <div class="obs-label">Observaciones:</div>
            <div style="margin-top: 4px; font-size: 10px;">
                Se recibió a satisfacción los bienes y/o servicios contratados durante: de
                <span class="bold">{{ $contract->start_date?->format('d/m/Y') ?? '' }}</span>
                hasta
                <span class="bold">{{ $contract->end_date?->format('d/m/Y') ?? '' }}</span>
            </div>
        </div>

        {{-- TEXTO CERTIFICACIÓN --}}
        <div class="text-block" style="border-top: 1px solid #ddd; padding-top: 10px;">
            El supervisor certificó que las actividades asignadas al contratista, fueron ejecutadas a satisfacción y de conformidad con lo estipulado en el contrato, por esta razón procedió a autorizar el pago, dando cumplimiento a la forma de pago pactada. Para constancia se firma por quienes en ella intervinieron.
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
