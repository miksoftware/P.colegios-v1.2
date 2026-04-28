<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución de Pago No. {{ $po->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #222; line-height: 1.6; }
        .container { padding: 25px 35px; }
        .doc-border { border: 2px solid #1e3a5f; }
        .header { text-align: center; padding: 12px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-muni { font-size: 9px; color: #444; }
        .doc-title { font-size: 12px; font-weight: bold; color: #1e3a5f; margin-top: 10px; }
        .doc-subtitle { font-size: 10px; color: #555; margin-top: 2px; font-style: italic; }
        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; color: #1e3a5f; padding: 10px 15px; text-transform: uppercase; letter-spacing: 1px; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 160px; text-transform: uppercase; font-size: 9px; }
        .concepto-block { padding: 8px 15px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .concepto-label { font-weight: bold; color: #1e3a5f; text-transform: uppercase; font-size: 9px; margin-bottom: 4px; }
        .concepto-value { padding: 4px 0; border-bottom: 1px dotted #ccc; min-height: 20px; }
        .resuelve-box { margin: 8px 15px; padding: 10px 15px; border: 1px solid #ccc; }
        .lugar-fecha { padding: 10px 15px; font-size: 10px; }
        .firma-section { padding: 15px; margin-top: 30px; }
        .sig-line { border-top: 1px solid #333; width: 280px; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; font-style: italic; }
        .footer { margin-top: 15px; text-align: center; font-size: 7px; color: #999; }
        .monto-row { display: flex; justify-content: flex-end; padding: 4px 15px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
<div class="doc-border">

    {{-- HEADER --}}
    <div class="header">
        <div class="school-name">{{ $school->name }}</div>
        <div class="school-muni">{{ $school->municipality ?? '' }}</div>
        <div class="doc-title">RESOLUCIÓN DE PAGO No. {{ $po->formatted_number }}</div>
        <div class="doc-subtitle">Por la cual se reconoce una Obligación,</div>
    </div>

    {{-- TEXTO LEGAL --}}
    <div class="text-block">
        El RECTOR(A), en uso de sus atribuciones legales y en especial las que confiere la ley 115 de 1994 y su decreto
        reglamentario No 1857 de 1994 del Ministerio de Educacion Nacional, la ley 80 de 1993, Ley 715/2001:
    </div>

    {{-- CONSIDERANDO --}}
    <div class="section-title">Considerando:</div>

    <table class="info-table">
        <tr>
            <td class="info-label">Que se necesita cancelar a:</td>
            <td class="bold">{{ $supplier?->full_name ?? 'DIAN' }}</td>
        </tr>
    </table>

    {{-- POR CONCEPTO DE (bloque independiente con espacio para texto largo) --}}
    <div class="concepto-block">
        <div class="concepto-label">Por concepto de:</div>
        <div class="concepto-value">{{ $po->description ?? 'PAGO RETENCIONES' }}</div>
        <div class="concepto-value">&nbsp;</div>
        <div class="concepto-value">&nbsp;</div>
    </div>

    {{-- RESUELVE --}}
    <div class="section-title">Resuelve:</div>

    <div class="resuelve-box">
        <p style="font-size:10px;text-align:justify;">
            <span class="bold">Artículo Único:</span> Ordenar al Pagador (o quien Haga las veces) del Colegio para que con cargo a:
            <br>(Rubro Presupuestal)
        </p>
    </div>

    {{-- CANCELE A / LA SUMA DE --}}
    <table class="info-table" style="margin-top:6px;">
        <tr>
            <td class="info-label">Cancele a:</td>
            <td class="bold">{{ $supplier?->full_name ?? 'DIAN' }}</td>
        </tr>
        <tr>
            <td class="info-label">La suma de:</td>
            <td>
                <span class="bold">${{ number_format($amount, 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    {{-- Monto alineado a la derecha (segundo bloque) --}}
    <div style="text-align:right;padding:4px 15px;font-size:10px;font-weight:bold;border-bottom:1px solid #ddd;">
        ${{ number_format($amount, 2, ',', '.') }}
    </div>

    {{-- COMUNÍQUESE Y CÚMPLASE --}}
    <div class="text-block" style="border-top:1px solid #ddd;padding-top:10px;">
        <span class="bold" style="font-size:11px;">COMUNÍQUESE Y CÚMPLASE</span>
    </div>

    {{-- LUGAR Y FECHA --}}
    <div class="lugar-fecha">
        {{ $school->municipality ?? '' }} &nbsp;&nbsp;&nbsp; {{ $po->payment_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
    </div>

    {{-- FIRMA --}}
    <div class="firma-section">
        <div style="font-size:9px;font-style:italic;color:#555;margin-bottom:30px;">Firma RECTOR(A) (ORDENADOR DEL PAGO)</div>
        <div class="sig-line">
            <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
        </div>
    </div>

</div>

<div class="footer">
    Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
</div>
</div>
</body>
</html>