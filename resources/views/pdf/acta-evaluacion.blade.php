<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Evaluación - {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #222; line-height: 1.4; }
        .container { padding: 12px 20px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 8px 12px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 7px; color: #555; }
        .school-muni { font-size: 8px; color: #444; }
        .school-date { font-size: 8px; color: #444; margin-top: 2px; }
        .doc-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 8px; letter-spacing: 0.5px; }
        .doc-subtitle { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 2px; }

        .text-block { padding: 6px 12px; font-size: 8.5px; text-align: justify; }
        .info-line { padding: 3px 12px; font-size: 8.5px; }
        .bold { font-weight: bold; }
        .separator { border-top: 1px solid #ddd; }

        .section-title { background: #1e3a5f; color: #fff; padding: 4px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Tablas */
        .data-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .data-table th { background: #e8edf3; font-size: 7px; text-transform: uppercase; padding: 4px 6px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .data-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 8px; }
        .data-table .center { text-align: center; }
        .data-table .right { text-align: right; }
        .data-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        .sig-line { border-top: 1px solid #333; width: 240px; padding-top: 3px; margin-top: 30px; }
        .sig-name { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .sig-role { font-size: 7px; color: #666; }
        .footer { margin-top: 8px; text-align: center; font-size: 6px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            <div style="font-size: 8px; color: #444;">{{ $school->nit ?? '' }}</div>
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="school-date">{{ ($convocatoria->evaluation_date ?? $convocatoria->start_date)?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</div>
            <div class="doc-title">Acta de Evaluación</div>
            <div class="doc-subtitle">De la Invitación a Cotizar y/o a Presentar Propuesta N. {{ $convocatoria->formatted_number }}</div>
        </div>

        {{-- FECHA Y OBJETO --}}
        <div class="info-line separator">
            <span class="bold">FECHA:</span> {{ ($convocatoria->evaluation_date ?? $convocatoria->start_date)?->format('d/m/Y') ?? '' }}
        </div>
        <div class="info-line separator" style="padding-bottom: 6px;">
            <span class="bold">OBJETO:</span> CONVOCATORIA ABIERTA PARA LA RECEPCIÓN DE COTIZACIONES CON EL FIN DE CONTRATAR
            <br>
            {{ $convocatoria->object }}
        </div>

        {{-- TEXTO INTRODUCTORIO --}}
        <div class="text-block separator">
            El señor rector de la Institución Educativa <span class="bold">{{ $school->rector_display_name }}</span>, procedió a revisar las propuestas recibidas dentro de esta convocatoria y se constató que se recibieron dentro del término establecido para participar en el proceso.
        </div>

        {{-- NÚMERO DE PROPUESTAS RECIBIDAS --}}
        <div class="info-line separator" style="padding-top: 6px;">
            <span class="bold">NÚMERO DE PROPUESTAS RECIBIDAS: {{ $proposals->count() }}</span>
        </div>

        {{-- TABLA DE PROPUESTAS RECIBIDAS --}}
        <div style="padding: 4px 12px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Fecha Recibido</th>
                        <th style="width: 15%;">Hora Recibido</th>
                        <th style="width: 40%;">Proponentes</th>
                        <th style="width: 25%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposals as $proposal)
                        <tr>
                            <td class="center">{{ $proposal->received_date?->format('d/m/Y') ?? $proposal->created_at?->format('d/m/Y') ?? '' }}</td>
                            <td class="center">{{ $proposal->received_time ? \Carbon\Carbon::parse($proposal->received_time)->format('h:i:s a') : ($proposal->created_at?->format('h:i:s A') ?? '') }}</td>
                            <td>{{ $proposal->supplier?->full_name ?? 'N/A' }}</td>
                            <td class="right">${{ number_format($proposal->total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TEXTO VERIFICACIÓN --}}
        <div class="text-block separator">
            Se procedió a verificar el cumplimiento de los criterios habilitantes con el fin de constatar el cumplimiento de los mismos y de conformidad con los términos establecidos en la Invitación pública, se presenta el Informe final de la evaluación de las propuestas, después de realizado el proceso de chequeo de documentación se constató y se determinó:
        </div>

        {{-- TABLA DE REQUISITOS POR PROPONENTE --}}
        <div style="padding: 4px 12px;">
            @php
                $requisitos = [
                    'Presentación de la Cotización',
                    'Fotocopia de la cédula de ciudadanía',
                    'Copia del registro único tributario (RUT)',
                    'Certificado de existencia (Cámara de Comercio)',
                    'Certificado de ausencia de antecedentes disciplinarios (Procuraduría)',
                    'Certificado de ausencia de antecedentes fiscales (Contraloría)',
                    'Certificado de ausencia de antecedentes judiciales',
                    'Certificado de medidas correctivas',
                    'Copia de la libreta militar para hombres menores de 50 años',
                    'Certificado REDAM',
                    'Certificado de Delitos Sexuales',
                    'Certificación Bancaria para la transferencia',
                    'PARA PERSONAS JURÍDICAS: Copia de pago de la planilla de seguridad social al día.',
                    'PARA PERSONAS NATURALES: Certificación de afiliación a SALUD, PENSIÓN Y ARL o planilla de pago',
                    'OBSERVACIÓN: La liquidación y pago por este concepto se hará sobre el 40% del valor total del contrato (Esto aplica para valores mayores a 1 SMLV)',
                ];
            @endphp
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50%;" rowspan="2">Requisitos para Presentación de Cotizaciones</th>
                        <th colspan="{{ $proposals->count() }}">Proponentes</th>
                    </tr>
                    <tr>
                        @foreach($proposals as $proposal)
                            <th>{{ $proposal->supplier?->full_name ?? 'N/A' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisitos as $req)
                        <tr>
                            <td>{{ $req }}</td>
                            @foreach($proposals as $proposal)
                                <td class="center">X</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TABLA DE EVALUACIÓN ECONÓMICA --}}
        <div class="text-block separator">
            Se procede a adjudicar valoración por el precio en la siguiente tabla:
        </div>
        <div style="padding: 4px 12px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Proponente</th>
                        <th style="width: 30%;">Valor Económico</th>
                        <th style="width: 20%;">Puntaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposals as $proposal)
                        <tr>
                            <td class="{{ $proposal->is_selected ? 'bold' : '' }}">{{ $proposal->supplier?->full_name ?? 'N/A' }}</td>
                            <td class="right">${{ number_format($proposal->total, 2, ',', '.') }}</td>
                            <td class="center bold">{{ number_format($proposal->score ?? 0, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ADJUDICACIÓN --}}
        @if($selectedProposal)
        <div class="text-block separator" style="padding-top: 8px;">
            Por lo anteriormente expuesto la propuesta ganadora es la presentada por:
            <span class="bold">{{ $selectedProposal->supplier?->full_name ?? 'N/A' }}</span>
            <br><br>
            Domicilio del Contratista: <span class="bold">{{ $selectedProposal->supplier?->address ?? '' }}</span>
            <br>
            Identificación del proveedor C.C. O NIT. <span class="bold">{{ $selectedProposal->supplier?->document_number ?? '' }}</span>
            @if($selectedProposal->supplier?->dv)
                DV <span class="bold">{{ $selectedProposal->supplier->dv }}</span>
            @endif
            <br><br>
            por valor de: <span class="bold">${{ number_format($selectedProposal->total, 2, ',', '.') }}</span>
        </div>

        {{-- CDP Y RUBROS --}}
        <div class="text-block">
            A quien se le adjudicará la orden y/o contrato con cargo al rubro de
            @if(count($expenseCodeRows) > 0)
                @foreach($expenseCodeRows as $ec)
                    <span class="bold">{{ $ec['name'] }}</span>{{ !$loop->last ? ', ' : '' }}
                @endforeach
            @endif
        </div>

        @if(count($cdpRows) > 0)
        <div class="text-block">
            Con cargo a los recursos de:
            @foreach($cdpRows as $row)
                <span class="bold">{{ $row['funding_source_name'] }}</span>{{ !$loop->last ? ', ' : '' }}
            @endforeach
        </div>
        @endif
        @endif

        {{-- CONSTANCIA --}}
        <div class="text-block separator" style="padding-top: 8px;">
            En constancia se firma en <span class="bold">{{ $school->municipality ?? '' }}</span>
            el día <span class="bold">{{ ($convocatoria->evaluation_date ?? $convocatoria->start_date)?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</span>
        </div>

        {{-- FIRMA --}}
        <div style="padding: 12px;">
            <div class="sig-line">
                <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                @if($school->ordenador_gasto_display_document)
                    <div style="font-size: 7.5px; color: #444;">Cédula de ciudadanía número {{ $school->ordenador_gasto_display_document }}</div>
                @endif
                <div class="sig-role">Rector - Ordenador del Gasto</div>
                <div style="font-size: 7.5px; color: #444;">{{ $school->name }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
