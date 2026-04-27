<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Disponibilidad Presupuestal - {{ $cdpNumber }}</title>
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

        /* Título CDP */
        .cdp-title-row { display: table; width: 100%; border-bottom: 2px solid #1e3a5f; }
        .cdp-title-cell { display: table-cell; padding: 8px 15px; vertical-align: middle; }
        .cdp-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .cdp-number { font-size: 14px; font-weight: bold; color: #1e3a5f; text-align: right; }

        /* Texto */
        .text-block { padding: 10px 15px; font-size: 10px; text-align: justify; }
        .bold { font-weight: bold; }

        /* Tabla CDP */
        .cdp-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .cdp-table th { background: #1e3a5f; color: #fff; font-size: 8px; text-transform: uppercase; padding: 6px 8px; border: 1px solid #1e3a5f; text-align: center; font-weight: bold; letter-spacing: 0.3px; }
        .cdp-table td { padding: 6px 8px; border: 1px solid #ccc; font-size: 9px; vertical-align: top; }
        .cdp-table .text-right { text-align: right; }
        .cdp-table .text-center { text-align: center; }
        .cdp-table tfoot td { font-weight: bold; background: #e8edf3; border: 1px solid #aaa; font-size: 10px; }

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

        {{-- TÍTULO CDP --}}
        <div class="cdp-title-row">
            <div class="cdp-title-cell">
                <span class="cdp-title">Certificado de Disponibilidad Presupuestal</span>
            </div>
            <div class="cdp-title-cell" style="text-align: right;">
                <span class="cdp-number">No. {{ $cdpNumber }}</span>
            </div>
        </div>

        {{-- TEXTO CERTIFICACIÓN --}}
        <div class="text-block">
            EL SUSCRITO ORDENADOR DEL PAGO CERTIFICA:
        </div>
        <div class="text-block" style="padding-top: 0;">
            Que en el presupuesto de Gastos de la vigencia {{ $convocatoria->fiscal_year }} existe apropiación disponible para atender la presente solicitud así:
        </div>

        {{-- TABLA DE CDPs --}}
        <div style="padding: 6px 15px;">
            <table class="cdp-table">
                <thead>
                    <tr>
                        <th style="width: 18%;">Código</th>
                        <th style="width: 30%;">Nombre del Rubro</th>
                        <th style="width: 30%;">Fuente Financiación</th>
                        <th style="width: 22%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cdpRows as $row)
                        <tr>
                            <td class="text-center">{{ $row['budget_item_code'] }}</td>
                            <td>{{ $row['budget_item_name'] }}</td>
                            <td>
                                {{ collect($row['sources'])->pluck('name')->implode(' Y ') }}
                            </td>
                            <td class="text-right bold">${{ number_format($row['total_amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if(count($cdpRows) > 1)
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right" style="padding-right: 15px;">TOTAL</td>
                        <td class="text-right">${{ number_format($grandTotal, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- OBJETO --}}
        <div class="objeto-section">
            <span class="objeto-label">Objeto:</span>
            {{ $convocatoria->object }}
        </div>

        {{-- OTROSÍ DE ADICIÓN (si aplica) --}}
        @if(!empty($isAddition) && $isAddition)
        <div style="padding: 10px 15px; border-top: 1px solid #ccc; background: #f0fdf4;">
            <div style="margin-bottom: 4px;">
                <span style="font-weight: bold; color: #166534; font-size: 9px; text-transform: uppercase;">Otrosí de Adición de Recursos — Contrato No. {{ $additionContract?->formatted_number ?? '' }}</span>
            </div>
            <div style="font-size: 9px; color: #333;">
                Este Certificado de Disponibilidad Presupuestal corresponde a una adición de recursos al Contrato No. {{ $additionContract?->formatted_number ?? '' }} mediante Otrosí
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
            {{ $school->municipality ?? 'N/A' }} &nbsp;&nbsp;&nbsp; {{ (!empty($isAddition) && !empty($otrosiDate)) ? $otrosiDate->format('d/m/Y') : ($convocatoria->start_date?->format('d/m/Y') ?? now()->format('d/m/Y')) }}
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
