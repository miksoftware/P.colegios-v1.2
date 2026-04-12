<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado Plan de Compras - Convocatoria {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }

        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 10px 15px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #1e3a5f; }
        .header-center { text-align: center; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .school-dane { font-size: 8px; color: #555; margin-top: 1px; }
        .school-muni { font-size: 9px; color: #444; margin-top: 1px; }
        .doc-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.8px; margin-top: 8px; }

        /* Texto */
        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .text-block-border { border-bottom: 1px solid #ddd; }

        /* Tabla rubros */
        .rubro-table { width: 100%; border-collapse: collapse; }
        .rubro-table th { background: #1e3a5f; color: #fff; font-size: 8px; text-transform: uppercase; padding: 6px 8px; border: 1px solid #1e3a5f; text-align: center; font-weight: bold; letter-spacing: 0.5px; }
        .rubro-table td { padding: 8px; border: 1px solid #ccc; font-size: 9px; }
        .rubro-table .text-right { text-align: right; }
        .rubro-table .text-center { text-align: center; }
        .rubro-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        /* Acuerdo */
        .acuerdo-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .acuerdo-table td { padding: 5px 8px; border: 1px solid #ccc; font-size: 10px; vertical-align: middle; }
        .acuerdo-label { font-weight: bold; background: #e8edf3; color: #1e3a5f; width: 30%; }

        /* Firmas */
        .firma-section { padding: 15px; }
        .firma-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 15px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 220px; margin: 0 auto; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; }

        /* Fecha final */
        .date-final { padding: 10px 15px; font-size: 10px; border-top: 1px solid #ccc; }

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
                        <div class="school-dane">DANE: {{ $school->dane_code }}</div>
                    @endif
                    <div class="school-muni">{{ $school->municipality ?? '' }}</div>
                    <div class="doc-title">Certificado de Bienes y Servicios Incorporados en el Plan de Compras</div>
                </td>
            </tr>
        </table>

        {{-- ===== TEXTO INTRODUCTORIO ===== --}}
        <div class="text-block text-block-border">
            Una vez realizada la verificación de la solicitud realizada por:
            <br>
            <span class="bold" style="margin-left: 40px;">{{ $school->rector_name ?? 'Rector(a)' }}</span>, Rector(a) del
            <br>
            <span class="bold" style="margin-left: 40px;">{{ $school->name }}</span>, se pudo constatar que:
        </div>

        {{-- ===== TABLA RUBROS ===== --}}
        <table class="rubro-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Rubro</th>
                    <th style="width: 55%;">Bien o Servicio</th>
                    <th style="width: 25%;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="text-center">{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            @if(count($rows) > 1)
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right" style="padding-right: 15px;">TOTAL</td>
                    <td class="text-right">${{ number_format($totalAmount, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>

        {{-- ===== TEXTO CERTIFICACIÓN ===== --}}
        <div class="text-block text-block-border">
            Se encuentra incluido en el plan anual de adquisiciones de bienes y servicios de la Institución Educativa.
        </div>

        {{-- ===== ACUERDO ===== --}}
        <div style="padding: 10px 15px;">
            <table class="acuerdo-table">
                <tr>
                    <td class="acuerdo-label">ACUERDO No.</td>
                    <td>{{ $school->budget_agreement_number ?? 'N/A' }}</td>
                    <td class="acuerdo-label">Aprobado por el Consejo Directivo del</td>
                    <td>{{ $school->name }}</td>
                </tr>
            </table>
            <p style="margin-top: 8px; font-size: 10px; text-align: justify;">
                de la Institución Educativa y sus modificaciones durante la vigencia {{ $convocatoria->fiscal_year }}, las cuales fueron puestas para uso del mismo.
            </p>
        </div>

        {{-- ===== FECHA ===== --}}
        <div class="date-final">
            Se firma la presente en {{ $school->municipality ?? 'N/A' }}, SANTANDER, el {{ $convocatoria->start_date?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}.
        </div>

        {{-- ===== FIRMAS ===== --}}
        <div class="firma-section">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                            <div class="sig-role">RECTOR(A)</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->pagador_name ?? 'Auxiliar Administrativo' }}</div>
                            <div class="sig-role">AUXILIAR ADMINISTRATIVO</div>
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
