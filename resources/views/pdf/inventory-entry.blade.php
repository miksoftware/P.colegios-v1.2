<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Entrada {{ str_pad($entry->consecutive, 4, '0', STR_PAD_LEFT) }}</title>
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
            margin-bottom: 15px;
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
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .info-table td {
            padding: 3px;
            vertical-align: top;
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
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 300px;
            padding-top: 5px;
            font-weight: bold;
            text-align: center;
            margin: 0 auto;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold;
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
                <div class="doc-title">COMPROBANTE DE ENTRADA</div>
                <div>{{ strtoupper($school->name) }}</div>
            </td>
            <td width="20%" class="doc-meta">
                <div>CÓDIGO: AP-AI-RG-141</div>
                <div>VERSIÓN: 3</div>
                <div>FECHA: {{ $entry->date ? $entry->date->format('d/m/Y') : '' }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>No. DE COMPROBANTE:</strong> {{ str_pad($entry->consecutive, 4, '0', STR_PAD_LEFT) }}
            </td>
            <td width="50%">
                <strong>FECHA:</strong> {{ $entry->date ? $entry->date->format('d/m/Y') : 'N/A' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>PROVEEDOR:</strong> {{ mb_strtoupper($entry->supplier->first_surname ?? 'N/A') }}
            </td>
            <td>
                <strong>CEDULA O NIT:</strong> {{ $entry->supplier->document_number ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>CENTRO DE COSTO:</strong> INSTITUCIONAL
            </td>
            <td>
                <strong>SECRETARIA:</strong> EDUCACIÓN
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>DEPENDENCIA:</strong> {{ mb_strtoupper($school->name) }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>No. DE CONTRATO:</strong> N/A
            </td>
            <td>
                <strong>FACTURA:</strong> {{ $entry->invoice_number ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>CONCEPTO / OBSERVACIONES:</strong> {{ $entry->observations ?? 'SIN OBSERVACIONES' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="15%">CÓDIGO CONTABLE</th>
                <th width="15%">NOMBRE CUENTA</th>
                <th width="30%">DESCRIPCIÓN ITEM</th>
                <th width="10%">MEDIDA</th>
                <th width="5%">CANT</th>
                <th width="10%">V. UNITARIO</th>
                <th width="10%">V. TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->account->code ?? 'N/A' }}</td>
                    <td>{{ $item->account->name ?? 'N/A' }}</td>
                    <td>{{ $item->name }} {{ $item->current_tag ? ' (Placa: ' . $item->current_tag . ')' : '' }}</td>
                    <td class="text-center">UNIDAD</td>
                    <td class="text-center">1</td>
                    <td class="text-right">${{ number_format($item->initial_value, 2) }}</td>
                    <td class="text-right">${{ number_format($item->initial_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-right font-bold" style="padding-right: 10px;">TOTAL:</td>
                <td class="text-right font-bold">${{ number_format($entry->total_value, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="signature-line">
            (FIRMA DEL FUNCIONARIO DE ALMACÉN QUE REALIZA LA ENTRADA)
        </div>
    </div>

</body>
</html>
