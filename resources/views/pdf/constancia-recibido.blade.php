<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Recibido - {{ $po->formatted_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #222; line-height: 1.7; }
        .container { padding: 30px 40px; }
        .doc-border { border: 2px solid #1e3a5f; padding: 25px 30px; }

        .header { text-align: center; margin-bottom: 20px; }
        .school-name { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; }
        .school-muni { font-size: 9px; color: #444; }
        .school-nit { font-size: 9px; color: #555; }

        .sub-header { text-align: center; margin-bottom: 15px; font-size: 11px; }

        .hace-constar { text-align: center; font-size: 13px; font-weight: bold; color: #1e3a5f; margin: 15px 0; letter-spacing: 2px; }

        .body-text { font-size: 11px; text-align: justify; line-height: 2; margin-bottom: 10px; }
        .bold { font-weight: bold; }

        .data-inline { display: inline; border-bottom: 1px solid #999; padding: 0 5px; font-weight: bold; }

        .contract-table { width: 70%; border-collapse: collapse; margin: 10px auto; }
        .contract-table td { padding: 4px 10px; font-size: 10px; border: 1px solid #ccc; }
        .contract-table .label { font-weight: bold; text-align: center; background: #f5f5f5; }

        .lugar-fecha { margin-top: 20px; font-size: 11px; }

        .firma-section { margin-top: 50px; }
        .firma-label { font-size: 10px; color: #555; font-style: italic; }
        .sig-line { border-top: 1px solid #333; width: 300px; padding-top: 5px; margin-top: 40px; }
        .sig-name { font-weight: bold; font-size: 11px; text-transform: uppercase; }

        .footer { margin-top: 25px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="doc-border">

        {{-- HEADER --}}
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-muni">{{ $school->municipality ?? '' }}</div>
            <div class="school-nit">{{ $school->nit ?? '' }}</div>
        </div>

        {{-- SUB HEADER --}}
        <div class="sub-header">
            EL(A) SUSCRITO RECTOR DEL
            <br>
            {{ $school->name }}
        </div>

        {{-- HACE CONSTAR --}}
        <div class="hace-constar">HACE CONSTAR</div>

        {{-- CUERPO --}}
        <div class="body-text">
            Que los bienes y/o servicios suministrados por
            <span class="bold">{{ $supplier->full_name ?? 'N/A' }}</span>
            <br>
            <span style="border-bottom: 1px solid #999; padding: 0 3px;">{{ $supplier->document_number ?? '' }}</span>
            &nbsp;&nbsp; DV &nbsp;&nbsp;
            <span style="border-bottom: 1px solid #999; padding: 0 3px;">{{ $supplier->dv ?? '' }}</span>
        </div>

        <div class="body-text">
            se recibieron a satisfacción conforme a los detalles y especificaciones contenidas en la
        </div>

        <div class="body-text">
            orden y/o contrato y factura correspondiente:
        </div>

        {{-- TABLA CONTRATO / FACTURA --}}
        <table class="contract-table">
            <tr>
                <td class="label">Orden y/o contrato</td>
                <td class="label">Factura No.</td>
            </tr>
            <tr>
                <td style="text-align: center;">
                    @if($po->contract)
                        CONTRATO No. {{ $po->contract->formatted_number }}
                    @else
                        N/A
                    @endif
                </td>
                <td style="text-align: center;">{{ $po->invoice_number ?? 'N/A' }}</td>
            </tr>
        </table>

        {{-- LUGAR Y FECHA --}}
        <div class="lugar-fecha">
            {{ $school->municipality ?? '' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            {{ $po->payment_date?->format('d/M/y') ?? now()->format('d/M/y') }}
        </div>

        {{-- FIRMA --}}
        <div class="firma-section">
            <div class="firma-label">Firma RECTOR (ordenador del gasto)</div>
            <div class="sig-line">
                <div class="sig-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
