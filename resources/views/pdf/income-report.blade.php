<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos — {{ $budget->budgetItem->code ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #222; }
        .container { padding: 20px 30px; }

        /* Main border */
        .report-border { border: 2px solid #333; padding: 0; }

        /* Header row */
        .header-row { display: table; width: 100%; border-bottom: 2px solid #333; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; padding: 10px 15px; }
        .header-right { display: table-cell; vertical-align: middle; width: 35%; text-align: center; padding: 10px 15px; border-left: 2px solid #333; }
        .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #111; }
        .school-detail { font-size: 9px; color: #444; margin-top: 2px; }
        .report-title { font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .report-subtitle { font-size: 9px; color: #555; margin-top: 3px; }

        /* Info rows */
        .info-row { display: table; width: 100%; border-bottom: 1px solid #999; }
        .info-row.thick { border-bottom: 2px solid #333; }
        .info-cell { display: table-cell; padding: 5px 10px; vertical-align: middle; font-size: 10px; }
        .info-label { font-weight: bold; text-transform: uppercase; white-space: nowrap; }
        .info-value { color: #111; }
        .info-cell-border { border-left: 1px solid #999; }

        /* Summary boxes */
        .summary-row { display: table; width: 100%; border-bottom: 2px solid #333; }
        .summary-cell { display: table-cell; width: 33.33%; text-align: center; padding: 8px 5px; border-right: 1px solid #999; }
        .summary-cell:last-child { border-right: none; }
        .summary-label { font-size: 8px; text-transform: uppercase; color: #666; font-weight: bold; }
        .summary-value { font-size: 13px; font-weight: bold; margin-top: 2px; }

        /* Section title */
        .section-title { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; padding: 6px 0; border-bottom: 1px solid #999; letter-spacing: 1px; }

        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f0f0f0; font-size: 8px; text-transform: uppercase; padding: 5px 6px; border: 1px solid #999; text-align: left; font-weight: bold; }
        .data-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9px; }
        .data-table tbody tr:nth-child(even) { background: #fafafa; }
        .data-table tfoot td { font-weight: bold; background: #f0f0f0; border: 1px solid #999; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Accounting table */
        .acct-table { width: 100%; border-collapse: collapse; }
        .acct-table th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; padding: 5px 8px; border: 1px solid #999; text-align: center; font-weight: bold; }
        .acct-table td { padding: 4px 8px; border: 1px solid #ccc; font-size: 10px; }
        .acct-table .level-1 { font-weight: bold; }
        .acct-table .level-2 { padding-left: 15px; font-weight: bold; }
        .acct-table .level-3 { padding-left: 25px; font-size: 9px; }
        .acct-table .level-4 { padding-left: 35px; font-size: 9px; }
        .acct-table .level-5 { padding-left: 45px; font-size: 9px; color: #444; }
        .acct-table tfoot td { font-weight: bold; background: #f0f0f0; border: 1px solid #999; }

        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signature-box { display: table-cell; width: 45%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; width: 180px; margin: 0 auto; padding-top: 3px; }
        .signature-name { font-weight: bold; font-size: 10px; }
        .signature-role { font-size: 8px; color: #666; }

        /* Footer */
        .footer { margin-top: 15px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="container">
    <div class="report-border">
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
                        <div class="school-detail">{{ $school->municipality ?? '' }} - {{ $school->address ?? '' }}</div>
                        <div class="school-detail">NIT: {{ $school->nit ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="report-title">Reporte de Ingresos</div>
                <div class="report-subtitle">Vigencia Fiscal {{ $school->current_validity ?? date('Y') }}</div>
            </div>
        </div>

        {{-- Información del Rubro --}}
        <div class="info-row">
            <div class="info-cell" style="width: 10%;">
                <span class="info-label">Rubro:</span>
            </div>
            <div class="info-cell" style="width: 90%;">
                <span class="info-value">{{ $budget->budgetItem->code ?? '' }} — {{ $budget->budgetItem->name ?? '' }}</span>
            </div>
        </div>
        <div class="info-row thick">
            <div class="info-cell" style="width: 10%;">
                <span class="info-label">Fuente:</span>
            </div>
            <div class="info-cell" style="width: 90%;">
                <span class="info-value">{{ $budget->fundingSource->code ?? '' }} — {{ $budget->fundingSource->name ?? '' }}</span>
            </div>
        </div>

        {{-- Resumen --}}
        @php $diff = (float)$budget->current_amount - $totalCollected; @endphp
        <div class="summary-row">
            <div class="summary-cell">
                <div class="summary-label">Presupuestado</div>
                <div class="summary-value" style="color: #1d4ed8;">${{ number_format($budget->current_amount, 2, ',', '.') }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">Total Recaudado</div>
                <div class="summary-value" style="color: #16a34a;">${{ number_format($totalCollected, 2, ',', '.') }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">{{ $diff >= 0 ? 'Pendiente' : 'Exceso' }}</div>
                <div class="summary-value" style="color: #ea580c;">${{ number_format(abs($diff), 2, ',', '.') }}</div>
            </div>
        </div>

        {{-- Detalle de Ingresos --}}
        <div class="section-title">Detalle de Ingresos ({{ $incomes->count() }} movimientos)</div>

        @if($incomes->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 9%;">Fecha</th>
                    <th style="width: 22%;">Concepto</th>
                    <th style="width: 22%;">Banco / Cuenta</th>
                    <th style="width: 13%;" class="text-right">Monto</th>
                    <th style="width: 13%;" class="text-right">Acumulado</th>
                    <th style="width: 17%;">En Letras</th>
                </tr>
            </thead>
            <tbody>
                @php $acumulado = 0; @endphp
                @foreach($incomes as $i => $income)
                @php $acumulado += (float) $income->amount; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $income->date->format('d/m/Y') }}</td>
                    <td>
                        {{ $income->name }}
                        @if($income->description)
                            <br><span style="color: #888; font-size: 7px;">{{ \Illuminate\Support\Str::limit($income->description, 40) }}</span>
                        @endif
                    </td>
                    <td>
                        @foreach($income->bankAccounts as $ba)
                            <div {{ !$loop->first ? 'style=margin-top:2px;border-top:1px solid #eee;padding-top:2px;' : '' }}>
                                {{ $ba->bank->name ?? '' }}<br>
                                <span style="color: #666; font-size: 8px;">{{ $ba->bankAccount->account_type_name ?? '' }} {{ $ba->bankAccount->account_number ?? '' }}</span>
                                @if($income->bankAccounts->count() > 1)
                                    <span style="color: #16a34a; font-weight: bold;"> ${{ number_format($ba->amount, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #16a34a;">
                        ${{ number_format($income->amount, 2, ',', '.') }}
                    </td>
                    <td class="text-right" style="color: #555;">
                        ${{ number_format($acumulado, 2, ',', '.') }}
                    </td>
                    <td style="font-size: 7px; color: #666; text-transform: uppercase;">
                        {{ \App\Http\Controllers\IncomePdfController::amountToWords($income->amount) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">TOTAL RECAUDADO:</td>
                    <td class="text-right" style="font-size: 11px; color: #16a34a;">${{ number_format($totalCollected, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div style="text-align: center; padding: 20px; color: #999;">No se han registrado ingresos para este rubro.</div>
        @endif
    </div>

    {{-- Imputación Contable --}}
    @if(!empty($creditHierarchy))
    <div style="margin-top: 15px;">
        <div style="border: 2px solid #333; padding: 0;">
            <div class="section-title" style="border-bottom: 2px solid #333;">Imputación Contable</div>
            <table class="acct-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Código</th>
                        <th style="width: 48%;">Denominación</th>
                        <th style="width: 20%;">Débitos</th>
                        <th style="width: 20%;">Créditos</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Débito: Bancos (genérico para el reporte colectivo) --}}
                    @php
                        $debitAccount = \App\Models\AccountingAccount::where('code', '1110')->first();
                        $debitHierarchy = [];
                        if ($debitAccount) {
                            $current = $debitAccount;
                            while ($current) {
                                array_unshift($debitHierarchy, [
                                    'code' => $current->code,
                                    'name' => $current->name,
                                    'level' => $current->level,
                                    'show_amount' => ($current->id === $debitAccount->id),
                                ]);
                                $current = $current->parent;
                            }
                        }
                    @endphp
                    @foreach($debitHierarchy as $acct)
                    <tr>
                        <td>{{ $acct['code'] }}</td>
                        <td class="level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td class="text-right">@if($acct['show_amount'])${{ number_format($totalCollected, 2, ',', '.') }}@endif</td>
                        <td></td>
                    </tr>
                    @endforeach

                    <tr><td colspan="4" style="padding: 2px;">&nbsp;</td></tr>

                    {{-- Crédito: Cuenta del rubro --}}
                    @foreach($creditHierarchy as $acct)
                    <tr>
                        <td>{{ $acct['code'] }}</td>
                        <td class="level-{{ $acct['level'] }}">{{ $acct['name'] }}</td>
                        <td></td>
                        <td class="text-right">@if($acct['show_amount'])${{ number_format($totalCollected, 2, ',', '.') }}@endif</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right" style="padding-right: 15px;">TOTALES:</td>
                        <td class="text-right">${{ number_format($totalCollected, 2, ',', '.') }}</td>
                        <td class="text-right">${{ number_format($totalCollected, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- Firmas --}}
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

    {{-- Footer --}}
    <div class="footer">
        Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
    </div>
</div>
</body>
</html>
