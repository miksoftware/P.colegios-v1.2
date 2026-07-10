<div>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Libro de Bancos</h1>
                    <p class="text-gray-500 mt-1">Movimientos por cuenta bancaria - {{ $this->periodLabel }}</p>
                </div>
                @can('reports.export')
                <button id="btn-export-bank-book" onclick="window.exportBankBookExcel()" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Exportar Excel
                </button>
                @endcan
            </div>

            {{-- Info del Colegio --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900 uppercase">LIBRO DE BANCOS VIGENCIA {{ $filterYear }}</h2>
                    <p class="text-sm text-gray-600">INSTITUCI&Oacute;N EDUCATIVA: <span class="font-semibold">{{ $school->name ?? 'N/A' }}</span></p>
                    <p class="text-sm text-gray-600">NIT: <span class="font-semibold">{{ $school->nit ?? 'N/A' }}</span> &nbsp;&nbsp; MUNICIPIO: <span class="font-semibold">{{ $school->municipality ?? 'N/A' }}</span></p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                        <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                            @for($y = now()->year + 1; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria</label>
                        <select wire:model.live="filterBankAccount" class="w-full rounded-xl border-gray-300">
                            <option value="">Seleccionar cuenta...</option>
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba['id'] }}">{{ $ba['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if($selectedAccount)
            {{-- Gestión de extractos bancarios por mes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                    <div class="w-full lg:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes del Extracto</label>
                        <select wire:model.live="statementMonth" class="w-full rounded-xl border-gray-300">
                            @foreach($this->monthOptions as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full lg:w-2/4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Archivo PDF</label>
                        <input
                            type="file"
                            wire:model="statementFile"
                            accept="application/pdf,.pdf"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 disabled:opacity-60"
                            @disabled(!empty($selectedStatement))
                        >
                        @error('statementFile')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-full lg:w-1/4">
                        <button
                            wire:click="uploadStatement"
                            wire:loading.attr="disabled"
                            wire:target="statementFile,uploadStatement"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl disabled:opacity-60 disabled:cursor-not-allowed"
                            @disabled(!empty($selectedStatement))
                        >
                            Cargar Extracto
                        </button>
                    </div>
                </div>

                @if($selectedStatement)
                <div class="mt-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">
                            Mes {{ $this->monthOptions[$statementMonth] ?? $statementMonth }} bloqueado por extracto cargado
                        </p>
                        <p class="text-xs text-emerald-700">
                            Archivo: {{ $selectedStatement['file_name'] }}
                            ({{ number_format(($selectedStatement['file_size'] ?? 0) / 1024, 1, ',', '.') }} KB)
                            @if(!empty($selectedStatement['uploaded_at']))
                                - Cargado: {{ $selectedStatement['uploaded_at'] }}
                            @endif
                        </p>
                    </div>

                    @if($isAdmin)
                    <div class="flex gap-2">
                        <button
                            wire:click="downloadStatement({{ $selectedStatement['id'] }})"
                            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            Descargar PDF
                        </button>
                        <button
                            wire:click="deleteStatement({{ $selectedStatement['id'] }})"
                            onclick="if(!confirm('¿Eliminar este extracto? El mes quedará habilitado para una nueva carga.')){event.stopImmediatePropagation();return false;}"
                            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700"
                        >
                            Eliminar
                        </button>
                    </div>
                    @else
                    <p class="text-xs text-amber-700 font-medium">Solo los administradores pueden descargar o eliminar extractos.</p>
                    @endif
                </div>
                @else
                <p class="mt-4 text-sm text-blue-700">Este mes está habilitado para cargar extracto bancario (PDF).</p>
                @endif

                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    @foreach($this->monthOptions as $monthNumber => $monthName)
                        @php $monthStatement = $statementByMonth[$monthNumber] ?? null; @endphp
                        <div class="px-3 py-2 rounded-lg border text-xs {{ $monthStatement ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                            <p class="font-semibold">{{ $monthName }}</p>
                            <p>{{ $monthStatement ? 'Cargado' : 'Sin archivo' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($selectedAccount)
            {{-- Info de la Cuenta --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 font-medium">ENTIDAD</span>
                        <p class="font-semibold text-gray-900">{{ $selectedAccount['bank_name'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium">N. CUENTA</span>
                        <p class="font-semibold text-gray-900">{{ $selectedAccount['account_number'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium">TIPO CUENTA</span>
                        <p class="font-semibold text-gray-900">{{ $selectedAccount['account_type'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium">NOMBRE CTA</span>
                        <p class="font-semibold text-gray-900">{{ $selectedAccount['holder_name'] ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tarjetas Resumen --}}
            @if(!empty($movements))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Saldo Anterior</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($movements['previous_balance'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Al 31/12/{{ $movements['previous_year'] ?? ($filterYear - 1) }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Total Ingresos</p>
                    <p class="text-2xl font-bold text-emerald-600">${{ number_format($movements['total_income'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Total Egresos</p>
                    <p class="text-2xl font-bold text-red-600">${{ number_format($movements['total_expense'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Saldo Final</p>
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($movements['final_balance'] ?? 0, 2, ',', '.') }}</p>
                </div>
            </div>
            @endif

            {{-- Tabla de Movimientos --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="bank-book-table">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th rowspan="2" class="px-4 py-3 text-left font-semibold border-r border-gray-600">FECHA</th>
                                <th rowspan="2" class="px-4 py-3 text-left font-semibold border-r border-gray-600">DETALLE</th>
                                <th colspan="2" class="px-4 py-2 text-center font-semibold border-r border-gray-600 bg-emerald-700">INGRESOS</th>
                                <th colspan="2" class="px-4 py-2 text-center font-semibold border-r border-gray-600 bg-red-700">EGRESOS</th>
                                <th rowspan="2" class="px-4 py-3 text-right font-semibold bg-blue-700">NUEVO SALDO</th>
                            </tr>
                            <tr class="bg-gray-700 text-white text-xs">
                                <th class="px-3 py-2 text-center font-medium border-r border-gray-600">No CONSIG</th>
                                <th class="px-3 py-2 text-right font-medium border-r border-gray-600">VALOR</th>
                                <th class="px-3 py-2 text-center font-medium border-r border-gray-600">No CHEQUE</th>
                                <th class="px-3 py-2 text-right font-medium border-r border-gray-600">VALOR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            {{-- Fila de saldo anterior --}}
                            <tr class="bg-yellow-50 font-semibold">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">31/12/{{ $movements['previous_year'] ?? ($filterYear - 1) }}</td>
                                <td class="px-4 py-3 text-gray-700">SALDO ANTERIOR A 31/12/{{ $movements['previous_year'] ?? ($filterYear - 1) }}</td>
                                <td class="px-3 py-3 text-center text-gray-400">—</td>
                                <td class="px-3 py-3 text-right text-gray-400">—</td>
                                <td class="px-3 py-3 text-center text-gray-400">—</td>
                                <td class="px-3 py-3 text-right text-gray-400">—</td>
                                <td class="px-3 py-3 text-right font-bold text-gray-900">$ {{ number_format($movements['previous_balance'] ?? 0, 2, ',', '.') }}</td>
                            </tr>

                            {{-- Movimientos --}}
                            @forelse(($movements['items'] ?? []) as $mov)
                                <tr class="hover:bg-gray-50 {{ $mov['type'] === 'income' ? '' : '' }}">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ $mov['date'] ? \Carbon\Carbon::parse($mov['date'])->format('d/m/Y') : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 max-w-md">
                                        <span class="line-clamp-2">{{ $mov['detail'] }}</span>
                                    </td>
                                    @if($mov['type'] === 'income')
                                        <td class="px-3 py-3 text-center text-gray-500">{{ $mov['income_ref'] }}</td>
                                        <td class="px-3 py-3 text-right font-medium text-emerald-700">$ {{ number_format($mov['income_amount'], 2, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-center text-gray-300">—</td>
                                        <td class="px-3 py-3 text-right text-gray-300">—</td>
                                    @else
                                        <td class="px-3 py-3 text-center text-gray-300">—</td>
                                        <td class="px-3 py-3 text-right text-gray-300">—</td>
                                        <td class="px-3 py-3 text-center text-gray-500">{{ $mov['expense_ref'] }}</td>
                                        <td class="px-3 py-3 text-right font-medium text-red-600">$ {{ number_format($mov['expense_amount'], 2, ',', '.') }}</td>
                                    @endif
                                    <td class="px-3 py-3 text-right font-bold text-gray-900">{{ number_format($mov['balance'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        No hay movimientos registrados para esta cuenta en la vigencia {{ $filterYear }}.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Fila de totales --}}
                            @if(!empty($movements['items']))
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                <td class="px-4 py-3" colspan="2">TOTALES</td>
                                <td class="px-3 py-3"></td>
                                <td class="px-3 py-3 text-right text-emerald-700">$ {{ number_format($movements['total_income'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3"></td>
                                <td class="px-3 py-3 text-right text-red-600">$ {{ number_format($movements['total_expense'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-blue-700">$ {{ number_format($movements['final_balance'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @else
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <p class="text-gray-500 text-lg">Seleccione una cuenta bancaria para ver el libro de bancos.</p>
                    @if(empty($bankAccounts))
                        <p class="text-gray-400 text-sm mt-2">No hay cuentas bancarias registradas para este colegio.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Hidden data for JS export --}}
    @if($selectedAccount && !empty($movements))
    <div class="hidden"
        id="bank-book-data"
        data-school-name="{{ $school->name ?? '' }}"
        data-school-nit="{{ $school->nit ?? '' }}"
        data-school-municipality="{{ $school->municipality ?? '' }}"
        data-year="{{ $filterYear }}"
        data-bank-name="{{ $selectedAccount['bank_name'] }}"
        data-account-number="{{ $selectedAccount['account_number'] }}"
        data-account-type="{{ $selectedAccount['account_type'] }}"
        data-holder-name="{{ $selectedAccount['holder_name'] }}"
        data-previous-balance="{{ $movements['previous_balance'] ?? 0 }}"
        data-previous-year="{{ $movements['previous_year'] ?? ($filterYear - 1) }}"
        data-total-income="{{ $movements['total_income'] ?? 0 }}"
        data-total-expense="{{ $movements['total_expense'] ?? 0 }}"
        data-final-balance="{{ $movements['final_balance'] ?? 0 }}"
        data-movements="{{ json_encode($movements['items'] ?? []) }}"
    ></div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
window.exportBankBookExcel = function() {
    var dataEl = document.getElementById('bank-book-data');
    if (!dataEl) { alert('No hay datos para exportar.'); return; }

    var schoolName = dataEl.dataset.schoolName;
    var schoolNit = dataEl.dataset.schoolNit;
    var schoolMunicipality = dataEl.dataset.schoolMunicipality;
    var year = dataEl.dataset.year;
    var bankName = dataEl.dataset.bankName;
    var accountNumber = dataEl.dataset.accountNumber;
    var accountType = dataEl.dataset.accountType;
    var holderName = dataEl.dataset.holderName;
    var previousBalance = parseFloat(dataEl.dataset.previousBalance) || 0;
    var previousYear = dataEl.dataset.previousYear;
    var totalIncome = parseFloat(dataEl.dataset.totalIncome) || 0;
    var totalExpense = parseFloat(dataEl.dataset.totalExpense) || 0;
    var finalBalance = parseFloat(dataEl.dataset.finalBalance) || 0;
    var movements = JSON.parse(dataEl.dataset.movements || '[]');

    var wb = XLSX.utils.book_new();
    var rows = [];

    // Header rows
    rows.push(['LIBRO DE BANCOS VIGENCIA ' + year]);
    rows.push(['INSTITUCION EDUCATIVA: ' + schoolName]);
    rows.push(['NIT: ' + schoolNit + '     MUNICIPIO: ' + schoolMunicipality]);
    rows.push([]);
    rows.push(['ENTIDAD', bankName]);
    rows.push(['N. CUENTA', accountNumber]);
    rows.push(['TIPO CUENTA', accountType]);
    rows.push(['NOMBRE CTA', holderName]);
    rows.push([]);

    // Table header
    rows.push(['FECHA', 'DETALLE', 'No CONSIG', 'VALOR INGRESO', 'No CHEQUE', 'VALOR EGRESO', 'NUEVO SALDO']);

    // Saldo anterior
    rows.push(['31/12/' + previousYear, 'SALDO ANTERIOR A 31/12/' + previousYear, '', '', '', '', previousBalance]);

    // Movimientos
    for (var i = 0; i < movements.length; i++) {
        var m = movements[i];
        var dateStr = m.date || '';
        if (dateStr) {
            var parts = dateStr.split('-');
            dateStr = parts[2] + '/' + parts[1] + '/' + parts[0];
        }
        if (m.type === 'income') {
            rows.push([dateStr, m.detail, m.income_ref || '', m.income_amount, '', '', m.balance]);
        } else {
            rows.push([dateStr, m.detail, '', '', m.expense_ref || '', m.expense_amount, m.balance]);
        }
    }

    // Totales
    rows.push(['', 'TOTALES', '', totalIncome, '', totalExpense, finalBalance]);

    var ws = XLSX.utils.aoa_to_sheet(rows);

    // Column widths
    ws['!cols'] = [
        { wch: 18 }, // Fecha
        { wch: 55 }, // Detalle
        { wch: 12 }, // No Consig
        { wch: 18 }, // Valor Ingreso
        { wch: 12 }, // No Cheque
        { wch: 18 }, // Valor Egreso
        { wch: 18 }, // Nuevo Saldo
    ];

    // Merges for header
    ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 6 } },
        { s: { r: 1, c: 0 }, e: { r: 1, c: 6 } },
        { s: { r: 2, c: 0 }, e: { r: 2, c: 6 } },
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Libro de Bancos');
    XLSX.writeFile(wb, 'Libro_Bancos_' + bankName.replace(/\s+/g, '_') + '_' + accountNumber + '_' + year + '.xlsx');
};
</script>
@endpush
