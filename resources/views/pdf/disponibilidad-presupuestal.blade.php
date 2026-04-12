<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Disponibilidad Presupuestal - Convocatoria {{ $convocatoria->formatted_number }}</title>
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
        .rubro-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .rubro-table th { background: #e8edf3; font-size: 8px; text-transform: uppercase; padding: 5px 8px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; }
        .rubro-table td { padding: 5px 8px; border: 1px solid #ccc; font-size: 9px; }
        .rubro-table .text-right { text-align: right; }
        .rubro-table .text-center { text-align: center; }
        .rubro-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; }

        /* Monto */
        .amount-big { font-size: 12px; font-weight: bold; color: #1e3a5f; }
        .amount-words { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #333; margin-top: 2px; }

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
                    <div class="doc-title">Solicitud de Disponibilidad Presupuestal</div>
                </td>
            </tr>
        </table>

        {{-- ===== PARA / FECHA ===== --}}
        <table class="info-table">
            <tr>
                <td class="info-label">PARA:</td>
                <td>{{ $school->rector_name ?? 'Rector(a)' }} - RECTOR(A), ORDENADOR(A) DEL GASTO</td>
            </tr>
            <tr>
                <td class="info-label">FECHA:</td>
                <td>{{ $convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
            </tr>
        </table>

        {{-- ===== TEXTO INTRODUCTORIO ===== --}}
        <div style="padding: 12px 15px; font-size: 10px; text-align: justify; border-top: 1px solid #ccc;">
            En atención al plan de compras previsto para la vigencia {{ $convocatoria->fiscal_year }}, me permito solicitar se expida un Certificado de Disponibilidad Presupuestal, de acuerdo con la siguiente información:
        </div>

        {{-- ===== 1. OBJETO ===== --}}
        <div class="section">
            <div class="section-header">1. Objeto</div>
            <div class="section-body">
                {{ $convocatoria->object }}
            </div>
        </div>

        {{-- ===== 2. VALOR Y RUBRO PRESUPUESTAL ===== --}}
        <div class="section">
            <div class="section-header">2. Valor y Rubro Presupuestal</div>
            <div class="section-body">
                @if(count($rubroRows) > 0)
                    <table class="rubro-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Valor</th>
                                <th style="width: 25%;">Código Rubro</th>
                                <th style="width: 55%;">Rubro</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rubroRows as $row)
                                <tr>
                                    <td class="text-right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if(count($rubroRows) > 1)
                        <tfoot>
                            <tr>
                                <td class="text-right">${{ number_format($totalAmount, 2, ',', '.') }}</td>
                                <td colspan="2" style="text-align: left; padding-left: 15px;">TOTAL</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                @endif

                <div style="margin-top: 8px;">
                    <span class="amount-big">${{ number_format($totalAmount, 2, ',', '.') }}</span>
                </div>
                <div class="amount-words">{{ $amountInWords }}</div>
            </div>
        </div>

        {{-- ===== 3. NECESIDAD ===== --}}
        <div class="section">
            <div class="section-header">3. Necesidad</div>
            <div class="section-body">
                {{ $convocatoria->object }}
                @if($convocatoria->justification)
                    , {{ $convocatoria->justification }}
                @endif
            </div>
        </div>

        {{-- ===== FIRMAS ===== --}}
        <div class="signatures">
            <table class="sig-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                            <div class="sig-role">ORDENADOR(A) DEL GASTO</div>
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
