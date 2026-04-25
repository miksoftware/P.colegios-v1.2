<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aceptación de Propuesta - {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #222; line-height: 1.5; }
        .container { padding: 15px 25px; }
        .doc-border { border: 2px solid #1e3a5f; padding: 0; }

        .text-block { padding: 6px 15px; font-size: 9px; text-align: justify; }
        .info-line { padding: 3px 15px; font-size: 9px; }
        .bold { font-weight: bold; }

        /* Header info */
        .header-info { padding: 12px 15px; border-bottom: 1px solid #ddd; }

        /* Tabla datos contrato */
        .contract-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .contract-table td { padding: 4px 10px; border: 1px solid #ccc; font-size: 8.5px; vertical-align: top; }
        .contract-table .label { font-weight: bold; background: #e8edf3; color: #1e3a5f; width: 25%; text-transform: uppercase; }

        .section-title { background: #1e3a5f; color: #fff; padding: 4px 15px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        .sig-line { border-top: 1px solid #333; width: 240px; padding-top: 3px; margin-top: 40px; }
        .sig-name { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .sig-role { font-size: 7px; color: #666; }
        .footer { margin-top: 8px; text-align: center; font-size: 6px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- ENCABEZADO --}}
        <div class="header-info">
            <div style="font-size: 9px;">
                <span class="bold">{{ $school->municipality ?? '' }}</span>
                &nbsp;&nbsp;
                {{ $convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
            </div>
        </div>

        {{-- DESTINATARIO --}}
        <div style="padding: 10px 15px; border-bottom: 1px solid #ddd;">
            <div class="bold">SEÑORES:</div>
            <div class="bold" style="font-size: 10px;">{{ $supplier->full_name ?? 'N/A' }}</div>
            @if($supplier->address)
                <div>{{ $supplier->address }}</div>
            @endif
        </div>

        {{-- REFERENCIA --}}
        <div style="padding: 8px 15px; border-bottom: 1px solid #ddd;">
            <span class="bold">REFERENCIA; COMUNICACIÓN DE ACEPTACIÓN DE PROPUESTA</span>
        </div>

        {{-- TEXTO LEGAL --}}
        <div class="text-block">
            De conformidad con lo dispuesto por el Artículo 94 de la Ley 1474 de 2.011 y por lo reglamentado en el Decreto 1510 de 2013 Y de acuerdo al manual de contratación de la institución educativa, aprobado por el consejo directivo para la vigencia {{ $convocatoria->fiscal_year }}, me permito manifestarle que la cotización que Usted ha presentado ha sido aceptada.
        </div>
        <div class="text-block">
            Para todos los efectos a que haya lugar, se entiende que esta carta de aceptación implica que con Usted ha quedado celebrado el contrato de mínima cuantía que a partir de la fecha queda codificado de la siguiente manera:
        </div>
        <div class="text-block">
            Usted deberá cumplir con la ejecución del contrato de conformidad con las condiciones de los estudios previos y con los ofrecimientos formulados en su propuesta.
        </div>
        <div class="text-block">
            Los términos generales de la descripción contractual son los siguientes:
        </div>

        {{-- TABLA DE DATOS DEL CONTRATO --}}
        <div style="padding: 6px 15px;">
            <table class="contract-table">
                <tr>
                    <td class="label">Contratista:</td>
                    <td colspan="3">{{ $supplier->full_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">NIT / CC:</td>
                    <td>{{ $supplier->document_number ?? '' }}</td>
                    <td class="label">DV:</td>
                    <td>{{ $supplier->dv ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Representante Legal:</td>
                    <td colspan="3">{{ $supplier->full_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Dirección:</td>
                    <td colspan="3">{{ $supplier->address ?? '' }}{{ $supplier->municipality ? ', ' . $supplier->municipality->name : '' }}</td>
                </tr>
                <tr>
                    <td class="label">Teléfono:</td>
                    <td colspan="3">{{ $supplier->phone ?? $supplier->mobile ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Cotización No.:</td>
                    <td colspan="3">{{ $selectedProposal->proposal_number ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Objeto:</td>
                    <td colspan="3">{{ $convocatoria->object }}</td>
                </tr>
                <tr>
                    <td class="label">C.D.P No.:</td>
                    <td colspan="3">{{ $cdpNumbers ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Valor:</td>
                    <td colspan="3">
                        <span class="bold">${{ number_format($amount, 2, ',', '.') }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Plazo de Ejecución:</td>
                    <td colspan="3">{{ $durationDays ?? 'N/A' }} DÍAS</td>
                </tr>
                <tr>
                    <td class="label">Supervisor:</td>
                    <td colspan="3">{{ $contract?->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->ordenador_gasto_display_name }}</td>
                </tr>
                <tr>
                    <td class="label">Forma de Pago:</td>
                    <td colspan="3">{{ $contract?->payment_method_name ?? 'A CONVENIR' }}</td>
                </tr>
            </table>
        </div>

        {{-- GARANTÍAS --}}
        <div class="text-block" style="border-top: 1px solid #ddd; padding-top: 8px;">
            <span class="bold">GARANTÍAS:</span> Para la ejecución del contrato, de conformidad con lo dispuesto por el art.41 de la Ley 80 de 1993, modificado por el art.23 de la Ley 1150 de 2007 y Decreto 1510 de 2013 Y de acuerdo con el manual de contratación aprobado por el Consejo Directivo de esta institución educativa para la vigencia {{ $convocatoria->fiscal_year }}, el Contratista constituirá a favor de la Institución Educativa las siguientes garantías:
            <br><br>
            <span class="bold">NO GENERA RIESGOS</span>
        </div>

        {{-- CORDIALMENTE --}}
        <div class="text-block" style="border-top: 1px solid #ddd; padding-top: 8px;">
            Cordialmente,
        </div>

        {{-- FIRMA --}}
        <div style="padding: 12px 15px;">
            <div class="sig-line">
                <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                <div class="sig-role">ORDENADOR DEL GASTO</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
