<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Registro Presupuestal - RP {{ $rpNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        .container { padding: 20px 30px; }
        .doc-border { border: 2px solid #1e3a5f; }

        /* Header */
        .header { text-align: center; padding: 10px 15px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-dane { font-size: 8px; color: #555; }
        .school-nit { font-size: 8px; color: #555; }

        /* Título */
        .title-row { display: table; width: 100%; border-bottom: 2px solid #1e3a5f; }
        .title-cell { display: table-cell; padding: 8px 15px; vertical-align: middle; }
        .title-text { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .title-number { font-size: 14px; font-weight: bold; color: #1e3a5f; text-align: right; }

        /* Texto */
        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        /* Tabla principal */
        .rp-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .rp-table th { background: #1e3a5f; color: #fff; font-size: 8px; text-transform: uppercase; padding: 6px 8px; border: 1px solid #1e3a5f; text-align: center; font-weight: bold; letter-spacing: 0.3px; }
        .rp-table td { padding: 6px 8px; border: 1px solid #ccc; font-size: 9px; vertical-align: top; }
        .rp-table .text-right { text-align: right; }
        .rp-table .text-center { text-align: center; }
        .rp-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; font-size: 10px; }

        /* Info rows */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 15px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #ddd; }
        .info-label { font-weight: bold; color: #1e3a5f; width: 120px; }

        /* Objeto */
        .objeto-section { border-top: 1px solid #ccc; padding: 10px 15px; }
        .objeto-label { font-weight: bold; color: #1e3a5f; }

        /* Lugar y fecha */
        .lugar-fecha { padding: 10px 15px; border-top: 1px solid #ccc; font-size: 10px; }

        /* Firmas */
        .firma-section { padding: 15px; }
        .firma-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 15px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 220px; margin: 0 auto; padding-top: 4px; }
        .sig-name { font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #666; font-style: italic; }

        .footer { margin-top: 12px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            @if($school->logo_absolute_path && file_exists($school->logo_absolute_path))
                <img src="{{ $school->logo_absolute_path }}" style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 4px;" alt="Logo">
                <br>
            @endif
            <div class="school-name">{{ $school->name }}</div>
            @if($school->nit)
                <div class="school-nit">{{ $school->nit }}</div>
            @endif
            @if($school->dane_code)
                <div class="school-dane">{{ $school->dane_code }}</div>
            @endif
        </div>

        {{-- TÍTULO --}}
        <div class="title-row">
            <div class="title-cell">
                <span class="title-text">Certificado de Registro Presupuestal</span>
            </div>
            <div class="title-cell" style="text-align: right;">
                <span class="title-number">No. {{ $rpNumber }}</span>
            </div>
        </div>

        {{-- TEXTO CERTIFICACIÓN --}}
        <div class="text-block">
            EL SUSCRITO ORDENADOR DEL PAGO CERTIFICA:
        </div>
        <div class="text-block" style="padding-top: 0;">
            QUE EL PRESUPUESTO DE GASTOS DE LA VIGENCIA {{ $contract->fiscal_year }} HA QUEDADO REGISTRADO PRESUPUESTALMENTE EL SIGUIENTE COMPROMISO
        </div>

        {{-- TABLA DE RPs --}}
        <div style="padding: 6px 15px;">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">CDP</th>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 27%;">Nombre del Rubro</th>
                        <th style="width: 28%;">Fuente Financiación</th>
                        <th style="width: 22%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rpRows as $row)
                        <tr>
                            <td class="text-center">{{ $row['cdp_number'] }}</td>
                            <td class="text-center">{{ $row['expense_code'] }}</td>
                            <td>{{ $row['expense_name'] }}</td>
                            <td>
                                {{ collect($row['sources'])->pluck('name')->implode(' Y ') }}
                            </td>
                            <td class="text-right bold">${{ number_format($row['total_amount'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if(count($rpRows) > 1)
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right" style="padding-right: 15px;">TOTAL</td>
                        <td class="text-right">${{ number_format($grandTotal, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- BENEFICIARIO --}}
        <div style="padding: 6px 15px; border-top: 1px solid #ccc;">
            <table class="info-table">
                <tr>
                    <td class="info-label">Beneficiario:</td>
                    <td>{{ $supplier->full_name ?? 'N/A' }}</td>
                    <td class="info-label" style="width: 80px;">NIT. / CC:</td>
                    <td>{{ $supplier->document_number ?? '' }}</td>
                    <td class="info-label" style="width: 30px;">DV:</td>
                    <td style="width: 30px;">{{ $supplier->dv ?? '' }}</td>
                </tr>
            </table>
        </div>

        {{-- CONTRATO --}}
        <div style="padding: 6px 15px; border-top: 1px solid #ccc;">
            <table class="info-table">
                <tr>
                    <td class="info-label">Contrato No.:</td>
                    <td>CONTRATO No. {{ $contract->formatted_number }}</td>
                </tr>
            </table>
        </div>

        {{-- OBJETO --}}
        <div class="objeto-section">
            <span class="objeto-label">Objeto:</span>
            {{ $contract->object }}
        </div>

        {{-- OTROSÍ DE ADICIÓN (si aplica) --}}
        @if(!empty($isAddition) && $isAddition)
        <div style="padding: 10px 15px; border-top: 1px solid #ccc; background: #f0fdf4;">
            <div style="margin-bottom: 4px;">
                <span style="font-weight: bold; color: #166534; font-size: 9px; text-transform: uppercase;">Otrosí de Adición de Recursos — Contrato No. {{ $contract->formatted_number }}</span>
            </div>
            <div style="font-size: 9px; color: #333;">
                Este Registro Presupuestal corresponde a una adición de recursos al Contrato No. {{ $contract->formatted_number }} mediante Otrosí
                @if(!empty($otrosiDate))
                    de fecha {{ $otrosiDate->format('d/m/Y') }}
                @endif.
            </div>
            @if(!empty($additionJustification))
            <div style="margin-top: 4px; font-size: 9px;">
                <span style="font-weight: bold; color: #1e3a5f;">Justificación:</span>
                {{ $additionJustification }}
            </div>
            @endif
        </div>
        @endif

        {{-- LUGAR Y FECHA --}}
        <div class="lugar-fecha">
            <span class="bold">Lugar y Fecha de Expedición:</span>
            {{ $school->municipality ?? 'N/A' }} &nbsp;&nbsp;&nbsp; {{ (!empty($isAddition) && !empty($otrosiDate)) ? $otrosiDate->format('d/m/Y') : ($contract->start_date?->format('d/m/Y') ?? now()->format('d/m/Y')) }}
        </div>

        {{-- FIRMAS --}}
        <div class="firma-section">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                            <div class="sig-role">Auxiliar Administrativo</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name"><span style="font-size: 8px; font-weight: normal; color: #666;">Vo. Bo.</span> {{ $school->rector_display_name }}</div>
                            <div class="sig-role">Rector</div>
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
