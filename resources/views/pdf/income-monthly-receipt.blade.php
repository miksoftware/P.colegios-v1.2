<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Ingreso No. {{ $receipt['receipt_number'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; }
        .container { padding: 20px 30px; }
        .receipt-border { border: 2px solid #333; padding: 0; }
        .header-row { display: table; width: 100%; border-bottom: 2px solid #333; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; padding: 10px 15px; }
        .header-right { display: table-cell; vertical-align: middle; width: 35%; text-align: center; padding: 10px 15px; border-left: 2px solid #333; }
        .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #111; }
        .school-detail { font-size: 9px; color: #444; margin-top: 2px; }
        .receipt-title { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .receipt-number { font-size: 20px; font-weight: bold; color: #1a56db; margin-top: 4px; }
        .info-row { display: table; width: 100%; border-bottom: 1px solid #999; }
        .info-row.thick { border-bottom: 2px solid #333; }
        .info-cell { display: table-cell; padding: 5px 10px; vertical-align: middle; font-size: 10px; }
        .info-label { font-weight: bold; text-transform: uppercase; white-space: nowrap; }
        .info-value { color: #111; }
        .info-cell-border { border-left: 1px solid #999; }
        .amount-big { font-size: 14px; font-weight: bold; color: #111; text-align: right; }
        .section-title { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; padding: 7px 0; border-bottom: 2px solid #333; letter-spacing: 1px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f0f0f0; font-size: 8px; text-transform: uppercase; padding: 5px 6px; border: 1px solid #999; text-align: left; font-weight: bold; }
        .data-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9px; vertical-align: top; }
        .data-table tbody tr:nth-child(even) { background: #fafafa; }
        .data-table tfoot td { font-weight: bold; background: #f0f0f0; border: 1px solid #999; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #666; font-size: 8px; }
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
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signature-box { display: table-cell; width: 45%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 4px; }
        .signature-name { font-weight: bold; font-size: 10px; }
        .signature-role { font-size: 8px; color: #666; }
        .footer { margin-top: 15px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="receipt-border">
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
                <div class="receipt-number">{{ $receipt['receipt_number'] }}</div>
            </div>
        </div>

        <div class="info-row thick">
            <div class="info-cell" style="width: 15%;">
                <span class="info-label">Ciudad y Fecha:</span>
            </div>
            <div class="info-cell" style="width: 50%;">
                <span class="info-value">{{ $school->municipality ?? 'N/A' }} &nbsp;&nbsp; {{ $receipt['period_end']->format('d/m/Y') }}</span>
            </div>
            <div class="info-cell info-cell-border amount-big" style="width: 35%;">
                ${{ number_format($receipt['total_collected'], 2, ',', '.') }}
            </div>
        </div>

        <div class="info-row">
            <div class="info-cell" style="width: 15%;">
                <span class="info-label">Mes:</span>
            </div>
            <div class="info-cell" style="width: 35%;">
                <span class="info-value">{{ $receipt['period_label'] }}</span>
            </div>
            <div class="info-cell info-cell-border" style="width: 15%;">
                <span class="info-label">Vigencia:</span>
            </div>
            <div class="info-cell" style="width: 35%;">
                <span class="info-value">{{ $receipt['year'] }}</span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-cell" style="width: 15%;">
                <span class="info-label">Recibido de:</span>
            </div>
            <div class="info-cell" style="width: 85%;">
                <span class="info-value">Consolidado mensual de ingresos del mes {{ $receipt['period_label'] }}</span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-cell" style="width: 18%;">
                <span class="info-label">Por concepto de:</span>
            </div>
            <div class="info-cell" style="width: 82%;">
                <span class="info-value">Recaudo consolidado de {{ $receipt['movement_count'] }} movimientos del periodo, agrupado por rubro, fuente y codigo contable.</span>
            </div>
        </div>

        @foreach($receipt['debit_entries'] as $entry)
        <div class="info-row">
            <div class="info-cell" style="width: 100%; padding-left: 30px;">
                <span class="info-value" style="font-size: 9px;">
                    {{ $entry['bank_name'] ?: 'Cuenta bancaria' }} - {{ $entry['account_type_name'] ?: 'Tipo no definido' }} {{ $entry['account_number'] }}
                    &nbsp; ${{ number_format((float) ($entry['amount'] ?? 0), 2, ',', '.') }}
                </span>
            </div>
        </div>
        @endforeach

        <div class="info-row thick">
            <div class="info-cell" style="width: 22%;">
                <span class="info-label">La suma de (en letras):</span>
            </div>
            <div class="info-cell" style="width: 78%;">
                <span class="info-value" style="text-transform: uppercase;">{{ $receipt['amount_in_words'] }}</span>
            </div>
        </div>

        <div class="section-title">Detalle Consolidado por Rubro, Fuente y Codigo</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Rubro</th>
                    <th style="width: 20%;">Fuente</th>
                    <th style="width: 10%;">Codigo</th>
                    <th style="width: 32%;">Conceptos</th>
                    <th style="width: 8%;" class="text-center">Mov.</th>
                    <th style="width: 12%;" class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt['detail_groups'] as $group)
                <tr>
                    <td>
                        {{ $group['budget_item_code'] ?? 'N/A' }}<br>
                        <span class="muted">{{ $group['budget_item_name'] ?? 'Sin rubro' }}</span>
                    </td>
                    <td>
                        {{ $group['funding_source_code'] ?? 'N/A' }}<br>
                        <span class="muted">{{ $group['funding_source_name'] ?? 'Sin fuente' }}</span>
                    </td>
                    <td class="text-center">{{ $group['accounting_code'] ?? 'N/A' }}</td>
                    <td>
                        {{ implode(', ', $group['concepts']) }}
                    </td>
                    <td class="text-center">{{ $group['movement_count'] }}</td>
                    <td class="text-right">${{ number_format((float) $group['amount'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">TOTAL CONSOLIDADO:</td>
                    <td class="text-right">${{ number_format($receipt['total_collected'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">Detalle de Movimientos ({{ $receipt['movement_count'] }})</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 10%;">Fecha</th>
                    <th style="width: 18%;">Rubro / Fuente</th>
                    <th style="width: 28%;">Concepto</th>
                    <th style="width: 24%;">Banco / Cuenta</th>
                    <th style="width: 16%;" class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt['incomes'] as $i => $income)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $income->date->format('d/m/Y') }}</td>
                    <td>
                        {{ $income->fundingSource->budgetItem->code ?? 'N/A' }} / {{ $income->fundingSource->code ?? 'N/A' }}<br>
                        <span class="muted">{{ $income->fundingSource->name ?? 'Sin fuente' }}</span>
                    </td>
                    <td>
                        {{ $income->name }}
                        @if($income->description)
                            <br><span class="muted">{{ \Illuminate\Support\Str::limit($income->description, 60) }}</span>
                        @endif
                    </td>
                    <td>
                        @foreach($income->bankAccounts as $bankAccount)
                            <div {!! !$loop->first ? 'style="margin-top:2px;border-top:1px solid #eee;padding-top:2px;"' : '' !!}>
                                {{ $bankAccount->bank->name ?? '' }}<br>
                                <span class="muted">{{ $bankAccount->bankAccount->account_type_name ?? '' }} {{ $bankAccount->bankAccount->account_number ?? '' }}</span>
                                @if($income->bankAccounts->count() > 1)
                                    <span class="muted"> ${{ number_format((float) $bankAccount->amount, 2, ',', '.') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </td>
                    <td class="text-right">${{ number_format((float) $income->amount, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Imputacion Contable</div>

        <table class="acct-table">
            <thead>
                <tr>
                    <th class="code-col">Codigo</th>
                    <th class="name-col">Denominacion</th>
                    <th class="debit-col">Debitos</th>
                    <th class="credit-col">Creditos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt['debit_entries'] as $entry)
                    @foreach($entry['hierarchy'] as $account)
                    <tr>
                        <td class="code-col">{{ $account['code'] }}</td>
                        <td class="name-col level-{{ $account['level'] }}">{{ $account['name'] }}</td>
                        <td class="debit-col">@if($account['show_amount'])${{ number_format((float) $entry['amount'], 2, ',', '.') }}@endif</td>
                        <td class="credit-col"></td>
                    </tr>
                    @endforeach
                @endforeach

                <tr>
                    <td colspan="4" style="padding: 2px;">&nbsp;</td>
                </tr>

                @foreach($receipt['credit_entries'] as $entry)
                    @foreach($entry['hierarchy'] as $account)
                    <tr>
                        <td class="code-col">{{ $account['code'] }}</td>
                        <td class="name-col level-{{ $account['level'] }}">{{ $account['name'] }}</td>
                        <td class="debit-col"></td>
                        <td class="credit-col">@if($account['show_amount'])${{ number_format((float) $entry['amount'], 2, ',', '.') }}@endif</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; padding-right: 15px;">TOTALES:</td>
                    <td class="debit-col">${{ number_format($receipt['total_collected'], 2, ',', '.') }}</td>
                    <td class="credit-col">${{ number_format($receipt['total_collected'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <div class="signature-name">{{ $school->ordenador_gasto_display_name }}</div>
                <div class="signature-role">Ordenador del Gasto</div>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <div class="signature-name">{{ $school->pagador_display_name }}</div>
                <div class="signature-role">Pagador(a) / Tesorero(a)</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
