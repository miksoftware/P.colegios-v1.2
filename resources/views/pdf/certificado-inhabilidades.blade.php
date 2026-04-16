<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Inhabilidades - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #222; line-height: 1.7; }
        .container { padding: 30px 40px; }

        .header-line { display: table; width: 100%; margin-bottom: 40px; }
        .header-left { display: table-cell; font-size: 10px; color: #444; }
        .header-right { display: table-cell; text-align: right; font-size: 10px; color: #444; }

        .destinatario { margin-bottom: 30px; font-size: 11px; }
        .destinatario-label { font-size: 10px; color: #555; }
        .destinatario-name { font-weight: bold; font-size: 12px; text-transform: uppercase; }

        .asunto { margin-bottom: 25px; padding: 8px 0; }
        .asunto-label { font-weight: bold; color: #1e3a5f; }

        .body-text { font-size: 11px; text-align: justify; line-height: 1.8; margin-bottom: 40px; }

        .firma-section { margin-top: 50px; }
        .firma-label { font-size: 10px; color: #555; margin-bottom: 5px; }
        .sig-line { border-top: 1px solid #333; width: 280px; padding-top: 5px; margin-top: 40px; }
        .sig-name { font-size: 11px; }
        .sig-prefix { font-size: 10px; color: #555; }
        .sig-detail { font-size: 10px; color: #444; }

        .footer { margin-top: 30px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">

    {{-- LUGAR Y FECHA --}}
    <div class="header-line">
        <div class="header-left">{{ $school->municipality ?? '' }}</div>
        <div class="header-right">{{ $contract->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</div>
    </div>

    {{-- DESTINATARIO --}}
    <div class="destinatario">
        <div class="destinatario-label">Señores</div>
        <div class="destinatario-name">{{ $school->name }}</div>
    </div>

    {{-- ASUNTO --}}
    <div class="asunto">
        <span class="asunto-label">Asunto:</span>
        Certificado de Inhabilidades e Incompatibilidades para contratar
    </div>

    {{-- CUERPO --}}
    <div class="body-text">
        Manifiesto bajo la gravedad de juramento que no me encuentro incurso en ninguna de las causales de inhabilidad e incompatibilidad legal conforme a lo establecido en los artículos 8 de la Ley 80 de 1993, 44 y 86 de la ley 142 de 1994 y demás normas sobre la materia y que tampoco me hallo o nos hallamos incursos en ninguno de los eventos de prohibiciones especiales para contratar.
    </div>

    {{-- FIRMA --}}
    <div class="firma-section">
        <div class="firma-label">Firma:</div>
        <div class="sig-line">
            <div class="sig-prefix">Nombre:</div>
            <div class="sig-name" style="font-weight: bold;">{{ $supplier->full_name ?? 'N/A' }}</div>
            <div class="sig-detail" style="margin-top: 4px;">C.C.: {{ $supplier->document_number ?? '' }}</div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
