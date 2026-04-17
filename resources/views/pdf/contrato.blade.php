<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato No. {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #222; line-height: 1.4; }
        .container { padding: 10px 18px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 6px 10px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-sub { font-size: 7px; color: #555; }
        .doc-title { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-top: 4px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 8px; font-size: 8px; vertical-align: top; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 150px; font-size: 7.5px; text-transform: uppercase; }

        .section-title { background: #1e3a5f; color: #fff; padding: 3px 8px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        .section-body { padding: 5px 8px; font-size: 8px; text-align: justify; }

        .cdp-table { width: 100%; border-collapse: collapse; margin: 3px 0; }
        .cdp-table th { background: #e8edf3; font-size: 7px; padding: 3px 4px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; text-transform: uppercase; }
        .cdp-table td { padding: 3px 4px; border: 1px solid #ccc; font-size: 7.5px; }
        .cdp-table .right { text-align: right; }
        .cdp-table .center { text-align: center; }

        .bold { font-weight: bold; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 8px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 2px; }
        .sig-name { font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .sig-role { font-size: 6.5px; color: #666; }
        .sig-detail { font-size: 7px; color: #444; }

        .footer { margin-top: 6px; text-align: center; font-size: 6px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            @if($school->dane_code)<div class="school-sub">{{ $school->dane_code }}</div>@endif
            <div class="school-sub">{{ $school->municipality ?? '' }}</div>
            <div class="doc-title">CONTRATO No. {{ $contract->formatted_number }}</div>
        </div>

        {{-- DATOS BÁSICOS --}}
        <table class="info-table">
            <tr><td class="info-label">Fecha de la Orden:</td><td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td></tr>
            <tr><td class="info-label">Objeto del Orden:</td><td>{{ $contract->object }}</td></tr>
            <tr>
                <td class="info-label">Valor:</td>
                <td class="bold">${{ number_format($amount, 2, ',', '.') }}</td>
                <td class="info-label" style="width: 120px;">Fuente Financiación:</td>
                <td>{{ implode(', ', $expenseCodeRows) ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">NIT:</td>
                <td>{{ $supplier->document_number ?? '' }}</td>
                <td class="info-label">Representante Legal:</td>
                <td>{{ $supplier->full_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Contratista:</td>
                <td>{{ $supplier->full_name ?? '' }}</td>
                <td></td>
                <td>DV: {{ $supplier->dv ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Modalidad del Gasto de la Orden:</td>
                <td>{{ $contract->modality_name }}</td>
                <td class="info-label">Supervisor:</td>
                <td>{{ $contract->supervisor?->name ?? $school->rector_name ?? '' }}</td>
            </tr>
        </table>

        {{-- OBJETO CONTRACTUAL --}}
        <div class="section-title">Objeto Contractual</div>
        <div class="section-body">{{ $contract->object }}</div>

        {{-- CONDICIONES PAGO CON EL CONTRATISTA --}}
        <div class="section-title">Condiciones Pago con el Contratista</div>
        <div class="section-body">
            El pago se realizará de acuerdo con la forma de pago pactada: <span class="bold">{{ $contract->payment_method_name ?? 'UN PAGO' }}</span>.
            Dicho pago se realizará previa presentación de la factura o documento equivalente, certificación de cumplimiento expedida por el supervisor, y demás documentos requeridos.
            El contratista deberá estar al día en el pago de aportes al sistema de seguridad social integral.
        </div>

        {{-- OBLIGACIONES DEL CONTRATISTA --}}
        <div class="section-title">Obligaciones del Contratista</div>
        <div class="section-body">
            El contratista se obliga a cumplir con el objeto del contrato de acuerdo con las especificaciones técnicas, a entregar los bienes y/o servicios en los términos pactados, a mantener vigentes las garantías cuando aplique, y a cumplir con todas las obligaciones laborales y de seguridad social.
        </div>

        {{-- VALOR DEL CONTRATO --}}
        <div class="section-title">Valor del Contrato</div>
        <div class="section-body">
            <span class="bold" style="font-size: 10px;">${{ number_format($amount, 2, ',', '.') }}</span>
            <br>
            <span class="bold" style="text-transform: uppercase;">{{ $amountInWords }}</span>
        </div>

        {{-- CDP --}}
        @if(count($cdpRows) > 0)
        <div class="section-title">Certificado de Disponibilidad Presupuestal</div>
        <div style="padding: 3px 8px;">
            <table class="cdp-table">
                <thead>
                    <tr>
                        <th>CDP N°</th>
                        <th>Código Rubro</th>
                        <th>Nombre Rubro</th>
                        <th>Fuente Financiación</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cdpRows as $row)
                    <tr>
                        <td class="center">{{ $row['cdp_number'] }}</td>
                        <td class="center">{{ $row['budget_item_code'] }}</td>
                        <td>{{ $row['budget_item_name'] }}</td>
                        <td>{{ $row['funding_source'] }}</td>
                        <td class="right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- CLÁUSULAS LEGALES --}}
        <div class="section-title">Cláusulas del Contrato</div>
        <div class="section-body">
            <span class="bold">CLÁUSULA PRIMERA - OBJETO:</span> {{ $contract->object }}
            <br><br>
            <span class="bold">CLÁUSULA SEGUNDA - VALOR:</span> El valor total del presente contrato es de ${{ number_format($amount, 2, ',', '.') }} ({{ $amountInWords }}).
            <br><br>
            <span class="bold">CLÁUSULA TERCERA - PLAZO:</span> El plazo de ejecución del presente contrato será de {{ $contract->duration_days ?? 'N/A' }} días, contados a partir de la suscripción del acta de inicio.
            <br><br>
            <span class="bold">CLÁUSULA CUARTA - FORMA DE PAGO:</span> {{ $contract->payment_method_name ?? 'UN PAGO' }}.
            <br><br>
            <span class="bold">CLÁUSULA QUINTA - OBLIGACIONES DEL CONTRATISTA:</span> El contratista se obliga a: a) Cumplir con el objeto del contrato. b) Entregar los bienes y/o servicios de acuerdo con las especificaciones. c) Presentar los informes requeridos. d) Cumplir con las obligaciones de seguridad social.
            <br><br>
            <span class="bold">CLÁUSULA SEXTA - SUPERVISIÓN:</span> La supervisión del contrato estará a cargo de {{ $contract->supervisor?->name ?? $school->rector_name ?? '' }}.
            <br><br>
            <span class="bold">CLÁUSULA SÉPTIMA - GARANTÍAS:</span> NO GENERA RIESGOS. De conformidad con el manual de contratación de la Institución Educativa.
            <br><br>
            <span class="bold">CLÁUSULA OCTAVA - AUSENCIA DE RELACIÓN LABORAL:</span> El presente contrato no genera relación laboral entre las partes. El contratista actuará como independiente.
            <br><br>
            <span class="bold">CLÁUSULA NOVENA - DURACIÓN DEL CONTRATO:</span> El presente contrato tendrá una duración de {{ $contract->duration_days ?? 'N/A' }} días.
            <br><br>
            <span class="bold">CLÁUSULA DÉCIMA - DOMICILIO:</span> Para efectos del presente contrato, las partes acuerdan como domicilio contractual la ciudad de {{ $school->municipality ?? '' }}.
        </div>

        {{-- PERFECCIONAMIENTO Y EJECUCIÓN --}}
        <div class="section-title">Perfeccionamiento y Ejecución</div>
        <div class="section-body">
            El presente contrato se perfecciona con las firmas de las partes y se entiende que fue firmado por el contratante y el ordenador del gasto.
        </div>

        {{-- LUGAR --}}
        <div class="section-body" style="border-top: 1px solid #ddd;">
            <span class="bold">Lugar:</span> {{ $contract->execution_place ?? $school->municipality ?? '' }}
        </div>

        {{-- FIRMAS --}}
        <div style="padding: 8px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                            @if($school->rector_document)
                                <div class="sig-detail">{{ $school->rector_document }}</div>
                            @endif
                            <div class="sig-role">RECTOR(A)</div>
                            <div class="sig-detail">{{ $school->name }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            <div class="sig-detail">{{ $supplier->full_document ?? '' }}</div>
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
