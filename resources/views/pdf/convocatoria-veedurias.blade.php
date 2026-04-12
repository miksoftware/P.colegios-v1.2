<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Convocatoria Veedurías - {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.6; }
        .container { padding: 20px 30px; }

        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 10px 15px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #1e3a5f; }
        .header-center { text-align: center; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 8px; color: #555; margin-top: 1px; }
        .school-muni { font-size: 9px; color: #444; margin-top: 1px; }

        /* Título convocatoria */
        .title-box { border: 2px solid #333; margin: 12px 15px; padding: 10px 15px; text-align: center; }
        .title-main { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 1px; }
        .title-sub { font-size: 10px; font-weight: bold; margin-top: 2px; }
        .title-info { display: table; width: 100%; margin-top: 6px; }
        .title-info-cell { display: table-cell; padding: 2px 5px; font-size: 10px; }
        .title-info-label { font-weight: bold; width: 180px; text-align: right; padding-right: 8px; }

        /* Info rows */
        .info-row { padding: 4px 15px; font-size: 10px; }
        .info-label { font-weight: bold; text-transform: uppercase; color: #1e3a5f; }

        /* Texto */
        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }

        /* Presupuesto */
        .presupuesto-table { width: 100%; border-collapse: collapse; margin: 0; }
        .presupuesto-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; }
        .presupuesto-label { font-weight: bold; text-transform: uppercase; color: #1e3a5f; width: 130px; }
        .amount-value { font-weight: bold; font-size: 12px; }

        /* Firmas */
        .firma-section { padding: 15px; margin-top: 30px; }
        .sig-line { border-top: 1px solid #333; width: 240px; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; }

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
                    @if($school->dane_code)
                        <div class="school-dane">{{ $school->dane_code }}</div>
                    @endif
                    <div class="school-muni">{{ $school->municipality ?? '' }}</div>
                </td>
            </tr>
        </table>

        {{-- ===== TÍTULO ===== --}}
        <div class="title-box">
            <div class="title-main">Convocatoria a Veedurías Ciudadanas</div>
            <div class="title-sub">SEGÚN LEY 715 DEC 1075 DE 2015</div>
            <div style="margin-top: 8px;">
                <div class="title-info">
                    <div class="title-info-cell title-info-label">No.</div>
                    <div class="title-info-cell">{{ $convocatoria->formatted_number }}</div>
                </div>
                <div class="title-info">
                    <div class="title-info-cell title-info-label">FECHA DE PUBLICACIÓN:</div>
                    <div class="title-info-cell">{{ $convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        {{-- ===== TEXTO INTRODUCTORIO ===== --}}
        <div class="text-block">
            La <span class="bold">{{ $school->name }}</span>,
            en cumplimiento al artículo 66 de la ley 80 de 1993 y la ley 850 de 2003, convoca a todos los comités u organizaciones de veedurías ciudadanas que se encuentren legalmente establecidas en este municipio, con el fin de que participen del control social a los procesos adelantados por esta institución.
        </div>

        {{-- ===== MODALIDAD ===== --}}
        <div class="info-row" style="border-top: 1px solid #ddd; padding-top: 8px;">
            <span class="info-label">MODALIDAD:</span> SEGÚN LEY 715 DEC 1075 DE 2015
        </div>

        {{-- ===== OBJETO ===== --}}
        <div class="info-row" style="border-top: 1px solid #ddd; padding-top: 8px; padding-bottom: 8px;">
            <span class="info-label">OBJETO:</span>
            <div style="margin-top: 4px; margin-left: 15px; text-align: justify;">
                {{ $convocatoria->object }}
            </div>
        </div>

        {{-- ===== PRESUPUESTO OFICIAL ===== --}}
        <div style="border-top: 1px solid #ddd; padding: 8px 15px;">
            <table class="presupuesto-table">
                <tr>
                    <td class="presupuesto-label" style="vertical-align: top;">PRESUPUESTO OFICIAL:</td>
                    <td>
                        {{ $amountInWords }}
                        <div style="margin-top: 4px;">
                            <span class="amount-value">$ {{ number_format($amount, 2, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== LUGAR DE CONSULTA ===== --}}
        <div style="border-top: 1px solid #ddd; padding: 8px 15px;">
            <table class="presupuesto-table">
                <tr>
                    <td class="presupuesto-label" style="vertical-align: top;">LUGAR DE CONSULTA:</td>
                    <td>
                        La documentación relacionada con el presente <span class="bold">SEGÚN LEY 715 DEC 1075 DE 2015</span>
                        puede ser consultada en la secretaría de la Institución Educativa ubicada en la
                        <br>
                        {{ $school->address ?? '' }}, a partir del {{ $convocatoria->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? '' }}.
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== OBSERVACIONES ===== --}}
        @if($school->email)
        <div class="text-block" style="border-top: 1px solid #ddd;">
            Así mismo las observaciones y sugerencias pueden dirigirse al correo
            <br>
            <span class="bold">{{ $school->email }}</span>
        </div>
        @endif

        {{-- ===== LUGAR Y FECHA ===== --}}
        <div style="padding: 10px 15px; border-top: 1px solid #ddd;">
            <span class="bold">{{ $school->municipality ?? 'N/A' }}</span>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="bold">{{ $convocatoria->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</span>
        </div>

        {{-- ===== FIRMA ===== --}}
        <div class="firma-section">
            <div class="sig-line">
                <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                <div class="sig-role">RECTOR(A)</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
