<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Acuerdos (Ingresos) - Contraloría</h1>
                <p class="text-gray-500 mt-1">Movimientos presupuestales de ingreso: adiciones y reducciones</p>
            </div>
            @can('reports.export')
            <button id="btn-export-income-agreements"
                class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500">NIT (Sin DV):</span> <span class="font-semibold">{{ preg_replace('/-\d+$/', '', $school->nit ?? '') }}</span></div>
                <div><span class="text-gray-500">Institución:</span> <span class="font-semibold">{{ $school->name }}</span></div>
                <div><span class="text-gray-500">Dirección:</span> <span class="font-semibold">{{ $school->address ?? 'ND' }}</span></div>
                <div><span class="text-gray-500">Vigencia:</span> <span class="font-semibold">{{ $filterYear }}</span></div>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- Tarjetas resumen --}}
        @php
            $totalAdicion   = collect($rows)->sum('adicion');
            $totalReduccion = collect($rows)->sum('reduccion');
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Registros</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($rows) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Adiciones</p>
                <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($totalAdicion, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Reducciones</p>
                <p class="text-2xl font-bold text-red-600 mt-1">${{ number_format($totalReduccion, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Movimientos — {{ count($rows) }} registro(s)</h2>
            </div>

            @if(count($rows) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Código Rubro</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Acto Administrativo</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Fecha</th>
                            <th class="px-3 py-3 text-right font-semibold text-green-700 uppercase tracking-wider whitespace-nowrap">Adición</th>
                            <th class="px-3 py-3 text-right font-semibold text-red-700 uppercase tracking-wider whitespace-nowrap">Reducción</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-gray-800 whitespace-nowrap">{{ $row['codigo_rubro'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $row['acto_adm'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600">{{ $row['fecha'] }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap {{ $row['adicion'] > 0 ? 'font-semibold text-green-600' : 'text-gray-400' }}">
                                {{ $row['adicion'] > 0 ? '$'.number_format($row['adicion'], 0, ',', '.') : '0' }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap {{ $row['reduccion'] > 0 ? 'font-semibold text-red-600' : 'text-gray-400' }}">
                                {{ $row['reduccion'] > 0 ? '$'.number_format($row['reduccion'], 0, ',', '.') : '0' }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 max-w-xs truncate" title="{{ $row['motivo'] }}">{{ $row['motivo'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr class="font-bold text-xs">
                            <td colspan="3" class="px-3 py-3 text-gray-700">TOTALES</td>
                            <td class="px-3 py-3 text-right text-green-700">${{ number_format($totalAdicion, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right text-red-700">${{ number_format($totalReduccion, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="text-center py-16 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm">No hay movimientos de ingreso para la vigencia <strong>{{ $filterYear }}</strong>.</p>
            </div>
            @endif
        </div>

    </div>

    {{-- Datos para exportación --}}
    <div id="income-agreements-data" class="hidden"
        data-school="{{ $school->name }}"
        data-year="{{ $filterYear }}"
        data-rows="{{ json_encode($rows) }}"
    ></div>

    @push('scripts')
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btn-export-income-agreements');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const el     = document.getElementById('income-agreements-data');
            const school = el.dataset.school;
            const year   = el.dataset.year;
            const rows   = JSON.parse(el.dataset.rows);

            const headers = [
                'Código Rubro Presupuestal',
                'Acto Administrativo',
                'Fecha',
                'Adición',
                'Reducción',
                'Motivo',
            ];

            const dataRows = rows.map(r => [
                r.codigo_rubro,
                r.acto_adm,
                r.fecha,
                r.adicion,
                r.reduccion,
                r.motivo,
            ]);

            const ws = XLSX.utils.aoa_to_sheet([headers].concat(dataRows));

            // Formato numérico columnas D–E (índices 3–4)
            const numFmt = '#,##0';
            const range  = XLSX.utils.decode_range(ws['!ref']);
            for (let R = 1; R <= range.e.r; R++) {
                for (let C = 3; C <= 4; C++) {
                    const addr = XLSX.utils.encode_cell({ r: R, c: C });
                    if (ws[addr]) ws[addr].z = numFmt;
                }
            }

            ws['!cols'] = [
                { wch: 22 },
                { wch: 20 },
                { wch: 12 },
                { wch: 15 },
                { wch: 15 },
                { wch: 50 },
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Acuerdos Ingresos');

            const safeName = school.replace(/[^a-zA-Z0-9]/g, '_').substring(0, 30);
            XLSX.writeFile(wb, `Contraloria_Acuerdos_Ingresos_${safeName}_${year}.xlsx`);
        });
    });
    </script>
    @endpush
</div>
