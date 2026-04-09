<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Reporte SIFSE</h1>
                <p class="text-gray-500 mt-1">Informe presupuestal para el Sistema de Informaci&oacute;n de los FSE</p>
            </div>
            @can('reports.export')
            <button id="btn-export-sifse" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">REPORTE SIFSE</span></div>
                <div><span class="text-gray-500">C&Oacute;DIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FONDO:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">PAGADOR:</span> <span class="font-semibold text-gray-900">{{ $school->pagador_name ?? 'N/A' }}</span></div>
                <div class="lg:col-span-3"><span class="text-gray-500">PERIODO:</span> <span class="font-semibold text-gray-900">{{ $this->periodLabel }}</span></div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                    <select wire:model.live="filterTrimester" class="w-full rounded-xl border-gray-300">
                        <option value="1">1 - Primer Trimestre (Ene-Mar)</option>
                        <option value="2">2 - Segundo Trimestre (Abr-Jun)</option>
                        <option value="3">3 - Tercer Trimestre (Jul-Sep)</option>
                        <option value="4">4 - Cuarto Trimestre (Oct-Dic)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mb-6">
            <div class="flex space-x-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-1.5">
                <button
                    wire:click="setTab('expenses')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl transition-colors {{ $activeTab === 'expenses' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Gastos Presupuestales
                </button>
                <button
                    wire:click="setTab('incomes')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl transition-colors {{ $activeTab === 'incomes' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Ingresos Presupuestales
                </button>
            </div>
        </div>

        {{-- Tab: Gastos Presupuestales --}}
        @if($activeTab === 'expenses')
            {{-- Tarjetas resumen --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Presupuesto Inicial</p>
                    <p class="text-xl font-bold text-gray-900">${{ number_format($expenseTotals['initial'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Presupuesto Definitivo</p>
                    <p class="text-xl font-bold text-blue-600">${{ number_format($expenseTotals['definitive'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Compromisos</p>
                    <p class="text-xl font-bold text-indigo-600">${{ number_format($expenseTotals['commitments'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Obligaciones</p>
                    <p class="text-xl font-bold text-amber-600">${{ number_format($expenseTotals['obligations'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Pagos</p>
                    <p class="text-xl font-bold text-emerald-600">${{ number_format($expenseTotals['payments'] ?? 0, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Tabla Gastos --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">REPORTE GASTOS PRESUPUESTALES</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">C&oacute;digo Establecimiento</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase">A&ntilde;o</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase">Trimestre</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Fuente de Ingreso</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Item Detalle</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Presupuesto Inicial</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Presupuesto Definitivo</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase">Compromisos</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase">Obligaciones</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase">Pagos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($expenseRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2.5 whitespace-nowrap text-gray-900">{{ $row['dane_code'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['year'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['trimester'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['funding_source_code'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['sifse_code'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['initial'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['definitive'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['commitments'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['obligations'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['payments'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">No hay datos de gastos para esta vigencia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($expenseRows) > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="5" class="px-3 py-3 text-right text-gray-700 uppercase">Totales</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($expenseTotals['initial'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($expenseTotals['definitive'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($expenseTotals['commitments'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($expenseTotals['obligations'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($expenseTotals['payments'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif

        {{-- Tab: Ingresos Presupuestales --}}
        @if($activeTab === 'incomes')
            {{-- Tarjetas resumen --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Presupuesto Inicial</p>
                    <p class="text-xl font-bold text-gray-900">${{ number_format($incomeTotals['initial'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Presupuesto Definitivo</p>
                    <p class="text-xl font-bold text-blue-600">${{ number_format($incomeTotals['definitive'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Monto Recaudado</p>
                    <p class="text-xl font-bold text-emerald-600">${{ number_format($incomeTotals['collected'] ?? 0, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Tabla Ingresos --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">REPORTE INGRESOS PRESUPUESTALES</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">C&oacute;digo Establecimiento</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase">A&ntilde;o</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase">Trimestre</th>
                                <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Fuente de Ingreso</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Presupuesto Inicial</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Presupuesto Definitivo</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Monto Recaudados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($incomeRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2.5 whitespace-nowrap text-gray-900">{{ $row['dane_code'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['year'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['trimester'] }}</td>
                                    <td class="px-3 py-2.5 text-center text-gray-900">{{ $row['funding_source_code'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['initial'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['definitive'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-900">{{ number_format($row['collected'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay datos de ingresos para esta vigencia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($incomeRows) > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="4" class="px-3 py-3 text-right text-gray-700 uppercase">Totales</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($incomeTotals['initial'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($incomeTotals['definitive'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-900">{{ number_format($incomeTotals['collected'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Hidden data for JS export --}}
    <div class="hidden"
        id="sifse-data"
        data-school-name="{{ $school->name ?? '' }}"
        data-school-dane="{{ $school->dane_code ?? '' }}"
        data-school-municipality="{{ $school->municipality ?? '' }}"
        data-school-rector="{{ $school->rector_name ?? '' }}"
        data-school-pagador="{{ $school->pagador_name ?? '' }}"
        data-year="{{ $filterYear }}"
        data-trimester="{{ $filterTrimester }}"
        data-expense-rows="{{ json_encode($expenseRows) }}"
        data-income-rows="{{ json_encode($incomeRows) }}"
        data-expense-totals="{{ json_encode($expenseTotals) }}"
        data-income-totals="{{ json_encode($incomeTotals) }}"
    ></div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
function exportSifseExcel() {
    var el = document.getElementById('sifse-data');
    if (!el) return;

    var schoolName = el.dataset.schoolName;
    var dane = el.dataset.schoolDane;
    var year = el.dataset.year;
    var trimester = el.dataset.trimester;
    var expenseRows = JSON.parse(el.dataset.expenseRows || '[]');
    var incomeRows = JSON.parse(el.dataset.incomeRows || '[]');
    var expenseTotals = JSON.parse(el.dataset.expenseTotals || '{}');
    var incomeTotals = JSON.parse(el.dataset.incomeTotals || '{}');

    var wb = XLSX.utils.book_new();

    // --- Sheet 1: Gastos Presupuestales ---
    var expData = [
        ['REPORTE GASTOS PRESUPUESTALES'],
        ['Establecimiento: ' + schoolName, '', '', 'DANE: ' + dane, '', 'Vigencia: ' + year, 'Trimestre: ' + trimester],
        [],
        ['Codigo Establecimiento', 'Ano', 'Trimestre', 'Fuente de Ingreso', 'Item Detalle', 'Presupuesto inicial', 'Presupuesto definitivo', 'Compromisos', 'Obligaciones', 'Pagos']
    ];

    for (var i = 0; i < expenseRows.length; i++) {
        var r = expenseRows[i];
        expData.push([
            r.dane_code, parseInt(r.year), parseInt(r.trimester),
            isNaN(r.funding_source_code) ? r.funding_source_code : parseInt(r.funding_source_code),
            isNaN(r.sifse_code) ? r.sifse_code : parseInt(r.sifse_code),
            parseFloat(r.initial), parseFloat(r.definitive),
            parseFloat(r.commitments), parseFloat(r.obligations), parseFloat(r.payments)
        ]);
    }

    expData.push([
        '', '', '', '', 'TOTALES',
        parseFloat(expenseTotals.initial || 0), parseFloat(expenseTotals.definitive || 0),
        parseFloat(expenseTotals.commitments || 0), parseFloat(expenseTotals.obligations || 0),
        parseFloat(expenseTotals.payments || 0)
    ]);

    var wsExp = XLSX.utils.aoa_to_sheet(expData);
    wsExp['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 9 } }];
    wsExp['!cols'] = [
        { wch: 22 }, { wch: 6 }, { wch: 10 }, { wch: 18 }, { wch: 12 },
        { wch: 18 }, { wch: 22 }, { wch: 16 }, { wch: 16 }, { wch: 16 }
    ];
    XLSX.utils.book_append_sheet(wb, wsExp, 'Gastos Presupuestales');

    // --- Sheet 2: Ingresos Presupuestales ---
    var incData = [
        ['REPORTE INGRESOS PRESUPUESTALES'],
        ['Establecimiento: ' + schoolName, '', 'DANE: ' + dane, '', 'Vigencia: ' + year, 'Trimestre: ' + trimester],
        [],
        ['Codigo Establecimiento', 'Ano', 'Trimestre', 'Fuente de ingreso', 'Presupuesto inicial', 'Presupuesto definitivo', 'Monto recaudados']
    ];

    for (var j = 0; j < incomeRows.length; j++) {
        var ir = incomeRows[j];
        incData.push([
            ir.dane_code, parseInt(ir.year), parseInt(ir.trimester),
            isNaN(ir.funding_source_code) ? ir.funding_source_code : parseInt(ir.funding_source_code),
            parseFloat(ir.initial), parseFloat(ir.definitive), parseFloat(ir.collected)
        ]);
    }

    incData.push([
        '', '', '', 'TOTALES',
        parseFloat(incomeTotals.initial || 0), parseFloat(incomeTotals.definitive || 0),
        parseFloat(incomeTotals.collected || 0)
    ]);

    var wsInc = XLSX.utils.aoa_to_sheet(incData);
    wsInc['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }];
    wsInc['!cols'] = [
        { wch: 22 }, { wch: 6 }, { wch: 10 }, { wch: 18 },
        { wch: 18 }, { wch: 22 }, { wch: 18 }
    ];
    XLSX.utils.book_append_sheet(wb, wsInc, 'Ingresos Presupuestales');

    XLSX.writeFile(wb, 'Reporte_SIFSE_' + dane + '_' + year + '_T' + trimester + '.xlsx');
}

document.addEventListener('livewire:navigated', function () {
    var btn = document.getElementById('btn-export-sifse');
    if (btn) btn.onclick = exportSifseExcel;
});

var btnInit = document.getElementById('btn-export-sifse');
if (btnInit) btnInit.onclick = exportSifseExcel;
</script>
@endpush
