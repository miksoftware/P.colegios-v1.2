<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato No. {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 7.5px; color: #222; line-height: 1.35; }
        .container { padding: 8px 15px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 5px 8px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-sub { font-size: 7px; color: #555; }
        .doc-title { font-size: 10px; font-weight: bold; color: #1e3a5f; margin-top: 3px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 6px; font-size: 7.5px; vertical-align: top; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 140px; font-size: 7px; text-transform: uppercase; }

        .section-title { background: #1e3a5f; color: #fff; padding: 3px 6px; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        .section-body { padding: 4px 6px; font-size: 7.5px; text-align: justify; }

        .cdp-table { width: 100%; border-collapse: collapse; margin: 3px 0; }
        .cdp-table th { background: #e8edf3; font-size: 6.5px; padding: 2px 3px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; text-transform: uppercase; }
        .cdp-table td { padding: 2px 3px; border: 1px solid #ccc; font-size: 7px; }
        .cdp-table .right { text-align: right; }
        .cdp-table .center { text-align: center; }

        .bold { font-weight: bold; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 6px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 190px; margin: 0 auto; padding-top: 2px; }
        .sig-name { font-weight: bold; font-size: 7.5px; text-transform: uppercase; }
        .sig-role { font-size: 6.5px; color: #666; }
        .sig-detail { font-size: 6.5px; color: #444; }

        .footer { margin-top: 4px; text-align: center; font-size: 5.5px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-sub">{{ $school->nit ?? '' }}</div>
            <div class="school-sub">{{ $school->municipality ?? '' }}</div>
            <div class="doc-title">CONTRATO No. {{ $contract->formatted_number }}</div>
        </div>

        {{-- DATOS BÁSICOS --}}
        <table class="info-table">
            <tr><td class="info-label">Fecha de la Orden o Contrato:</td><td>{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td></tr>
            <tr><td class="info-label">Objeto del Orden:</td><td>{{ $contract->object }}</td></tr>
            <tr>
                <td class="info-label">Valor $:</td>
                <td class="bold">${{ number_format($amount, 2, ',', '.') }}</td>
                <td class="info-label" style="width: 110px;">Fuente Financiación:</td>
                <td>{{ $fundingSourceText }}</td>
            </tr>
            <tr>
                <td class="info-label">Plazo:</td>
                <td>{{ $contract->duration_days ?? 'N/A' }} DIAS</td>
            </tr>
            <tr>
                <td class="info-label">Contratista:</td>
                <td>{{ $supplier->full_name ?? '' }}</td>
                <td class="info-label">Representante Legal de la Empresa:</td>
                <td>{{ $supplier->full_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">NIT Contratista o Empresa:</td>
                <td>{{ $supplier->document_number ?? '' }} {{ $supplier->dv ?? '' }}</td>
                <td class="info-label">Doc. Identidad:</td>
                <td></td>
            </tr>
            <tr>
                <td class="info-label">Dirección Contratista:</td>
                <td>{{ $supplier->address ?? '' }}</td>
                <td class="info-label">Teléfono del Contratista:</td>
                <td>{{ $supplier->phone ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Supervisor:</td>
                <td>{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</td>
                <td class="info-label">C.C. No:</td>
                <td>{{ $contract->supervisor?->identification_number ?? $school->rector_display_document ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Ordenador del Gasto de la I.E.:</td>
                <td>{{ $school->ordenador_gasto_display_name }}</td>
                <td class="info-label">CC:</td>
                <td>{{ $school->ordenador_gasto_display_document ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Fecha de Iniciación:</td>
                <td>{{ $contract->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Fecha de Terminación:</td>
                <td>{{ $contract->end_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Oficina Gestora:</td>
                <td>RECTORÍA</td>
            </tr>
        </table>

        {{-- OBJETO DEL CONTRATO --}}
        <div class="section-title">Objeto del Contrato</div>
        <div class="section-body">{{ $contract->object }}</div>

        {{-- ALCANCE DEL CONTRATO --}}
        <div class="section-title">Alcance del Contrato</div>
        <div class="section-body">
            Para el cumplimiento del objeto el contratista deberá realizar el suministro de los equipos, elementos y/o servicios que se requieren, de acuerdo a las especificaciones técnicas, estudios previos, invitación pública y la propuesta presentada por el contratista, la cual forma parte integral del presente contrato.
        </div>

        {{-- CONDICIONES PARA CON EL CONTRATISTA --}}
        <div class="section-title">Condiciones para con el Contratista</div>
        <div class="section-body">
            EL CONTRATISTA se compromete a cumplir con todas las obligaciones que a continuación se señalan, así como las estipuladas en los estudios previos, la invitación pública del presente proceso de selección y en la oferta presentada y sus anexos, Además de las obligaciones y derechos contemplados en los artículos 4º y 5º de la ley 80 de 1993 y demás normas concordantes y los convenidos en la orden, EL CONTRATISTA se obliga especialmente a:
            <br><br>
            1) Cumplir con el objeto del contrato, en los términos y condiciones establecidos, de conformidad con lo estipulado en el presente contrato, los estudios previos, en la invitación pública, las adendas y aclaraciones expedidas a la misma y la oferta presentada por el CONTRATISTA, cumpliendo a cabalidad con la entrega de elementos, instalación o prestación de los servicios que hayan sido contratados de acuerdo a las especificaciones técnicas señaladas y estar dispuesto a efectuar las aclaraciones del objeto del contrato al supervisor del contrato en caso que sean requeridos y apliquen a la presente Orden. Igualmente deberá velar por la calidad de los equipos, elementos o servicios que ofrezca y que hayan sido contratados.
            <br>
            2) Atender en el término de 48 horas cualquier requerimiento que se efectuado por la institución, cuando las necesidades del objeto contratado lo ameriten.
            <br>
            3) Poner en conocimiento de la institución educativa cualquier situación irregular que se presente o que requiera de su participación, para un cabal cumplimiento de las obligaciones contractuales.
            <br>
            4) El contratista debe prever todos los costos indirectos y directos durante la ejecución del contrato.
            <br>
            5) Deberá contar con el personal necesario para la ejecución del contrato el cual no tendrá ninguna relación laboral con la institución educativa.
            <br>
            6) Sufragar los gastos que legalmente haya lugar para la legalización y ejecución del contrato, de conformidad con la normatividad que rige la materia.
            <br>
            7) Asegurar y prestar por su cuenta y riesgo los servicios contratados en la calidad, cantidad y lugar estipulado.
            <br>
            8) Acatar las órdenes, instrucciones e indicaciones que le imparta el supervisor del presente contrato.
            <br>
            9) No aceptar presiones o amenazas de quienes actúen por fuera de la ley y comunicar oportunamente a la institución educativa y a la autoridad competente, si ello ocurriere, so pena de que la Institución educativa declare la caducidad del contrato.
            <br>
            10) Acreditar afiliación a salud y pensión según lo establecido en el artículo 50 de la ley 789 de 2002, 797 de 2003 y Decreto 510 de 2003.
            <br>
            11) Allegar dentro del día hábil siguiente al llamado de la administración los documentos y garantías –si es del caso- requerido para la cumplida iniciación del contrato.
            <br>
            12) Acatar las orientaciones e instrucciones del supervisor del contrato.
            <br>
            13) Las demás que se señalen en la Ley para este tipo de contratos o las establecidas en la invitación publica y en la oferta presentada por EL CONTRATISTA y las que se deriven de su naturaleza.
            <br>
            14) Solo tiene derecho a los emolumentos expresamente convenidos sin que se genere relación laboral ni prestaciones sociales por motivo del presente contrato sin formalidades plenas.
            <br>
            15) Autoriza a la Institución para que por conducto de su Pagaduría, efectúe las deducciones por concepto de los siguientes gravámenes ordenanzales para que sean transferidos al fondo u organismo correspondientes así: a.) Dos (2%) por ciento por cada mil o fracción, por concepto de estampilla Pro- UIS., b) Dos por ciento (2%) del valor total de la orden por concepto de Estampillas Pro- Hospitales Universitarios Públicos Departamento de Santander y el respectivo descuento a Sistemas y computadores (10% sobre estampillas del Departamento) d) Los descuentos Municipales y de retención en la fuente que haya lugar y los demás gravámenes de Ley a que haya lugar.
            <br>
            16) Deberá cumplir con las obligaciones que se deriven para el desarrollo del objeto contratado.
        </div>

        {{-- VALOR DEL CONTRATO --}}
        <div class="section-title">Valor del Contrato</div>
        <div class="section-body">
            <span class="bold">${{ number_format($amount, 2, ',', '.') }}</span>
            <span class="bold" style="text-transform: uppercase;">{{ $amountInWords }}</span>
            <br><br>
            VALOR ANTES DE IVA: ${{ number_format((float)$contract->subtotal, 2, ',', '.') }}
            &nbsp;&nbsp; IVA: ${{ number_format((float)$contract->iva, 2, ',', '.') }}
            &nbsp;&nbsp; TOTAL: ${{ number_format($amount, 2, ',', '.') }}
        </div>

        {{-- CDP --}}
        <div class="section-title">Certificado de Disponibilidad Presupuestal</div>
        <div class="section-body">
            La Institución Educativa cuenta con la Disponibilidad Presupuestal requerida, la cual se adjunta al presente contrato así:
        </div>
        @if(count($cdpRows) > 0)
        <div style="padding: 2px 6px;">
            <table class="cdp-table">
                <thead>
                    <tr>
                        <th>Código Rubro</th>
                        <th>Nombre Rubro</th>
                        <th>CDP N.</th>
                        <th>Fecha CDP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cdpRows as $row)
                    <tr>
                        <td class="center">{{ $row['budget_item_code'] }}</td>
                        <td>{{ $row['budget_item_name'] }}</td>
                        <td class="center">{{ $row['cdp_number'] }}</td>
                        <td class="center">{{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- GARANTÍA --}}
        <div class="section-title">Garantía</div>
        <div class="section-body">
            La Institución Educativa solicita al contratista Certificar que la Garantía de las obligaciones que se derivan del objeto del presente Contrato, se establezcan por el plazo de ejecución del contrato y seis meses más. En cuanto a garantías el Manual de Contratación establece: El Ordenador del Gasto si considera necesario de acuerdo con la Naturaleza y Riesgo de la contratación que va a realizar exigirá la póliza y determinará los amparos que debe constituir. Ya que las garantías no serán obligatorias en los contratos de empréstito, en los ínter administrativos, en los de seguro y en los contratos cuyo valor sea inferior al 10% de la menor cuantía (28 S.M.L.V.). El artículo 2.2.1.2.1.4.5.
            <br><br>
            Según Manual de la Modalidad de Selección de Mínima Cuantía de Colombia Compra Eficiente Numeral 4º. Literal C. Establece: Las Entidades Estatales no están obligadas a exigir garantías en los Procesos de Contratación de mínima cuantía. Si la Entidad Estatal decide exigir garantías en los procesos de selección de mínima cuantía debe ser una consecuencia del Riesgo del Proceso de Contratación y del sector económico al cual pertenecen los posibles oferentes.
            <br><br>
            El Decreto 1082 del 26 de Mayo de 2015 establece la: "No obligatoriedad de garantías. En la contratación directa la exigencia de garantías establecidas en la Sección 3, que comprende los artículos 2.2.1.2.3.1.1 al 2.2.1.2.3.5.1. del presente decreto no es obligatoria y la justificación para exigirlas o no debe estar en los estudios y documentos previos."
            <br><br>
            Teniendo en cuenta la modalidad de contratación y su cuantía este contrato estará exento de garantías. Los contratos de suscritos en cuantía inferiores a 20 (Veinte) Salarios Mínimos Legales Vigentes, podrán estar exentos de la exigencia de Garantía única, siempre y cuando en la forma de pago del contrato se establezca previo informe de recibo a satisfacción del bien o servicios por parte de la Entidad, expedido por el Supervisor del contrato. No se solicita póliza al contratista, pero el contratista debe expedir una certificación donde garantice las obligaciones que se deriven del objeto del contrato, por el tiempo que establezca la ejecución del contrato y seis meses más.
            <br><br>
            De acuerdo como lo establece el Manual de Contratación de la Institución Educativa, El Ordenador del Gasto de la Institución Educativa tiene la responsabilidad de la exigencia de o no de la Garantía.
            <br><br>
            Basado en lo anterior las garantías son las siguientes:
            <br>
            <span class="bold">NO GENERA</span>
        </div>

        {{-- SUPERVISIÓN --}}
        <div class="section-title">Supervisión</div>
        <div class="section-body">
            La supervisión del presente contrato será ejercida por:
            <br>
            Nombre: <span class="bold">{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</span>
            <br>
            cédula de ciudadanía No. {{ $contract->supervisor?->identification_number ?? $school->rector_display_document ?? '' }}
            <br>
            Quien tiene nombramiento en la Institución en el cargo de: <span class="bold">{{ mb_strtoupper($contract->supervisor?->job_title ?? 'RECTOR') }}</span>
            <br><br>
            La Supervisión del presente contrato deberá controlar la perfecta ejecución del objeto contractual y su correcto cumplimiento. PARÁGRAFO: son funciones del supervisor:
            <br><br>
            1) Efectuar control general sobre la debida ejecución del contrato; 2) El supervisor efectuará las recomendaciones necesarias para el desarrollo exitoso del contrato, 3) Comunicar al contratista, las circunstancias que pudieran poner en riesgo la ejecución del objeto del contrato, 4) Adelantará la verificación documental de la ejecución del presente contrato; 5) Velar por el adecuado cumplimiento del objeto dentro del plazo y en las condiciones pactadas por las partes; 6) Hacer recomendaciones y sugerencias al contratista, con respecto a la ejecución del contrato; 7) Expedir certificación que dé cuenta del cumplimiento del contrato; 8) Allegar a la carpeta del contrato que reposa en la Oficina de pagaduría, toda la documentación original que se genere en relación con el contrato; 9) Velar por su liquidación dentro del término legal; 10) Entregar al contratista, la información, documentación, y demás elementos necesarios para la ejecución del contrato y coordinar lo necesario con la misma finalidad; 11) Exigir el cumplimiento del contrato en todas sus partes, para lo cual debe requerir el efectivo y oportuno desarrollo de las funciones señaladas al contratista, 12) Estudiar y recomendar los cambios sustanciales que se consideren convenientes o necesarios para el logro de los objetivos del contrato y presentarlos oportunamente a consideración de la institución educativa; 13) Emitir concepto previo sobre la suspensión, reiniciación, modificación, interpretación del contrato, terminación por mutuo acuerdo, imposición de sanciones y, en general, en todos los eventos que impliquen la modificación de las condiciones y términos contractuales, elaborando y coordinando el trámite de los documentos respectivos los cuales, cuando corresponda, serán suscritos por las partes del contrato; 14) Solicitar al contratista, efectuar los correctivos pertinentes cuando considere que no está cumpliendo cabalmente con las obligaciones del contrato y si no los efectúa en el plazo señalado, solicitar al ordenador del gasto de la institución educativa, la aplicación de las sanciones que corresponda; 15) Las demás necesarias para la ejecución del presente contrato. PARÁGRAFO.- Las aprobaciones que imparta el supervisor no relevan al contratista, de ninguna de las responsabilidades contraídas por razón de este contrato.
        </div>

        {{-- FORMA DE PAGO --}}
        <div class="section-title">Forma de Pago</div>
        <div class="section-body">
            La Institución educativa pagará el valor del contrato, previa autorización del supervisor, de la siguiente manera:
            <br><br>
            <span class="bold">{{ $contract->payment_method_name ?? 'UN (1) PAGO' }}</span>
            <br><br>
            PARÁGRAFO PRIMERO: DEDUCCIONES- El contratista autoriza a la Institución educativa, para que efectúe de los desembolsos a su favor, las deducciones por concepto de los gravámenes municipales y legales a que hubiere lugar, teniendo en cuenta para la respectiva deducción, los parámetros que para tal efecto haya establecido la disposición que dio origen al gravamen. PARÁGRAFO SEGUNDO: El contratista deberá presentar la factura o la cuenta de cobro, según sea el caso, constancia de pago de aportes a las seguridades sociales demás documentos señalados en la presente cláusula. La no presentación de estos documentos o su presentación extemporánea exonera a la Institución del pago de intereses moratorios.
        </div>

        {{-- INHABILIDADES --}}
        <div class="section-title">Inhabilidades</div>
        <div class="section-body">
            EL CONTRATISTA afirma, bajo la gravedad del juramento, el cual se entiende prestado con la firma del presente Contrato, que no se halla incurso en ninguna causal de inhabilidad, incompatibilidad e impedimento previstas en la ley y específicamente en los Artículos 8 y 9 de la Ley 80 de 1993, así como en el artículo 122 de la Constitución Política, modificado por el Acto Legislativo Numero 01 de julio 14 de 2009, y concordantes.
        </div>

        {{-- PLAZO DEL CONTRATO --}}
        <div class="section-title">Plazo del Contrato</div>
        <div class="section-body">
            El término para la ejecución del presente contrato, contados a partir de que se agoten los requisitos establecidos en el inciso segundo del artículo 41 de la ley 80 de 1993 es: <span class="bold">{{ $contract->duration_days ?? 'N/A' }} DIAS</span>
        </div>

        {{-- SUSPENSIÓN TEMPORAL --}}
        <div class="section-title">Suspensión Temporal del Contrato</div>
        <div class="section-body">
            Las partes podrán suspender la ejecución del contrato, previo concepto vinculante del supervisor, donde conste clara y detalladamente las razones de la suspensión y el plazo de la misma, a su vez la obligación del contratista de prorrogar la vigencia de la garantía única en caso de haberse solicitado por un término igual al de la suspensión. Expirado el plazo de la suspensión, el contrato se reiniciará dejando constancia del hecho en el acta correspondiente, la cual diligenciará en la misma forma que el acta de suspensión.
        </div>

        {{-- CESIÓN --}}
        <div class="section-title">Cesión</div>
        <div class="section-body">
            El Contratista no podrá ceder en todo o en parte del contrato, sin previa autorización escrita del contratante.
        </div>

        {{-- CLÁUSULA PENAL --}}
        <div class="section-title">Cláusula Penal Pecuniaria y Multas</div>
        <div class="section-body">
            En caso de declaratoria de incumplimiento de sus obligaciones, el contratista pagará al contratante, a título de sanción pecuniaria y sin necesidad de requerimientos judiciales o extrajudiciales, una suma igual al diez por ciento (10%) del valor total del presente contrato. En caso de incumplimiento parcial la Institución educativa podrá imponer multas sucesivas hasta por el 5% del valor del contrato. El valor de la cláusula penal y las multas podrán cobrarse de acuerdo a los mecanismos previstos por el artículo 17 de la ley 1150 de 2007. PARÁGRAFO ÚNICO. El procedimiento para la imposición de multas o cláusula penal pecuniaria, se adelantará con plena aplicación del derecho al debido proceso (art. 29 de la Constitución nacional) y en concordancia con lo dispuesto por el artículo 86 de la ley 1474 de 2011, para lo cual una vez constatado por el supervisor de la orden, que el contratista ha incurrido o está incurriendo en una o varias de las causales de incumplimiento, pondrá en forma inmediata en conocimiento de aquel el hecho y lo requerirá por escrito, para que dentro del término que se le señale, proceda a dar las explicaciones que correspondan y adelante las actividades que le permitan conjurar la situación que lo puso en condiciones de apremio, so pena de proceder a aplicar las sanciones a que hubiere lugar.
        </div>

        {{-- AUSENCIA DE RELACIÓN LABORAL --}}
        <div class="section-title">Ausencia de Relación Laboral</div>
        <div class="section-body">
            El presente contrato no genera relación laboral alguna entre el contratante y el contratista y en consecuencia tampoco el pago de prestaciones sociales y de ningún tipo de emolumentos distintos al valor acordado en el presente contrato, ni con el Contratista ni con el personal que requiere el contratista para el debido desarrollo del objeto del presente contrato.
        </div>

        {{-- TERMINACIÓN --}}
        <div class="section-title">Terminación del Contrato</div>
        <div class="section-body">
            Además de los eventos previstos por el artículo 17 de la ley 80 de 1993, el contratante dispondrá la terminación anticipada del presente Contrato, mediante acto debidamente motivado susceptible del recurso de reposición, en caso de incumplimiento de los requisitos de celebración y de ejecución del presente contrato, cuando la ley no disponga otra medida, en los contemplados por los numerales 1, 2 y 4 del artículo 44 de la ley 80 de 1993, y en todo otro evento establecido por la ley. PARÁGRAFO ÚNICO. Cuando la terminación del contrato sea resultado del acuerdo de las partes, requerirá el concepto previo no vinculante del supervisor y no dará lugar al reconocimiento de compensaciones e indemnizaciones. Se entenderá en todo caso, que el contratista, renuncia expresamente con la suscripción del contrato a cualquier reclamación posterior.
        </div>

        {{-- LIQUIDACIÓN --}}
        <div class="section-title">Liquidación del Contrato</div>
        <div class="section-body">
            La liquidación del presente contrato se realizará de acuerdo con lo previsto manual de contratación aprobado por el Consejo Directivo, y dentro de los seis (6) meses siguientes al vencimiento del plazo de ejecución, para lo cual el supervisor preparará y suscribirá el acta correspondiente. En esta etapa las partes acordarán los ajustes, revisiones y reconocimientos a que haya lugar. En el acta de liquidación constarán, además, los acuerdos, conciliaciones y transacciones a que llegaren las partes para poner fin a las divergencias presentadas y poder declararse a paz y salvo. Para la liquidación se exigirá al contratista la extensión o ampliación, si es del caso, de la garantía para avalar las obligaciones que deba cumplir con posterioridad a la extinción del contrato.
        </div>

        {{-- DOMICILIO --}}
        <div class="section-title">Domicilio</div>
        <div class="section-body">
            Para todos los efectos del presente contrato, las partes acuerdan como domicilio contractual la ciudad de <span class="bold">{{ $school->municipality ?? '' }}</span>
        </div>

        {{-- PERFECCIONAMIENTO --}}
        <div class="section-title">Perfeccionamiento y Ejecución</div>
        <div class="section-body">
            El presente Contrato se entenderá perfeccionado con las firmas de las partes una vez firmado por el contratista y ordenador de gasto.
        </div>

        {{-- GASTOS --}}
        <div class="section-title">Gastos</div>
        <div class="section-body">
            El contratista deberá asumir en su totalidad las estampillas gubernamentales y demás gastos que sean necesarios para la legalización del presente contrato.
        </div>

        {{-- ESPACIO PARA FIRMAS --}}
        <div class="section-title">Espacio para Firmas</div>
        <div style="padding: 3px 6px; font-size: 7px;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 50%;">Por la Institución Educativa:</div>
                <div style="display: table-cell; width: 50%;">Por el Contratista:</div>
            </div>
        </div>
        <div style="padding: 6px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                            <div class="sig-detail">Cédula de ciudadanía número {{ $school->ordenador_gasto_display_document ?? '' }}</div>
                            <div class="sig-role">Ordenador del gasto</div>
                            <div class="sig-detail">{{ $school->name }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $supplier->full_name ?? '' }}</div>
                            <div class="sig-detail">Documento de identidad (CC / NIT) {{ $supplier->document_number ?? '' }} {{ $supplier->dv ?? '' }}</div>
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
