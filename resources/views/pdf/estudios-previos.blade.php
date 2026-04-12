<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estudios Previos - Convocatoria {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 15px 25px; }

        /* Border principal */
        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 8px 12px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #1e3a5f; }
        .header-center { text-align: center; }
        .header-right { width: 160px; text-align: center; border-left: 1px solid #1e3a5f; font-size: 9px; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .school-sub { font-size: 8px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 1px; margin-top: 4px; }

        /* Secciones */
        .section { border-bottom: 1px solid #ccc; }
        .section:last-child { border-bottom: none; }
        .section-header { background: #1e3a5f; color: #fff; padding: 5px 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-body { padding: 10px 12px; font-size: 10px; text-align: justify; }

        /* Fecha */
        .date-row { display: table; width: 100%; border-bottom: 1px solid #ccc; }
        .date-cell { display: table-cell; padding: 6px 12px; font-size: 10px; vertical-align: middle; }
        .date-label { font-weight: bold; width: 120px; }

        /* Tabla CDP */
        .cdp-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .cdp-table th { background: #e8edf3; font-size: 8px; text-transform: uppercase; padding: 5px 6px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .cdp-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9px; }
        .cdp-table .text-right { text-align: right; }
        .cdp-table .text-center { text-align: center; }
        .cdp-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        /* Monto grande */
        .amount-highlight { font-size: 12px; font-weight: bold; color: #1e3a5f; }

        /* Lista de documentos */
        .doc-list { margin: 4px 0; padding-left: 12px; }
        .doc-list li { margin-bottom: 3px; font-size: 9px; line-height: 1.4; }

        /* Conclusión */
        .conclusion { padding: 10px 12px; font-size: 10px; text-align: justify; border-top: 2px solid #1e3a5f; }

        /* Firmas */
        .signatures { width: 100%; margin-top: 40px; padding: 0 12px 15px; }
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-table td { text-align: center; vertical-align: bottom; padding: 0 20px; }
        .sig-line { border-top: 1px solid #333; width: 220px; margin: 0 auto; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; }

        /* Lugar y fecha final */
        .place-date { padding: 8px 12px; font-size: 10px; border-top: 1px solid #ccc; }

        /* Footer */
        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #999; }

        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- ===== HEADER ===== --}}
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if($school->logo_absolute_path && file_exists($school->logo_absolute_path))
                        <img src="{{ $school->logo_absolute_path }}" style="width: 55px; height: 55px; object-fit: contain;" alt="Logo">
                    @else
                        <div style="width: 55px; height: 55px; background: #e8edf3; border-radius: 4px; margin: 0 auto;"></div>
                    @endif
                </td>
                <td class="header-center">
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="school-sub">NIT: {{ $school->nit ?? 'N/A' }} &bull; {{ $school->municipality ?? '' }}</div>
                    <div class="doc-title">Estudios Previos a la Contratación</div>
                </td>
                <td class="header-right">
                    <div style="font-weight: bold; color: #1e3a5f;">Convocatoria</div>
                    <div style="font-size: 16px; font-weight: bold; color: #1e3a5f;">No. {{ $convocatoria->formatted_number }}</div>
                    <div style="margin-top: 3px;">Vigencia {{ $convocatoria->fiscal_year }}</div>
                </td>
            </tr>
        </table>

        {{-- ===== CIUDAD Y FECHA ===== --}}
        <div class="date-row">
            <div class="date-cell date-label">Ciudad y Fecha:</div>
            <div class="date-cell">{{ $school->municipality ?? 'N/A' }}, {{ $convocatoria->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? 'N/A' }}</div>
        </div>

        {{-- ===== 1. DESCRIPCIÓN DE LA NECESIDAD ===== --}}
        <div class="section">
            <div class="section-header">1. Descripción de la necesidad que se pretende satisfacer con el proceso contractual</div>
            <div class="section-body">
                {{ $convocatoria->object }}
            </div>
        </div>

        {{-- ===== 2. OBJETO A CONTRATAR ===== --}}
        <div class="section">
            <div class="section-header">2. Objeto a contratar, especificaciones e identificación del contrato a celebrar</div>
            <div class="section-body">
                {{ $convocatoria->object }}
                @if($convocatoria->justification)
                    <br><br><span class="bold">Justificación:</span> {{ $convocatoria->justification }}
                @endif
            </div>
        </div>

        {{-- ===== 3. DEFINICIÓN TÉCNICA ===== --}}
        <div class="section">
            <div class="section-header">3. Definición técnica de la forma en que se pretende satisfacer la necesidad</div>
            <div class="section-body">
                Contratar que le permita cumplir con el(los) requisito(s), el proceso se atenderá de conformidad con la ley 80 de 1993, sus decretos reglamentarios y el reglamento de contratación para la Institución Educativa aprobado por el Consejo Directivo, y el manual interno de contratación de la Institución Educativa.
            </div>
        </div>

        {{-- ===== 4. MODALIDAD DE SELECCIÓN ===== --}}
        <div class="section">
            <div class="section-header">4. Modalidad de selección del contratista</div>
            <div class="section-body">
                El contratista que se vincula para este proceso contractual se hará bajo la modalidad de contratación establecida en el Artículo 13 de la ley 715 de 2001, el decreto 1075 de 2015, y el manual interno de contratación de la Institución Educativa. Según el manual de contratación de la entidad el presente proceso se realizará bajo la modalidad de selección directa.
            </div>
        </div>

        {{-- ===== 5. CONDICIONES DEL CONTRATO ===== --}}
        <div class="section">
            <div class="section-header">5. Condiciones del contrato a celebrar - Vigencia del contrato</div>
            <div class="section-body">
                El término para la celebración del presente contrato es de
                <span class="bold">{{ $durationDays ?? 'N/A' }} días</span>,
                contados a partir de la fecha de suscripción del contrato.
                @if($contract && $contract->start_date && $contract->end_date)
                    <br>Desde: {{ $contract->start_date->format('d/m/Y') }} hasta {{ $contract->end_date->format('d/m/Y') }}.
                @endif
            </div>
        </div>

        {{-- ===== 6. SOPORTE TÉCNICO Y ECONÓMICO ===== --}}
        <div class="section">
            <div class="section-header">6. Soporte técnico y económico, valor estimado del contrato</div>
            <div class="section-body">
                <div class="amount-highlight" style="margin-bottom: 6px;">
                    ${{ number_format($amount, 2, ',', '.') }}
                </div>
                <div style="margin-bottom: 6px;">
                    <span class="bold uppercase">{{ $amountInWords }}</span>
                </div>
                <p style="margin-top: 6px;">
                    En el cual se encuentran incluidos impuestos a que haya lugar.
                </p>
                <p style="margin-top: 6px;">
                    Para el pago, el presupuesto interno y general de la Institución Educativa, dispondrá que pueda
                    efectuarse el pago correspondiente, según certificado de disponibilidad presupuestal y registro presupuestal así:
                </p>

                @if(count($cdpRows) > 0 || count($expenseCodeRows) > 0)
                    <table class="cdp-table" style="margin-top: 8px;">
                        <thead>
                            <tr>
                                <th style="width: 12%;">CDP N°</th>
                                <th style="width: 20%;">Código Rubro</th>
                                <th style="width: 48%;">Rubro</th>
                                <th style="width: 20%;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenseCodeRows as $i => $row)
                                <tr>
                                    <td class="text-center">{{ $cdpRows[$i]['cdp_number'] ?? '' }}</td>
                                    <td class="text-center">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right" style="padding-right: 10px;">TOTAL:</td>
                                <td class="text-right">${{ number_format(collect($expenseCodeRows)->sum('amount'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <p style="color: #888; font-style: italic; margin-top: 6px;">No hay CDPs registrados para esta convocatoria.</p>
                @endif
            </div>
        </div>

        {{-- ===== 7. OBLIGACIONES DEL CONTRATISTA ===== --}}
        <div class="section">
            <div class="section-header">7. Obligaciones del contratista</div>
            <div class="section-body">
                <ol class="doc-list" style="list-style-type: lower-alpha;">
                    <li>El contratista deberá cumplir el contrato de conformidad con los requerimientos técnicos necesarios para la ejecución del mismo.</li>
                    <li>Garantizar la calidad de los bienes y servicios prestados de acuerdo con la oferta presentada a la entidad.</li>
                    <li>Acatar las instrucciones que durante el desarrollo del contrato sean impartidas por el contratante.</li>
                    <li>Cumplir con lo dispuesto en la ley 100 de 1993 referente al sistema de seguridad social en pensión y salud y en especial con lo establecido en el artículo 23 del decreto No. 1703 de 2002 y la ley 797 de 2003, reglamentada por el decreto 510 de 2003.</li>
                </ol>
            </div>
        </div>

        {{-- ===== 8. CRITERIOS PARA SELECCIONAR LA OFERTA ===== --}}
        <div class="section">
            <div class="section-header">8. Criterios para seleccionar la oferta más favorable</div>
            <div class="section-body">
                MENOR PRECIO
            </div>
        </div>

        {{-- ===== 9. LUGAR DE EJECUCIÓN ===== --}}
        <div class="section">
            <div class="section-header">9. Lugar de ejecución del contrato</div>
            <div class="section-body">
                @if($contract && $contract->execution_place)
                    {{ $contract->execution_place }}
                @else
                    {{ $school->address ?? '' }}, {{ $school->municipality ?? '' }}
                @endif
            </div>
        </div>

        {{-- ===== 10. DOCUMENTOS PARA ACREDITAR CAPACIDAD ===== --}}
        <div class="section">
            <div class="section-header">10. Documentos para acreditar capacidad de contratación</div>
            <div class="section-body">
                <p class="bold" style="margin-bottom: 4px;">Documentos Generales:</p>
                <ol class="doc-list">
                    <li>Fotocopia de la cédula de ciudadanía</li>
                    <li>Fotocopia del RUT actualizado</li>
                    <li>Certificado de antecedentes disciplinarios, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de antecedentes fiscales, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de antecedentes judiciales, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de medidas correctivas, con fecha de expedición no mayor a 3 meses</li>
                    <li>Copia del certificado de afiliación y pago de seguridad social y Administradora de Riesgos Laborales, como independiente o empleado que deberá corresponder con la ejecución del contrato, siempre y cuando aplique de acuerdo a la normatividad vigente</li>
                    <li>Declaración de ausencia de inhabilidades e incompatibilidades</li>
                    <li>Certificado de libreta militar (hombres menores de 50 años de edad)</li>
                    <li>Certificado de matrícula mercantil/cámara de comercio actualizado (Si aplica según la norma del código de comercio vigente)</li>
                    <li>Certificado de Delitos Sexuales vigente</li>
                    <li>Documento adicional para contratar servicios para Persona Natural (En caso de que aplique): Hoja de vida en el formato Función Pública</li>
                </ol>

                <p class="bold" style="margin-top: 8px; margin-bottom: 4px;">Documentos Adicionales para contratar con personas jurídicas (En caso de que aplique):</p>
                <ol class="doc-list">
                    <li>Fotocopia de la cédula de ciudadanía del representante legal</li>
                    <li>Fotocopia del RUT actualizado de la persona jurídica</li>
                    <li>Certificado de antecedentes disciplinarios de la persona jurídica, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de antecedentes fiscales de la persona jurídica, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de antecedentes judiciales de la persona jurídica, con fecha de expedición no mayor a 3 meses</li>
                    <li>Certificado de matrícula mercantil/cámara de comercio actualizado</li>
                    <li>Copia de pago de afiliación a la seguridad social de la empresa o certificado firmado por el revisor fiscal de estar al día</li>
                </ol>
            </div>
        </div>

        {{-- ===== 11. SUPERVISIÓN Y CONTROL ===== --}}
        <div class="section">
            <div class="section-header">11. Supervisión y control a la ejecución del contrato</div>
            <div class="section-body">
                La supervisión y control a la ejecución del contrato, la realizará una persona natural, la cual será designada por el Ordenador del Gasto.
            </div>
        </div>

        {{-- ===== 12. ANÁLISIS DE RIESGOS ===== --}}
        <div class="section">
            <div class="section-header">12. Análisis de riesgos de la contratación y garantías</div>
            <div class="section-body">
                Se exigen al contratista garantías que amparen los siguientes riesgos:
                <br><br>
                <span class="bold">NO GENERA RIESGOS</span>
            </div>
        </div>

        {{-- ===== CONCLUSIÓN ===== --}}
        <div class="conclusion">
            <div style="text-align: center; font-size: 13px; font-weight: bold; color: #1e3a5f; margin-bottom: 8px; letter-spacing: 1px;">
                CONCLUSIÓN
            </div>
            <p>
                Teniendo en cuenta los anteriores aspectos, es viable contratar el objeto previsto en este estudio previo con la persona natural o jurídica que cumpla con las condiciones establecidas.
            </p>
        </div>

        {{-- ===== LUGAR Y FECHA ===== --}}
        <div class="place-date">
            {{ $school->municipality ?? 'N/A' }}, {{ $convocatoria->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
        </div>

        {{-- ===== FIRMAS ===== --}}
        <div class="signatures">
            <table class="sig-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                            <div class="sig-role">RECTOR(A)</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
