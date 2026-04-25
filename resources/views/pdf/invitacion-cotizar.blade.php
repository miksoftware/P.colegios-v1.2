<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación a Cotizar - {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #222; line-height: 1.4; }
        .container { padding: 12px 20px; }
        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header { text-align: center; padding: 8px 12px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-nit { font-size: 8px; color: #444; }
        .school-muni { font-size: 8px; color: #444; }
        .doc-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-top: 6px; letter-spacing: 0.5px; }

        /* Texto */
        .text-block { padding: 6px 12px; font-size: 8px; text-align: justify; }
        .info-line { padding: 3px 12px; font-size: 8.5px; }
        .bold { font-weight: bold; }

        /* Recuadro convocatoria */
        .conv-box { border: 2px solid #1e3a5f; margin: 6px 12px; }
        .conv-box-header { background: #1e3a5f; color: #fff; padding: 4px 8px; font-size: 8px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; }
        .conv-box-row { display: table; width: 100%; border-bottom: 1px solid #ddd; }
        .conv-box-row:last-child { border-bottom: none; }
        .conv-box-label { display: table-cell; width: 180px; padding: 4px 8px; font-weight: bold; font-size: 8px; text-transform: uppercase; color: #1e3a5f; vertical-align: top; }
        .conv-box-value { display: table-cell; padding: 4px 8px; font-size: 8px; vertical-align: top; }

        /* Tabla documentos */
        .doc-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .doc-table th { background: #e8edf3; font-size: 7px; text-transform: uppercase; padding: 3px 4px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .doc-table td { padding: 3px 4px; border: 1px solid #ccc; font-size: 7.5px; }
        .doc-table .center { text-align: center; }

        /* Secciones */
        .section-title { background: #1e3a5f; color: #fff; padding: 4px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Tabla plazos */
        .plazo-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .plazo-table th, .plazo-table td { padding: 3px 6px; border: 1px solid #aaa; font-size: 8px; }
        .plazo-table th { background: #e8edf3; font-weight: bold; color: #1e3a5f; text-transform: uppercase; font-size: 7px; }

        /* Tabla criterios */
        .criterio-table { width: 60%; border-collapse: collapse; margin: 4px auto; }
        .criterio-table th, .criterio-table td { padding: 3px 8px; border: 1px solid #aaa; font-size: 8px; }
        .criterio-table th { background: #e8edf3; font-weight: bold; color: #1e3a5f; text-transform: uppercase; font-size: 7px; }

        /* Firmas */
        .sig-line { border-top: 1px solid #333; width: 240px; padding-top: 3px; margin-top: 30px; }
        .sig-name { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .sig-role { font-size: 7px; color: #666; }
        .sig-detail { font-size: 7.5px; color: #444; }

        .footer { margin-top: 8px; text-align: center; font-size: 6px; color: #999; }
        .separator { border-top: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- ===== HEADER ===== --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-nit">{{ $school->nit ?? '' }}</div>
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="doc-title">Invitación a Cotizar y/o a Presentar Propuesta N. {{ $convocatoria->convocatoria_number }}</div>
        </div>

        {{-- ===== FECHA Y TEXTO INTRODUCTORIO ===== --}}
        <div class="info-line separator">
            <span class="bold">FECHA</span> {{ $convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
        </div>
        <div class="text-block">
            En cumplimiento a lo establecido en el CAPÍTULO 2, Numeral 1 del Manual de contratación institucional aprobado mediante acuerdo No. {{ $school->contracting_manual_approval_number ?? 'N/A' }} de fecha {{ $school->contracting_manual_approval_date ? \Carbon\Carbon::parse($school->contracting_manual_approval_date)->translatedFormat('d \\d\\e F \\d\\e Y') : 'N/A' }} por el Consejo Directivo, en la cual se establecen los parámetros para garantizar la selección objetiva del proveedor, se publica la presente convocatoria para la comparación de cotizaciones.
        </div>
        <div class="text-block">
            Podrán participar en el presente proceso de selección todas las personas naturales o jurídicas, legalmente constituidas en Colombia, nacionales o extranjeras, las uniones temporales o consorcios, que cumplan con los requisitos exigidos en la presente invitación pública y que se encuentren en capacidad de ejecutar el objeto del contrato.
        </div>

        {{-- ===== RECUADRO CONVOCATORIA ===== --}}
        <div class="conv-box">
            <div class="conv-box-header">Convocatoria abierta para la recepción de cotizaciones con el fin de contratar</div>
            <div class="conv-box-row">
                <div class="conv-box-label">Objeto:</div>
                <div class="conv-box-value">{{ $convocatoria->object }}</div>
            </div>
            <div class="conv-box-row">
                <div class="conv-box-label">Presupuesto Asignado:</div>
                <div class="conv-box-value bold">$ {{ number_format($amount, 2, ',', '.') }}</div>
            </div>
            <div class="conv-box-row">
                <div class="conv-box-label">Fecha Publicación de la Propuesta:</div>
                <div class="conv-box-value">{{ $convocatoria->start_date?->format('d/m/Y') ?? '' }}</div>
            </div>
        </div>

        {{-- ===== DOCUMENTOS REQUERIDOS ===== --}}
        <div style="padding: 4px 12px;">
            <p class="bold" style="font-size: 8px; margin-bottom: 4px; text-align: center;">DOCUMENTOS REQUERIDOS PARA LA PRESENTACIÓN DE LA PROPUESTA:</p>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 45%;" rowspan="2">Documento solicitado</th>
                        <th style="width: 15%;" rowspan="2">Persona Natural</th>
                        <th colspan="2">Persona Jurídica</th>
                    </tr>
                    <tr>
                        <th style="width: 20%;">Rep. Legal</th>
                        <th style="width: 20%;">Empresa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Presentación de la Cotización</td>
                        <td class="center">x</td><td class="center"></td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>Fotocopia de la cédula de ciudadanía</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Copia del registro único tributario (RUT)</td>
                        <td class="center">x</td><td class="center"></td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>Certificado de existencia (Cámara de Comercio)</td>
                        <td class="center">x</td><td class="center"></td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>Certificado de ausencia de antecedentes disciplinarios (Procuraduría)</td>
                        <td class="center">x</td><td class="center">x</td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>Certificado de ausencia de antecedentes fiscales</td>
                        <td class="center">x</td><td class="center">x</td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>Certificado de ausencia de antecedentes judiciales</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Certificado de medidas correctivas</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Copia de la libreta militar para hombres menores de 50 años</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Certificado REDAM</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Certificado de Delitos Sexuales</td>
                        <td class="center">x</td><td class="center">x</td><td class="center"></td>
                    </tr>
                    <tr>
                        <td>Certificación Bancaria para la transferencia</td>
                        <td class="center">x</td><td class="center"></td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>PARA PERSONAS JURÍDICAS: Copia de pago de la planilla de seguridad social al día.</td>
                        <td class="center"></td><td class="center"></td><td class="center">x</td>
                    </tr>
                    <tr>
                        <td>PARA PERSONAS NATURALES: Certificación de afiliación a SALUD, PENSIÓN Y ARL o planilla de pago</td>
                        <td class="center">x</td><td class="center"></td><td class="center"></td>
                    </tr>
                </tbody>
            </table>
            <p style="font-size: 7.5px; margin-top: 3px;">
                <span class="bold">OBSERVACIÓN:</span> La liquidación y pago por este concepto se hará sobre el 40% del valor total del contrato (Esto aplica para valores mayores a 1 SMLV).
            </p>
        </div>

        {{-- ===== ELABORACIÓN DE LA PROPUESTA ===== --}}
        <div class="section-title">Elaboración de la Propuesta</div>
        <div class="text-block">
            Los interesados deberán tener en cuenta las siguientes condiciones:
            <br><br>
            a.) Las cotizaciones junto con requisitos habilitantes DEBERÁN SER RADICADAS EN EL CORREO ELECTRÓNICO DE LA INSTITUCIÓN
            <span class="bold">{{ $school->email ?? '' }}</span>
            o entregadas directamente en la dirección
            <br>
            <span class="bold">{{ $school->address ?? '' }}</span>
            en las oficinas de secretaría de la institución educativa.
            <br><br>
            SEGÚN LA FECHA PREVISTA COMO LÍMITE PARA LA RECEPCIÓN DE LAS MISMAS. Las cotizaciones que no se encuentren dentro de la respectiva hora y fecha fijadas serán consideradas como extemporáneas y NO SERÁN TENIDAS EN CUENTA.
            <br><br>
            b. La cotización deberá estar suscrita por su representante.
            <br><br>
            c.) La cotización deberá permanecer vigente por un período de 30 días calendario a partir de la fecha de cierre de la invitación.
        </div>

        {{-- ===== PLAZO LÍMITE ===== --}}
        <div class="section-title">Plazo Límite para la Presentación de la Propuesta</div>
        <div style="padding: 4px 12px;">
            <table class="plazo-table">
                <tbody>
                    <tr>
                        <td class="bold" style="width: 10%;">DÍA:</td>
                        <td style="width: 40%;">{{ $convocatoria->end_date?->format('d/m/Y') ?? $convocatoria->start_date?->format('d/m/Y') ?? '' }}</td>
                        <td class="bold" style="width: 10%;">HORA:</td>
                        <td style="width: 40%;">{{ $convocatoria->end_time ? \Carbon\Carbon::parse($convocatoria->end_time)->format('h:i:s a') : '11:30:00 a. m.' }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="plazo-table" style="margin-top: 2px;">
                <tbody>
                    <tr>
                        <td colspan="4" class="bold" style="text-align: center;">Revisión de Propuestas</td>
                    </tr>
                    <tr>
                        <td class="bold" style="width: 10%;">DÍA:</td>
                        <td style="width: 40%;">{{ $convocatoria->evaluation_date?->format('d/m/Y') ?? '' }}</td>
                        <td class="bold" style="width: 10%;">HORA:</td>
                        <td style="width: 40%;">1:00:00 p. m.</td>
                    </tr>
                </tbody>
            </table>
            <table class="plazo-table" style="margin-top: 2px;">
                <tbody>
                    <tr>
                        <td colspan="4" class="bold" style="text-align: center;">Fecha de Firma de Orden o Contrato</td>
                    </tr>
                    <tr>
                        <td class="bold" style="width: 10%;">DÍA:</td>
                        <td colspan="3">{{ $convocatoria->start_date?->format('d/m/Y') ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ===== FORMA DE PAGO ===== --}}
        <div class="section-title">Forma de Pago</div>
        <div class="text-block">A CONVENIR</div>

        {{-- ===== DESCUENTOS APLICABLES ===== --}}
        <div class="section-title">Descuentos Aplicables</div>
        <div class="text-block">
            <span class="bold">1.- DESCUENTOS POR RETENCIÓN EN LA FUENTE:</span> La Institución Educativa realizará los descuentos de Ley por concepto de retención en la fuente por servicios, compras u honorarios según corresponda y siempre y cuando se cumpla la base para realizar el descuento y de Retención de IVA, si es el caso.
            <br><br>
            <span class="bold">2.- DESCUENTOS POR ESTAMPILLAS DEPARTAMENTALES:</span> Para la cancelación de la compra o servicio descrito en el presente documento, el Proveedor deberá presentar el recibo de pago de estampillas que se liquidan en la Casa del Libro Total siempre y cuando se cumpla la base para la liquidación.
            <br><br>
            <span class="bold">3.- DESCUENTOS POR ESTAMPILLAS MUNICIPALES:</span> La Institución Educativa descontará las estampillas municipales en caso de que apliquen, cumpliendo con las bases para liquidación.
        </div>

        {{-- ===== CONVOCATORIA A VEEDURÍAS ===== --}}
        <div class="section-title">Convocatoria a Veedurías Ciudadanas</div>
        <div class="text-block">
            En cumplimiento al artículo 66 de la ley 80 de 1993 y la ley 850 de 2003, convoca a todos los comités u organizaciones de veedurías ciudadanas que se encuentran legalmente establecidas en este municipio, con el fin de que participen del control social a los procesos adelantados por esta institución.
            <br><br>
            La documentación relacionada de la presente Selección Objetiva puede ser consultada en la Secretaría de la Institución Educativa ubicada en
            <br>
            <span class="bold">{{ $school->address ?? '' }}</span>
            a partir los 3 días hábiles posterior a la publicación de la convocatoria.
            @if($school->email)
                <br>Así mismo las observaciones y sugerencias pueden dirigirse al correo electrónico: <span class="bold">{{ $school->email }}</span>
            @endif
        </div>

        {{-- ===== CONSULTA DE DOCUMENTOS PREVIOS ===== --}}
        <div class="section-title">Consulta de Documentos Previos</div>
        <div class="text-block">
            Los estudios y documentos previos que hacen parte del presente proceso de selección podrán ser consultados en la oficina de secretaría de la Institución Educativa ubicada en:
            <br>
            <span class="bold">{{ $school->address ?? '' }}</span>
        </div>

        {{-- ===== DECLARATORIA DESIERTA ===== --}}
        <div class="section-title">Declaratoria Desierta</div>
        <div class="text-block">
            a) Cuando ninguno de las personas naturales o jurídicas postuladas cumplan con el lleno de los requisitos exigidos en la convocatoria.
            <br>
            b) Cuando no se presente ninguna postulación. Por lo tanto la convocatoria se declarará DESIERTA.
        </div>

        {{-- ===== CRITERIO DE SELECCIÓN ===== --}}
        <div class="section-title">Criterio de Selección</div>
        <div style="padding: 4px 12px;">
            <table class="criterio-table">
                <thead>
                    <tr>
                        <th style="width: 70%;">Criterio</th>
                        <th style="width: 30%;">Puntaje</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: justify; font-size: 7.5px;">
                            Para la escogencia de la oferta se hará un análisis comparativo y evaluativo de las propuestas, teniendo en cuenta el criterio de la propuesta más económica que satisfaga las necesidades requeridas por la Institución. Esta será la ganadora. La calificación de cada una de las propuestas se hará sobre un máximo de 100 puntos. PRIMER CRITERIO por MENOR PRECIO se asignará un puntaje de 90 puntos y al segundo 80 puntos y así sucesivamente de acuerdo al número de propuestas recibida.
                        </td>
                        <td style="text-align: center; font-weight: bold; vertical-align: middle;">100</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ===== EMPATE ===== --}}
        <div class="section-title">Empate</div>
        <div class="text-block">
            En caso de empate en el puntaje total de dos o más cotizaciones la INSTITUCIÓN EDUCATIVA escogerá el oferente que tenga el mayor puntaje en el primero de los factores de escogencia y calificación establecidos del Proceso de Contratación es decir el factor económico. Si persiste el empate, escogerá la cotización que tenga el mayor puntaje en el segundo de los factores de escogencia y calificación establecidos en los pliegos del Proceso de Contratación técnico y así sucesivamente hasta agotar la totalidad de los factores de escogencia y calificación establecidos en los pliegos de condiciones. De persistir el empate se dará aplicación a las reglas señaladas en el artículo 2.2.1.1.2.2.9 del Decreto 1082 de 2015.
        </div>

        {{-- ===== FIRMA ===== --}}
        <div style="padding: 12px;">
            <div class="sig-line">
                <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                @if($school->ordenador_gasto_display_document)
                    <div class="sig-detail">Cédula de ciudadanía número {{ $school->ordenador_gasto_display_document }}</div>
                @endif
                <div class="sig-role">Rector - Ordenador del gasto</div>
                <div class="sig-detail">{{ $school->name }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
