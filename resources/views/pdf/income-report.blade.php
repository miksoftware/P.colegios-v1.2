<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos — {{ $budget->budgetItem->code ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; }

        .container { padding: 25px 35px; }

        /* Header */
        .header { border: 2px solid #1a56db; border-radius: 6px; padding: 12px 18px; margin-bottom: 18px; }
        .header-top { display: table; width: 100%; margin-bottom: 6px; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; }
        .header-right { display: table-cell; vertical-align: middle; width: 35%; text-align: right; }
        .school-name { font-size: 14px; font-weight: bold; color: #1a56db; margin-bottom: 2px; }
        .school-info { font-size: 8px; color: #666; line-height: 1.4; }
        .report-label { font-size: 12px; font-weight: bold; color: #fff; background: #1a56db; padding: 5px 12px; border-radius: 4px; display: inline-block; }
        .report-date { font-size: 9px; color: #555; margin-top: 4px; }

        /* Info rows */
        .info-section { margin-bottom: 14px; }
        .info-title { font-size: 11px; font-weight: bold; color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 3px; margin-bottom: 7px; text-transform: uppercase; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 6px; font-size: 10px; vertical-align: top; }
        .info-table .label { font-weight: bold; color: #555; width: 140px; }
        .info-table .value { color: #111; }

        /* Summary boxes */
        .summary-row { display: table; width: 100%; margin-bottom: 14px; }
        .summary-box { display: table-cell; width: 33.33%; padding: 0 4px; }
        .summary-inner { border: 1px solid #ddd; border-radius: 5px; padding: 8px 10px; text-align: center; }
        .summary-inner.blue { border-color: #93c5fd; background: #eff6ff; }
        .summary-inner.green { border-color: #86efac; background: #f0fdf4; }
        .summary-inner.orange { border-color: #fdba74; background: #fff7ed; }
        .summary-label { font-size: 8px; text-transform: uppercase; color: #666; font-weight: bold; margin-bottom: 3px; }
        .summary-value { font-size: 14px; font-weight: bold; }
        .summary-value.blue { color: #1d4ed8; }
        .summary-value.green { color: #16a34a; }
        .summary-value.orange { color: #ea580c; }

        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-table thead th { background: #e8eef7; color: #1a56db; font-size: 8px; text-transform: uppercase; padding: 6px 8px; border: 1px solid #c5d4e8; text-align: left; }
        .data-table tbody td { padding: 5px 8px; border: 1px solid #ddd; font-size: 9px; }
        .data-table tbody tr:nth-child(even) { background: #f9fafb; }
        .data-table tfoot td { padding: 7px 8px; border: 1px solid #c5d4e8; font-weight: bold; background: #f0f4fa; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signature-box { display: table-cell; width: 45%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; width: 180px; margin: 0 auto; padding-top: 3px; }
        .signature-name { font-weight: bold; font-size: 10px; }
        .signature-role { font-size: 8px; color: #666; }

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; font-size: 7px; color: #999; }

        .no-data { text-align: center; padding: 20px; color: #999; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="school-info">
                        NIT: {{ $school->nit ?? 'N/A' }} | DANE: {{ $school->dane_code ?? 'N/A' }}<br>
                        {{ $school->municipality ?? '' }} {{ $school->address ? '— ' . $school->address : '' }}
                        @if($school->phone) | Tel: {{ $school->phone }} @endif
                    </div>
                </div>
                <div class="header-right">
                    <div class="report-label">REPORTE DE INGRESOS</div>
                    <div class="report-date">Vigencia: {{ $school->current_validity ?? date('Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Información del Rubro -->
        <div class="info-section">
            <div class="info-title">Información Presupuestal</div>
            <table class="info-table">
                <tr>
                    <td class="label">Rubro:</td>
                    <td class="value">{{ $budget->budgetItem->code ?? '' }} — {{ $budget->budgetItem->name ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Fuente de Financiación:</td>
                    <td class="value">{{ $budget->fundingSource->code ?? '' }} — {{ $budget->fundingSource->name ?? '' }}</td>
                </tr>
            </table>
        </div>

        <!-- Resumen -->
        <div class="summary-row">
            <div class="summary-box">
                <div class="summary-inner blue">
                    <div class="summary-label">Presupuestado</div>
                    <div class="summary-value blue">${{ number_format($budget->current_amount, 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="summary-box">
                <div class="summary-inner green">
                    <div class="summary-label">Total Recaudado</div>
                    <div class="summary-value green">${{ number_format($totalCollected, 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="summary-box">
                @php $diff = (float)$budget->current_amount - $totalCollected; @endphp
                <div class="summary-inner orange">
                    <div class="summary-label">{{ $diff >= 0 ? 'Pendiente' : 'Exceso' }}</div>
                    <div class="summary-value orange">${{ number_format(abs($diff), 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="info-section">
            <div class="info-title">Detalle de Ingresos ({{ $incomes->count() }} movimientos)</div>

            @if($incomes->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 10%;">Fecha</th>
                        <th style="width: 25%;">Concepto</th>
                        <th style="width: 25%;">Banco / Cuenta</th>
                        <th style="width: 12%;" class="text-right">Monto</th>
                        <th style="width: 12%;" class="text-right">Acumulado</th>
                        <th style="width: 11%;">Registrado por</th>
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
                                <br><span style="color: #999; font-size: 8px;">{{ \Illuminate\Support\Str::limit($income->description, 50) }}</span>
                            @endif
                        </td>
                        <td>
                            @foreach($income->bankAccounts as $ba)
                                <div {{ !$loop->first ? 'style=margin-top:2px;border-top:1px solid #eee;padding-top:2px;' : '' }}>
                                    {{ $ba->bank->name ?? '' }}<br>
                                    <span style="color: #666;">{{ $ba->bankAccount->account_type_name ?? '' }} {{ $ba->bankAccount->account_number ?? '' }}</span>
                                    <span style="color: #16a34a; font-weight: bold;"> ${{ number_format($ba->amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td class="text-right" style="font-weight: bold; color: #16a34a;">
                            ${{ number_format($income->amount, 2, ',', '.') }}
                        </td>
                        <td class="text-right" style="color: #555;">
                            ${{ number_format($acumulado, 2, ',', '.') }}
                        </td>
                        <td style="font-size: 8px; color: #666;">
                            {{ $income->creator->name ?? 'N/A' }}
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
            <div class="no-data">No se han registrado ingresos para este rubro.</div>
            @endif
        </div>

        <!-- Firmas -->
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

        <!-- Footer -->
        <div class="footer">
            Generado por: {{ $user->name }} | {{ now()->format('d/m/Y H:i') }} | {{ $school->name }}
        </div>
    </div>
</body>
</html>
