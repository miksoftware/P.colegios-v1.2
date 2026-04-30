<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución de Baja {{ $discharge->resolution_number ?? $discharge->consecutive }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .logo-img {
            max-width: 80px;
            max-height: 80px;
        }
        .doc-title {
            font-weight: bold;
            font-size: 13px;
        }
        .doc-meta {
            font-size: 10px;
        }
        .resolution-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .resolution-subtitle {
            text-align: justify;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .content {
            margin-bottom: 20px;
            text-align: justify;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .signatures {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            padding-top: 5px;
            font-weight: bold;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="20%">
                @if($school->logo_absolute_path && file_exists($school->logo_absolute_path))
                    <img src="{{ $school->logo_absolute_path }}" class="logo-img" alt="Logo">
                @else
                    [LOGO INSTITUCIÓN]
                @endif
            </td>
            <td width="60%">
                <div class="doc-title">RESOLUCIÓN DE BAJA ADMINISTRATIVA</div>
                <div>{{ strtoupper($school->name) }}</div>
            </td>
            <td width="20%" class="doc-meta">
                <div>CÓDIGO: AP-AI-RG-153</div>
                <div>FECHA: {{ $discharge->date ? $discharge->date->format('d/m/Y') : '' }}</div>
            </td>
        </tr>
    </table>

    @php
        $date = $discharge->date;
        $day = $date ? $date->format('d') : '___';
        $month = $date ? mb_strtoupper($date->translatedFormat('F')) : '_________';
        $year = $date ? $date->format('Y') : '____';
        $resNumber = $discharge->resolution_number ?? str_pad($discharge->consecutive, 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="resolution-header">
        RESOLUCION No. {{ $resNumber }} del mes de {{ $month }} del día {{ $day }} de {{ $year }}
    </div>

    <div class="resolution-subtitle">
        POR LA CUAL SE AUTORIZA LA BAJA ADMINISTRATIVA Y LA ADJUDICACION EN DONACION O COMODATO DE BIENES REINTEGRADOS POR LAS DIFERENTES DEPENDENCIAS
    </div>

    <div class="content">
        El/La Rector(a) de la {{ $school->name }} en uso de sus facultades legales,
        <br><br>
        <strong>CONSIDERANDO:</strong>
        <br>
        1. Que de acuerdo a la revisión de los bienes e inventarios, se hace necesario tramitar la baja de los mismos debido a su estado o cumplimiento de vida útil.
        <br>
        2. Que la solicitud es procedente de conformidad con la normatividad vigente para el manejo de inventarios.
        @if($discharge->observations)
        <br>
        3. {{ $discharge->observations }}
        @endif
        <br><br>
        <strong>RESUELVE:</strong>
        <br><br>
        <strong>ARTÍCULO PRIMERO:</strong> Autorizar la Baja Administrativa de Bienes identificados y su posterior retiro de los inventarios así:
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="10%">No.</th>
                <th width="50%">DETALLE</th>
                <th width="20%">CALCAMONIA</th>
                <th width="20%">AVALUO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($discharge->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ mb_strtoupper($item->name) }}</td>
                    <td class="text-center">{{ $item->current_tag ?? 'S/P' }}</td>
                    <td class="text-right">${{ number_format($item->initial_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right" style="font-weight: bold; padding-right: 10px;">TOTAL</td>
                <td class="text-right font-bold">${{ number_format($discharge->total_value, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="content">
        <strong>ARTÍCULO SEGUNDO:</strong> De los elementos dados de baja debe suscribirse el acta respectiva o el contrato correspondiente si existe adjudicación, y actualizar el reporte general de inventarios indicando esta resolución.
        <br><br>
        <strong>ARTÍCULO TERCERO:</strong> De la presente resolución enviar copia a los interesados: Contabilidad y Almacén.
        <br><br>
        <strong>NOTIFÍQUESE Y CÚMPLASE</strong>
        <br><br>
        Dado en {{ mb_strtoupper($school->municipality ?? '______________') }}, a los {{ $day }} días del mes de {{ mb_strtolower($month) }} de {{ $year }}.
    </div>

    <div class="signatures">
        <div class="signature-line">
            RECTOR(A) / REPRESENTANTE LEGAL<br>
            {{ mb_strtoupper($school->name) }}
        </div>
    </div>

</body>
</html>
