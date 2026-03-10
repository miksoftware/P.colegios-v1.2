<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Ingreso #{{ $income->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #333; }

        .container { padding: 30px 40px; }

        /* Header */
        .header { border: 2px solid #1a56db; border-radius: 6px; padding: 15px 20px; margin-bottom: 20px; }
        .header-top { display: table; width: 100%; margin-bottom: 8px; }
        .header-left { display: table-cell; vertical-align: middle; width: 70%; }
        .header-right { display: table-cell; vertical-align: middle; width: 30%; text-align: right; }
        .school-name { font-size: 16px; font-weight: bold; color: #1a56db; margin-bottom: 3px; }
        .school-info { font-size: 9px; color: #666; line-height: 1.5; }
        .receipt-label { font-size: 14px; font-weight: bold; color: #fff; background: #1a56db; padding: 6px 14px; border-radius: 4px; display: inline-block; }
        .receipt-number { font-size: 11px; color: #1a56db; font-weight: bold; margin-top: 5px; }

        /* Info rows */
        .info-section { margin-bottom: 15px; }
        .info-title { font-size: 12px; font-weight: bold; color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 4px; margin-bottom: 8px; text-transform: uppercase; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 8px; font-size: 11px; vertical-align: top; }
        .info-table .label { font-weight: bold; color: #555; width: 150px; }
        .info-table .value { color: #111; }

        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table thead th { background: #e8eef7; color: #1a56db; font-size: 10px; text-transform: uppercase; padding: 8px 10px; border: 1px solid #c5d4e8; text-align: left; }
        .data-table tbody td { padding: 7px 10px; border: 1px solid #ddd; font-size: 11px; }
        .data-table tfoot td { padding: 8px 10px; border: 1px solid #c5d4e8; font-weight: bold; background: #f0f4fa; }
        .text-right { text-align: right; }

        /* Amount highlight */
        .total-amount { font-size: 14px; font-weight: bold; color: #16a34a; }

        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 50px; }
        .signature-box { display: table-cell; width: 45%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 4px; }
        .signature-name { font-weight: bold; font-size: 11px; }
        .signature-role { font-size: 9px; color: #666; }

        /* Footer */
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 8px; text-align: center; font-size: 8px; color: #999; }
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
                        NIT: {{ $school->nit ?? 'N/A' }}<br>
                        DANE: {{ $school->dane_code ?? 'N/A' }}<br>
                        {{ $school->municipality ?? '' }} {{ $school->address ? '— ' . $school->address : '' }}<br>
                        @if($school->phone) Tel: {{ $school->phone }} @endif
                        @if($school->email) | {{ $school->email }} @endif
                    </div>
                </div>
                <div class="header-right">
                    <div class="receipt-label">COMPROBANTE DE INGRESO</div>
                    <div class="receipt-number">No. {{ str_pad($income->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>

        <!-- Información General -->
        <div class="info-section">
            <div class="info-title">Información del Ingreso</div>
            <table class="info-table">
                <tr>
                    <td class="label">Fecha del Ingreso:</td>
                    <td class="value">{{ $income->date->format('d/m/Y') }}</td>
                    <td class="label">Fecha de Registro:</td>
                    <td class="value">{{ $income->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td class="label">Concepto:</td>
                    <td class="value" colspan="3">{{ $income->name }}</td>
                </tr>
                @if($income->description)
                <tr>
                    <td class="label">Descripción:</td>
                    <td class="value" colspan="3">{{ $income->description }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Información Presupuestal -->
        <div class="info-section">
            <div class="info-title">Información Presupuestal</div>
            <table class="info-table">
                <tr>
                    <td class="label">Rubro:</td>
                    <td class="value" colspan="3">
                        {{ $income->fundingSource->budgetItem->code ?? '' }} — {{ $income->fundingSource->budgetItem->name ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Fuente de Financiación:</td>
                    <td class="value" colspan="3">
                        {{ $income->fundingSource->code ?? '' }} — {{ $income->fundingSource->name ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Vigencia Fiscal:</td>
                    <td class="value">{{ $school->current_validity ?? date('Y') }}</td>
                    <td class="label">Monto Total:</td>
                    <td class="value total-amount">${{ number_format($income->amount, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Detalle Cuentas Bancarias -->
        <div class="info-section">
            <div class="info-title">Detalle de Cuentas Bancarias</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Banco</th>
                        <th style="width: 20%;">Tipo de Cuenta</th>
                        <th style="width: 25%;">Número de Cuenta</th>
                        <th style="width: 20%;" class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($income->bankAccounts as $i => $ba)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $ba->bank->name ?? 'N/A' }}</td>
                        <td>{{ $ba->bankAccount->account_type_name ?? 'N/A' }}</td>
                        <td>{{ $ba->bankAccount->account_number ?? 'N/A' }}</td>
                        <td class="text-right">${{ number_format($ba->amount, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right">TOTAL:</td>
                        <td class="text-right total-amount">${{ number_format($income->amount, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
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
