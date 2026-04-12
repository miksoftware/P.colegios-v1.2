<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisición de Necesidades - Convocatoria {{ $convocatoria->formatted_number }}</title>
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
        .school-sub { font-size: 8px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 1.5px; margin-top: 6px; }

        /* Info */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; text-transform: uppercase; width: 130px; color: #1e3a5f; }

        /* Tabla descripción */
        .desc-table { width: 100%; border-collapse: collapse; }
        .desc-table th { background: #1e3a5f; color: #fff; font-size: 9px; text-transform: uppercase; padding: 6px 10px; border: 1px solid #1e3a5f; text-align: center; font-weight: bold; letter-spacing: 0.5px; }
        .desc-table td { padding: 10px; border: 1px solid #ccc; font-size: 10px; text-align: justify; }
        .desc-table .text-right { text-align: right; }
        .desc-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; font-size: 10px; }

        /* Necesidad */
        .necesidad { border-top: 2px solid #1e3a5f; }
        .necesidad-header { background: #1e3a5f; color: #fff; padding: 5px 15px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .necesidad-body { padding: 12px 15px; font-size: 10px; text-align: justify; }

        /* Firmas */
        .firma-section { padding: 15px; }
        .firma-row { display: table; width: 100%; margin-top: 30px; }
        .firma-cell { display: table-cell; width: 50%; vertical-align: bottom; }
        .firma-label { font-weight: bold; font-size: 9px; text-transform: uppercase; color: #1e3a5f; margin-bottom: 30px; }
        .sig-line { border-top: 1px solid #333; width: 220px; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; }

        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }
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
                    <div class="school-sub">{{ $school->municipality ?? '' }}</div>
                    <div class="doc-title">Requisición de Necesidades</div>
                </td>
            </tr>
        </table>

        {{-- ===== DATOS GENERALES ===== --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Fecha Solicitud:</td>
                <td>{{ $convocatoria->start_date?->format('Y/m/d') ?? now()->format('Y/m/d') }}</td>
            </tr>
            <tr>
                <td class="info-label">Solicitante:</td>
                <td>{{ $school->rector_name ?? 'Rector(a)' }}</td>
            </tr>
            <tr>
                <td class="info-label">Dependencia:</td>
                <td>RECTORÍA</td>
            </tr>
            <tr>
                <td class="info-label">A:</td>
                <td>RECTORÍA</td>
            </tr>
        </table>

        {{-- ===== TABLA DESCRIPCIÓN / VALOR ===== --}}
        <table class="desc-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Descripción</th>
                    <th style="width: 30%;">Valor Aproximado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $convocatoria->object }}</td>
                    <td class="text-right">${{ number_format($amount, 2, ',', '.') }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-right" style="padding-right: 15px;">TOTAL</td>
                    <td class="text-right">${{ number_format($amount, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- ===== NECESIDAD ===== --}}
        <div class="necesidad">
            <div class="necesidad-header">Necesidad que se pretende satisfacer con esta solicitud</div>
            <div class="necesidad-body">
                {{ $convocatoria->object }}
                @if($convocatoria->justification)
                    , {{ $convocatoria->justification }}
                @endif
            </div>
        </div>

        {{-- ===== FIRMAS ===== --}}
        <div class="firma-section">
            <div class="firma-row">
                <div class="firma-cell">
                    <div class="firma-label">Firma Solicitante</div>
                    <div style="margin-top: 35px;">
                        <div class="sig-line">&nbsp;</div>
                    </div>
                </div>
            </div>

            <div class="firma-row" style="margin-top: 20px;">
                <div class="firma-cell">
                    <div class="firma-label">Aprobado Firma y Autorizaciones</div>
                    <div style="margin-top: 35px;">
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                            <div class="sig-role">ORDENADOR DEL GASTO</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
