<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carta de Aceptación Oferta - {{ $convocatoria->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #222; line-height: 1.6; }
        .container { padding: 15px 25px; }
        .doc-border { border: 2px solid #1e3a5f; padding: 0; }
        .bold { font-weight: bold; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; }
        .header-table td { vertical-align: middle; padding: 8px 12px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #1e3a5f; }
        .header-center { text-align: center; }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; }
        .school-sub { font-size: 7.5px; color: #555; margin-top: 2px; }

        /* Título del documento */
        .doc-title-block { text-align: center; padding: 12px 15px; border-bottom: 1px solid #ddd; }
        .doc-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 1px; text-decoration: underline; }

        /* Campos del documento */
        .field-row { display: table; width: 100%; padding: 5px 15px; border-bottom: 1px solid #eee; }
        .field-label { display: table-cell; font-weight: bold; width: 180px; vertical-align: top; font-size: 9px; text-transform: uppercase; }
        .field-value { display: table-cell; font-size: 9px; vertical-align: top; }

        /* Texto con justificación */
        .text-block { padding: 8px 15px; font-size: 9px; text-align: justify; }

        /* Sección */
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 9px; text-decoration: underline; }

        /* Garantías */
        .garantias-block { padding: 8px 15px; font-size: 9px; text-align: justify; border-top: 1px solid #ddd; }

        /* Firma */
        .sig-line { border-top: 1px solid #333; width: 240px; padding-top: 3px; margin-top: 45px; }
        .sig-name { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .sig-role { font-size: 8px; color: #444; }

        .footer { margin-top: 8px; text-align: center; font-size: 6px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- ===== ENCABEZADO / MEMBRETE ===== --}}
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
                    @if($school->address)
                        <div class="school-sub">{{ $school->address }}</div>
                    @endif
                    @if($school->phone)
                        <div class="school-sub">Tel: {{ $school->phone }}</div>
                    @endif
                    <div class="school-sub">
                        @if($school->nit) NIT {{ $school->nit }} @endif
                        @if($school->dane_code) &bull; Dane: {{ $school->dane_code }} @endif
                    </div>
                    @if($school->website || $school->email)
                        <div class="school-sub">
                            @if($school->website) {{ $school->website }} @endif
                            @if($school->website && $school->email) &nbsp;&bull;&nbsp; @endif
                            @if($school->email) {{ $school->email }} @endif
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- ===== TÍTULO ===== --}}
        <div class="doc-title-block">
            <div class="doc-title">Carta de Aceptación Oferta</div>
        </div>

        {{-- ===== FECHA ===== --}}
        <div class="field-row">
            <div class="field-label">Fecha:</div>
            <div class="field-value">
                {{ ($convocatoria->evaluation_date ?? $convocatoria->start_date)?->translatedFormat('d \\d\\e F \\d\\e Y') ?? now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
            </div>
        </div>

        {{-- ===== OBJETO ===== --}}
        <div class="field-row">
            <div class="field-label">Objeto:</div>
            <div class="field-value">{{ $convocatoria->object }}</div>
        </div>

        {{-- ===== PROPONENTE ===== --}}
        <div class="field-row" style="border-bottom: 1px solid #ccc;">
            <div class="field-label">Proponente:</div>
            <div class="field-value bold">{{ strtoupper($supplier->full_name ?? 'N/A') }}</div>
        </div>

        {{-- ===== CUERPO PRINCIPAL ===== --}}
        <div class="text-block" style="padding-top: 10px;">
            POR MEDIO DE LA PRESENTE, LA <span class="bold">{{ strtoupper($school->name) }}</span>
            @if($school->municipality) DE <span class="bold">{{ strtoupper($school->municipality) }}</span> @endif
            ACEPTA LA PROPUESTA POR USTED PRESENTADA, EN LAS SIGUIENTES CONDICIONES:
        </div>

        {{-- ===== VALOR ===== --}}
        <div class="field-row" style="padding-top: 8px;">
            <div class="field-label">Valor:</div>
            <div class="field-value">
                {{ ucfirst(strtolower($amountInWords)) }} ${{ number_format($amount, 0, ',', '.') }} IVA INCLUIDO
            </div>
        </div>

        {{-- ===== PLAZO DE EJECUCIÓN ===== --}}
        <div class="field-row">
            <div class="field-label">Plazo de Ejecución:</div>
            <div class="field-value">
                @if($durationDays)
                    {{ $durationDays }} días contados a partir de la suscripción del contrato.
                @else
                    A convenir contados a partir de la suscripción del contrato.
                @endif
            </div>
        </div>

        {{-- ===== LUGAR DE EJECUCIÓN ===== --}}
        <div class="field-row">
            <div class="field-label">Lugar de Ejecución:</div>
            <div class="field-value bold">
                {{ strtoupper($contract?->execution_place ?? $school->name) }}
            </div>
        </div>

        {{-- ===== FORMA DE PAGO ===== --}}
        <div class="field-row" style="border-bottom: 1px solid #ccc;">
            <div class="field-label">Forma de Pago:</div>
            <div class="field-value">{{ $contract?->payment_method_name ?? 'A CONVENIR' }}</div>
        </div>

        {{-- ===== ELEMENTOS O SERVICIOS ===== --}}
        <div class="text-block" style="padding-top: 10px;">
            <span class="section-title">Elementos o Servicios a Suministrar o Realizar:</span><br>
            Los contenidos en el documento anexo según propuesta presentada.
        </div>

        {{-- ===== GARANTÍAS ===== --}}
        <div class="garantias-block">
            <span class="section-title">Garantías:</span><br><br>
            De conformidad con el inciso quinto del artículo 7 de la Ley 1150 de 2007, el artículo 2.2.1.2.1.5.4 del
            decreto 1082 de 2015, como quiera que se trata de un proceso cuyo valor NO supera el diez por ciento (10%)
            de la menor cuantía establecida para la entidad, teniendo en cuenta la naturaleza del objeto a contratar y
            su forma de pago, se determinó por parte de la INSTITUCIÓN EDUCATIVA, no exigir al contratista la garantía
            única que ampare los riesgos derivados de la ejecución del contrato. Sin embargo, el contratista deberá
            garantizar la garantía personal y comercial establecida por la Superintendencia de Industria y Comercio.
        </div>

        {{-- ===== CORDIALMENTE Y FIRMA ===== --}}
        <div class="text-block" style="padding-top: 16px; border-top: 1px solid #ddd;">
            Cordialmente,
        </div>
        <div style="padding: 10px 15px 20px;">
            <div class="sig-line">
                <div class="sig-name">{{ $school->rector_display_name }}</div>
                <div class="sig-role">Rector(a)</div>
            </div>
        </div>

    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
