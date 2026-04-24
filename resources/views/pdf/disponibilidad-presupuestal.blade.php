<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Certificado de Disponibilidad Presupuestal - Convocatoria {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }

        /* Border principal */
        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 10px 15px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #1e3a5f; }
        .header-center { text-align: center; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .school-sub { font-size: 8px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 1.5px; margin-top: 6px; }

        /* Info rows */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; text-transform: uppercase; width: 80px; color: #1e3a5f; }

        /* Secciones */
        .section { border-top: 1px solid #ccc; }
        .section-header { background: #1e3a5f; color: #fff; padding: 5px 15px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-body { padding: 12px 15px; font-size: 10px; text-align: justify; }

        /* Tabla de rubros */
        .rubro-row { display: table; width: 100%; margin-top: 4px; }
        .rubro-valor { display: table-cell; width: 25%; font-weight: bold; padding: 4px 0; }
        .rubro-nombre { display: table-cell; width: 75%; padding: 4px 0; }

        /* Firmas */
        .signatures { width: 100%; margin-top: 60px; padding: 0 15px 20px; }
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-table td { text-align: center; vertical-align: bottom; padding: 0 20px; }
        .sig-line { border-top: 1px solid #333; width: 240px; margin: 0 auto; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; }

        /* Footer */
        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }

        .bold { font-weight: bold; }
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
                    <div class="doc-title">Solicitud de Certificado de<br>Disponibilidad Presupuestal</div>
                </td>
            </tr>
        </table>

        {{-- ===== PARA / DE / FECHA ===== --}}
        <table class="info-table">
            <tr>
                <td class="info-label">PARA:</td>
                <td>ÁREA DE TESORERÍA Y/O PAGADURÍA</td>
            </tr>
            <tr>
                <td class="info-label">DE:</td>
                <td>RECTORÍA (ORDENADOR DEL GASTO)</td>
            </tr>
            <tr>
                <td class="info-label">FECHA:</td>
                <td>{{ $convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
            </tr>
        </table>

        {{-- ===== TEXTO INTRODUCTORIO ===== --}}
        <div style="padding: 12px 15px; font-size: 10px; text-align: justify; border-top: 1px solid #ccc;">
            En atención al plan de compras previsto para la vigencia {{ $convocatoria->fiscal_year }}, me permito solicitarle expedir un Certificado de Disponibilidad Presupuestal, de acuerdo con la siguiente información.
        </div>

        {{-- ===== 1. OBJETO ===== --}}
        <div class="section">
            <div class="section-header">1.- Objeto:</div>
            <div class="section-body" style="text-align: center; padding: 20px 15px;">
                {{ $convocatoria->object }}
            </div>
        </div>

        {{-- ===== 2. VALOR Y RUBRO PRESUPUESTAL ===== --}}
        <div class="section">
            <div class="section-header">2.- Valor y Rubro Presupuestal:</div>
            <div class="section-body">
                @if(count($rubroRows) > 0)
                    @foreach($rubroRows as $row)
                        <div class="rubro-row">
                            <div class="rubro-valor">${{ number_format($row['amount'], 2, ',', '.') }}</div>
                            <div style="display: table-cell; width: 50%; padding: 4px 0;">{{ $row['name'] }}</div>
                            <div style="display: table-cell; width: 25%; padding: 4px 0; font-size: 9px; color: #555;">{{ $row['funding_source'] }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- ===== 3. NECESIDAD ===== --}}
        <div class="section">
            <div class="section-header">3.- Necesidad:</div>
            <div class="section-body" style="text-align: center; padding: 20px 15px;">
                {{ $convocatoria->justification ?? $convocatoria->object }}
            </div>
        </div>

        {{-- ===== FIRMAS ===== --}}
        <div class="signatures">
            <table class="sig-table">
                <tr>
                    <td style="text-align: left;">
                        <div class="sig-line" style="margin: 0;">
                            <div class="sig-name">{{ $school->ordenador_gasto_display_name }}</div>
                            <div class="sig-role">ORDENADOR DEL GASTO</div>
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
