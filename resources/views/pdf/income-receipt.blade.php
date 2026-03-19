<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Ingreso No. {{ $income->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; }
        .container { padding: 20px 30px; }

        /* Main border */
        .receipt-border { border: 2px solid #333; padding: 0; }

        /* Header row */
        .header-row { display: table; width: 100%; border-bottom: 2px solid #333; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; padding: 10px 15px; }
        .header-right { display: table-cell; vertical-align: middle; width: 35%; text-align: center; padding: 10px 15px; border-left: 2px solid #333; }
        .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #111; }
        .school-detail { font-size: 9px; color: #444; margin-top: 2px; }
        .receipt-title { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .receipt-number { font-size: 22px; font-weight: bold; color: #1a56db; margin-top: 4px; }

        /* Info rows */
        .info-row { display: table; width: 100%; border-bottom: 1px solid #999; }
        .info-row.thick { border-bottom: 2px solid #333; }
        .info-cell { display: table-cell; padding: 5px 10px; vertical-align: middle; font-size: 10px; }
        .info-label { font-weight: bold; text-transform: uppercase; white-space: nowrap; }
        .info-value { color: #111; }
        .info-cell-border { border-left: 1px solid #999; }
        .amount-big { font-size: 14px; font-weight: bold; color: #111; text-align: right; }

        /* Section title */
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; padding: 8px 0; border-bottom: 2px solid #333; letter-spacing: 2px; }

        /* Accounting table */
        .acct-table { width: 100%; border-collapse: collapse; }
        .acct-table th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; padding: 5px 8px; border: 1px solid #999; text-align: center; font-weight: bold; }
        .acct-table td { padding: 4px 8px; border: 1px solid #ccc; font-size: 10px; }
        .acct-table .code-col { width: 12%; font-weight: bold; }
        .acct-table .name-col { width: 48%; }
        .acct-table .debit-col { width: 20%; text-align: right; }
        .acct-table .credit-col { width: 20%; text-align: right; }
        .acct-table .level-1 { font-weight: bold; font-size: 10px; }
        .acct-table .level-2 { padding-left: 15px; font-weight: bold; font-size: 10px; }
        .acct-table .level-3 { padding-left: 25px; font-size: 9px; }
        .acct-table .level-4 { padding-left: 35px; font-size: 9px; }
        .acct-table .level-5 { padding-left: 45px; font-size: 9px; color: #444; }
        .acct-table tfoot td { font-weight: bold; background: #f0f0f0; border: 1px solid #999; font-size: 10px; }

        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 50px; }
        .signature-box { display: table-cell; width: 45%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 4px; }
        .signature-name { font-weight: bold; font-size: 10px; }
        .signature-role { font-size: 8px; color: #666; }

        /* Footer */
        .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="receipt-border">
        {{-- Header --}}
        <div class="header-row">
            <div class="header-left">
                <div style="display: table; width: 100%;">
                    @if($school->logo_absolute_path && file_exists($school->logo_absolute_path))
                    <div style="display: table-cell; vertical-align: middle; width: 65px; padding-right: 10px;">
                        <img src="{{ $school->logo_absolute_path }}" style="width: 55px; height: 55px; object-fit: contain;" alt="Logo">
                    </div>
                    @endif
                    <div style="display: table-cell; vertical-align: middle;">
                        <div class="school-name">{{ $school->name }}</div>
                        <div class="school-detail">{{ $school->municipality ?? '' }} - {{ $school->address ? $school->address : '' }}</div>
                        <div class="school-detail">NIT: {{ $school->nit ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="receipt-title">Comprobante de Ingreso No.</div>
                <div class="receipt-number">{{ $income->id }}</div>
            </div>
        </div>

        {{-- Ciudad y Fecha + Monto --}}
        <div class="info-row thick">
            <div class="info-cell" style="width: 15%;">
                <span class="info-label">Ciudad y Fecha:</span>
            </div>
            <div class="info-cell" style="width: 50%;">
                <span class="info-value">{{ $school->municipality ?? 'N/A' }} &nbsp;&nbsp; {{ $income->date->format('d/m/Y') }}</span>
            </div>
            <div class="info-cell info-cell-border amount-big" style="width: 35%;">
                ${{ number_format($income->amount, 2, ',', '.') }}
            </div>
        </div>

        {{-- Recibido de --}}
        <div class="info-row">
            <div class="info-cell" style="width: 15%;">
                <span class="info-label">Recibido de:</span>
            </div>
            <div class="info-cell" style="width: 85%;">
                <span class="info-value">{{ $income->fundingSource->name ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- Por concepto de --}}
        <div class="info-row">
            <div class="info-cell" style="width: 18%;">
                <span class="info-label">Por concepto de:</span>
            </div>
            <div class="info-cell" style="width: 82%;">
                <span class="info-value">{{ $income->name }}</span>
            </div>
        </div>

        {{-- Descripción --}}
        @if($income->description)
        <div class="info-row">
            <div class="info-cell" style="width: 100%; padding-left: 30px;">
                <span class="info-value" style="font-size: 9px; color: #555;">{{ $income->description }}</span>
            </div>
        </div>
        @endif

        {{-- Detalle bancario --}}
        @foreach($income->bankAccounts as $ba)
        <div class="info-row">
            <div class="info-cell" style="width: 100%; padding-left: 30px;">
                <span class="info-value" style="font-size: 9px;">
                    {{ $ba->bank->name ?? '' }} - {{ $ba->bankAccount->account_type_name ?? '' }} {{ $ba->bankAccount->account_number ?? '' }}
                    @if($income->bankAccounts->count() > 1)
                        &nbsp; ${{ number_format($ba->amount, 2, ',', '.') }}
                    @endif
                </span>
            </div>
        </div>
        @endforeach

        {{-- La suma de (en letras) --}}
        <div class="info-row thick">
            <div class="info-cell" style="width: 22%;">
                <span class="info-label">La suma de (en letras):</span>
            </div>
            <div class="info-cell" style="width: 78%;">
                <span class="info-value" style="text-transform: uppercase;">{{ $amountInWords }}</span>
            </div>
        </div>

        {{-- Imputación Contable --}}
        <div class="section-title">Imputación Contable</div>

        <table class="acct-table">
            <thead>
                <tr>
                    <th class="code-col">Código</th>
                    <th class="name-col">Denominación</th>
                    <th class="debit-col">Débitos</th>
                    <th class="credit-col">Créditos</th>
                </tr>
            </thead>
            <tbody>
                {{-- Débito: Caja / Bancos (donde entra el dinero) --}}
                @foreach($debitAccounts as $entry)
                    @foreach($entry['hierarchy'] as $acct)
                    <tr>
                        <td class="code-col">{{ $acct['code'] }}</td>
                        <td class="name-col level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td class="debit-col">@if($acct['show_amount'])${{ number_format($entry['amount'], 2, ',', '.') }}@endif</td>
                        <td class="credit-col"></td>
                    </tr>
                    @endforeach
                @endforeach

                {{-- Espacio separador --}}
                <tr>
                    <td colspan="4" style="padding: 2px;">&nbsp;</td>
                </tr>

                {{-- Crédito: Cuenta del rubro presupuestal (de donde sale contablemente) --}}
                @foreach($creditHierarchy as $acct)
                <tr>
                    <td class="code-col">{{ $acct['code'] }}</td>
                    <td class="name-col level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                    <td class="debit-col"></td>
                    <td class="credit-col">@if($acct['show_amount'])${{ number_format($income->amount, 2, ',', '.') }}@endif</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; padding-right: 15px;">TOTALES:</td>
                    <td class="debit-col">${{ number_format($income->amount, 2, ',', '.') }}</td>
                    <td class="credit-col">${{ number_format($income->amount, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Firmas --}}
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <div class="signature-name">{{ $school->rector_name ?? 'Rector(a)' }}</div>
                <div class="signature-role">Rector(a) / Ordenador del Gasto</div>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <div class="signature-name">{{ $school->pagador_name ?? 'Pagador(a)' }}</div>
                <div class="signature-role">Pagador(a) / Tesorero(a)</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
