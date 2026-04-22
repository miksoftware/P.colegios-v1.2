<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución Designación de Supervisión - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 8px; color: #555; }
        .school-muni { font-size: 9px; color: #444; }

        .title-row { display: table; width: 100%; border-bottom: 1px solid #ddd; }
        .title-cell { display: table-cell; padding: 8px 15px; vertical-align: middle; }
        .title-text { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .title-number { font-size: 12px; font-weight: bold; color: #1e3a5f; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 210px; font-size: 9px; }

        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        .funciones-list { margin: 6px 0; padding-left: 15px; }
        .funciones-list li { margin-bottom: 5px; font-size: 9.5px; line-height: 1.4; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 35px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 15px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 220px; margin: 0 auto; padding-top: 3px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; text-transform: uppercase; }

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
        </div>

        {{-- TÍTULO --}}
        <div class="title-row">
            <div class="title-cell">
                <span class="title-text">Resolución Designación de Supervisión N.</span>
            </div>
            <div class="title-cell" style="text-align: right;">
                <span class="title-number">{{ $contract->formatted_number }}</span>
            </div>
        </div>

        {{-- DATOS --}}
        <table class="info-table">
            <tr>
                <td class="info-label">DE:</td>
                <td>RECTORÍA</td>
            </tr>
            <tr>
                <td class="info-label">ASUNTO:</td>
                <td>DESIGNACIÓN COMO SUPERVISOR DEL CONTRATO</td>
            </tr>
            <tr>
                <td class="info-label">Ciudad y Fecha:</td>
                <td>{{ $school->municipality ?? '' }} &nbsp;&nbsp;&nbsp; {{ $contract->start_date?->format('d/m/y') ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">Apreciado(a):</td>
                <td class="bold">{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</td>
            </tr>
            <tr>
                <td class="info-label">Cargo:</td>
                <td>RECTOR</td>
            </tr>
            <tr>
                <td class="info-label">Expedido Registro Presupuestal No.:</td>
                <td>{{ $rpNumbers ?: 'N/A' }} &nbsp;&nbsp;&nbsp; de fecha &nbsp;&nbsp;&nbsp; {{ $contract->start_date?->format('d/m/Y') ?? '' }}</td>
            </tr>
        </table>

        {{-- SALUDO --}}
        <div class="text-block" style="border-top: 1px solid #ddd;">
            Cordial Saludo,
        </div>

        {{-- TEXTO DESIGNACIÓN --}}
        <div class="text-block">
            Me permito informar su designación como supervisor(a) de la orden o contrato de la referencia, cuyo objeto es
            <br>
            <span class="bold">{{ $contract->object }}</span>
            <br>
            suscrito con <span class="bold">{{ $supplier->full_name ?? '' }}</span>
        </div>

        <div class="text-block" style="padding-top: 2px;">
            para lo cual remito copia del contrato y [copia del acta de aprobación de póliza].
            Así mismo, me permito recordar sus funciones como supervisor, señaladas en la orden o contrato así:
        </div>

        {{-- FUNCIONES --}}
        <div style="padding: 6px 15px;">
            <ol class="funciones-list" style="list-style-type: lower-alpha;">
                <li>Exigir al contratista la ejecución idónea y oportuna del objeto contratado.</li>
                <li>Buscar el cumplimiento de los fines del presente contrato y de los resultados esperados con la celebración del mismo.</li>
                <li>Exigir al contratista junto con el informe de actividades, los soportes de pago correspondientes a los aportes a los sistemas de pensión, salud y ARL, de acuerdo con lo estipulado por las normas vigentes.</li>
                <li>Vigilar la correcta ejecución del objeto del presente contrato.</li>
                <li>Proteger los derechos de la institución Educativa, del contratista y de los terceros que puedan verse afectados por la ejecución del mismo.</li>
                <li>Remitir oportunamente a la oficina de pagaduría los cumplidos de prestación del servicio a satisfacción, junto con los soportes correspondientes, para efectos del pago respectivo.</li>
                <li>Informar oportunamente a la Rectoría y pagaduría sobre las irregularidades o incumplimientos del(a) contratista en la ejecución de la orden o contrato.</li>
            </ol>
        </div>

        {{-- CORDIALMENTE --}}
        <div class="text-block" style="border-top: 1px solid #ddd;">
            Cordialmente,
        </div>

        {{-- FIRMAS --}}
        <div style="padding: 12px 15px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_display_name }}</div>
                            <div class="sig-role">RECTOR(A)</div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 9px; font-weight: bold; color: #1e3a5f; margin-bottom: 25px;">ACEPTADO:</div>
                        <div class="sig-line">
                            <div class="sig-name">{{ $contract->supervisor ? strtoupper(trim($contract->supervisor->name . ' ' . $contract->supervisor->surname)) : $school->rector_display_name }}</div>
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
