<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Reintegro {{ $transfer->consecutive }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.4;
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
        .content {
            margin-bottom: 20px;
            text-align: justify;
            font-size: 12px;
            line-height: 1.5;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
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
            width: 100%;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .signatures td {
            width: 50%;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: bold;
        }
        .footer-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 30px;
        }
        .footer-box-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }
        .footer-table {
            width: 100%;
        }
        .footer-table td {
            padding: 5px;
            vertical-align: bottom;
        }
        .footer-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
        }
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
                <div class="doc-title">ACTA DE REINTEGRO / TRASLADO</div>
                <div>{{ strtoupper($school->name) }}</div>
            </td>
            <td width="20%" class="doc-meta">
                <div>CÓDIGO: AP-AI-RG-134</div>
                <div>VERSIÓN: 2</div>
                <div>FECHA: {{ $transfer->transfer_date->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="content">
        <p>
            Yo, <strong>{{ mb_strtoupper($transfer->from_name) }}</strong>, 
            identificado(a) con documento No. <strong>{{ $transfer->from_document ?? '_____________' }}</strong>, 
            perteneciente a <strong>{{ mb_strtoupper($transfer->from_location) }}</strong> 
            hago entrega en calidad de reintegro a <strong>{{ mb_strtoupper($transfer->to_name) }}</strong>, 
            identificado(a) con documento No. <strong>{{ $transfer->to_document ?? '_____________' }}</strong>, 
            perteneciente a <strong>{{ mb_strtoupper($transfer->to_location) }}</strong> 
            de los siguientes bienes:
        </p>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="10%">ÍTEM</th>
                <th width="15%">CÓDIGO CONTABLE</th>
                <th width="40%">DESCRIPCIÓN</th>
                <th width="15%">PLACA / CALCOMANÍA</th>
                <th width="10%">ESTADO</th>
                <th width="10%">VALOR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $index => $itemRecord)
                @php $item = $itemRecord->item; @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ $item->account->code ?? 'N/A' }}</td>
                    <td>{{ $item->name }}</td>
                    <td style="text-align: center;">{{ $item->current_tag ?? 'S/P' }}</td>
                    <td style="text-align: center;">{{ strtoupper($item->state ?? 'BUENO') }}</td>
                    <td style="text-align: right;">${{ number_format($item->initial_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($transfer->observations)
    <div style="margin-bottom: 30px; font-size: 11px;">
        <strong>OBSERVACIONES:</strong> {{ $transfer->observations }}
    </div>
    @endif

    <div style="margin-bottom: 20px;">
        <strong>Municipio y Fecha:</strong> {{ mb_strtoupper($school->municipality ?? '____________') }}, {{ $transfer->transfer_date->format('d/m/Y') }}
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line">
                    QUIEN ENTREGA:<br>
                    {{ mb_strtoupper($transfer->from_name) }}<br>
                    C.C. {{ $transfer->from_document ?? '_____________' }}
                </div>
            </td>
            <td>
                <div class="signature-line">
                    QUIEN RECIBE:<br>
                    {{ mb_strtoupper($transfer->to_name) }}<br>
                    C.C. {{ $transfer->to_document ?? '_____________' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-box">
        <div class="footer-box-title">
            PARA USO EXCLUSIVO DE LA DIRECCIÓN ADMINISTRATIVA
        </div>
        <table class="footer-table">
            <tr>
                <td width="15%">RECIBIDO POR:</td>
                <td width="35%"><span class="footer-line"></span></td>
                <td width="15%">FECHA:</td>
                <td width="35%"><span class="footer-line"></span></td>
            </tr>
            <tr>
                <td>NOMBRE:</td>
                <td><span class="footer-line"></span></td>
                <td>FIRMA:</td>
                <td><span class="footer-line"></span></td>
            </tr>
            <tr>
                <td>No. ACTA:</td>
                <td><strong>{{ $transfer->consecutive }}</strong></td>
                <td>FECHA:</td>
                <td><strong>{{ $transfer->transfer_date->format('d/m/Y') }}</strong></td>
            </tr>
        </table>
    </div>

</body>
</html>
