<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Ruta - Contrato {{ $contract->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #222; line-height: 1.3; }
        .container { padding: 10px 15px; }
        .doc-border { border: 2px solid #1e3a5f; }

        .header { text-align: center; padding: 6px 8px; border-bottom: 2px solid #1e3a5f; }
        .school-name { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; font-style: italic; }
        .school-sub { font-size: 7px; color: #555; }

        .top-info { display: table; width: 100%; border-bottom: 1px solid #ccc; }
        .top-left { display: table-cell; width: 55%; padding: 5px 8px; vertical-align: top; font-size: 7.5px; }
        .top-right { display: table-cell; width: 45%; padding: 5px 8px; vertical-align: top; font-size: 8px; }
        .top-row { margin-bottom: 2px; }
        .top-label { font-weight: bold; color: #1e3a5f; }

        .objeto-box { padding: 5px 8px; border-bottom: 1px solid #ccc; font-size: 8px; }
        .objeto-label { font-weight: bold; color: #1e3a5f; }

        .checklist { width: 100%; border-collapse: collapse; }
        .checklist th { background: #e8edf3; font-size: 7px; padding: 3px 4px; border: 1px solid #aaa; text-align: center; font-weight: bold; color: #1e3a5f; text-transform: uppercase; }
        .checklist td { padding: 2px 4px; border: 1px solid #ccc; font-size: 7.5px; }
        .checklist .num { width: 4%; text-align: center; }
        .checklist .act { width: 42%; }
        .checklist .fol { width: 6%; text-align: center; }
        .checklist .si { width: 5%; text-align: center; }
        .checklist .no { width: 5%; text-align: center; }
        .checklist .fecha { width: 14%; text-align: center; }
        .checklist .obs { width: 24%; }
        .checklist .section-row td { background: #d4edda; font-weight: bold; font-size: 7.5px; color: #155724; }

        .firma-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .firma-table td { text-align: center; vertical-align: bottom; padding: 0 10px; width: 50%; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 2px; }
        .sig-name { font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .sig-role { font-size: 6.5px; color: #666; }

        .footer { margin-top: 4px; text-align: center; font-size: 5.5px; color: #999; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            @if($school->dane_code)<div class="school-sub">{{ $school->dane_code }}</div>@endif
            <div class="school-sub">{{ $school->municipality ?? '' }}</div>
        </div>

        {{-- INFO SUPERIOR --}}
        <div class="top-info">
            <div class="top-left">
                <div class="top-row"><span class="top-label">FUENTE DE FINANCIACIÓN:</span></div>
                <div class="top-row">{{ $fundingSourceText }}</div>
                <div class="top-row" style="margin-top: 4px;">CONTRATO No. {{ $contract->formatted_number }}</div>
            </div>
            <div class="top-right">
                <div class="top-row"><span class="top-label">EGRESO No.</span></div>
                <div class="top-row"><span class="top-label">VALOR:</span> ${{ number_format($contract->total, 2, ',', '.') }}</div>
                <div class="top-row"><span class="top-label">PROVEEDOR:</span> {{ $supplier->full_name ?? '' }}</div>
            </div>
        </div>

        {{-- OBJETO --}}
        <div class="objeto-box">
            <span class="objeto-label">OBJETO:</span>
            {{ $contract->object }}
        </div>

        {{-- CHECKLIST --}}
        <table class="checklist">
            <thead>
                <tr>
                    <th class="num"></th>
                    <th class="act">Actividad</th>
                    <th class="fol">Folios</th>
                    <th colspan="2">Verificación</th>
                    <th class="fecha">Fecha Verificación</th>
                    <th class="obs">Quien Verifica y Observación</th>
                </tr>
                <tr>
                    <th class="num"></th>
                    <th class="act"></th>
                    <th class="fol"></th>
                    <th class="si">SI</th>
                    <th class="no">NO</th>
                    <th class="fecha"></th>
                    <th class="obs"></th>
                </tr>
            </thead>
            <tbody>
                {{-- Documentos Iniciales --}}
                <tr class="section-row"><td></td><td colspan="6">Documentos Iniciales</td></tr>
                @php
                $iniciales = [
                    'Estudio de Oportunidad y Conveniencia / Estudios previos',
                    'Requisición de necesidades',
                    'Solicitud de certificado de Disponibilidad',
                    'Convocatoria invitación a cotizar',
                    'Cotizaciones - Propuesta',
                    'Acta Evaluación de propuestas - Recibidas',
                    'Aceptación de la propuesta',
                    'Certificado de Disponibilidad Presupuestal',
                    'Certificado de Disponibilidad de Tesorería',
                ];
                @endphp
                @foreach($iniciales as $i => $item)
                <tr><td class="num">{{ $i + 1 }}</td><td class="act">{{ $item }}</td><td class="fol"></td><td class="si"></td><td class="no"></td><td class="fecha"></td><td class="obs"></td></tr>
                @endforeach

                {{-- Documentos del contratista --}}
                <tr class="section-row"><td></td><td colspan="6">Documentos del contratista</td></tr>
                @php
                $contratista = [
                    'C.C.',
                    'RUT',
                    'Procuraduría',
                    'Contraloría',
                    'Antecedentes Policía Nacional',
                    'Medidas correctivas Policía Nacional',
                    'Copia de la libreta militar para hombres menores de 50 años',
                    'Cámara de Comercio (Cuando aplique)',
                    'Afiliación sistema de Salud',
                    'Certificación REEDAM',
                    'Certificación Delitos Sexuales',
                    'Certificación Bancaria',
                    'Hoja de Vida/Función Pública',
                    'Certificado de no presentar inhabilidades e incompatibilidades para contratar con el estado.',
                ];
                @endphp
                @foreach($contratista as $i => $item)
                <tr><td class="num">{{ $i + 10 }}</td><td class="act">{{ $item }}</td><td class="fol"></td><td class="si"></td><td class="no"></td><td class="fecha"></td><td class="obs"></td></tr>
                @endforeach

                {{-- Documentos contractuales y de liquidación --}}
                <tr class="section-row"><td></td><td colspan="6">Documentos contractuales y de liquidación</td></tr>
                @php
                $contractuales = [
                    'Resolución de asignación de supervisor',
                    'Contrato u Orden',
                    'Registro Presupuestal',
                    'Acta de inicio',
                    'Comprobante de causación',
                    'Informe de Supervisor - Interventor',
                    'Recibido a satisfacción',
                    'Informe de Contratista (Actividades)',
                    'Factura o documento equivalente',
                    'Ingresos Al Almacén (para bienes)',
                    'Salida de Almacén (para bienes)',
                    'Acta de liquidación',
                    'Resolución de pago',
                    'Comprobante de egresos',
                    'Recibo pago estampillas (Pago estampillas)',
                    'Constancia transferencia/fotocopia cheque',
                    'Constancia registro publicación Secop',
                ];
                @endphp
                @foreach($contractuales as $i => $item)
                <tr><td class="num">{{ $i + 24 }}</td><td class="act">{{ $item }}</td><td class="fol"></td><td class="si"></td><td class="no"></td><td class="fecha"></td><td class="obs"></td></tr>
                @endforeach
            </tbody>
        </table>

        {{-- FIRMAS --}}
        <div style="padding: 8px;">
            <table class="firma-table">
                <tr>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->auxiliar_display_name }}</div>
                            <div class="sig-role">AUXILIAR ADMINISTRATIVO</div>
                            <div class="sig-role">Responsable de Verificación.</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-line">
                            <div class="sig-name">{{ $school->rector_display_name }}</div>
                            <div class="sig-role">Vo. Bo. RECTOR(A)</div>
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
