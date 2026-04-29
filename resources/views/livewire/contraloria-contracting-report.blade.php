<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Resumen de Contratación - Contraloría</h1>
                <p class="text-gray-500 mt-1">Formato de reporte anual para ente de control</p>
            </div>
            @can('reports.export')
            <button id="btn-export-contraloria"
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
            <div class="flex items-center gap-4">
                <div class="w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mt-5 text-sm text-gray-500">
                    Presupuesto total del sujeto vigilado:
                    <span class="font-bold text-gray-800">${{ number_format($totalBudget, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Tarjetas resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Contratos</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($rows) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total Contratado</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">${{ number_format(collect($rows)->sum('valor_inicial'), 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Pagos Efectuados</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format(collect($rows)->sum('valor_pagos'), 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla de previsualización --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Contratos registrados — {{ count($rows) }} registro(s)</h2>
                <p class="text-xs text-gray-400 mt-1">Vista previa de columnas clave. El Excel exporta las {{ 67 }} columnas del formato Contraloría.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[11px]">
                        <tr>
                            <th class="px-3 py-3 text-left">N° Contrato</th>
                            <th class="px-3 py-3 text-left">Contratista</th>
                            <th class="px-3 py-3 text-left">Cédula/NIT</th>
                            <th class="px-3 py-3 text-left">Fuente Recurso</th>
                            <th class="px-3 py-3 text-left">Modalidad</th>
                            <th class="px-3 py-3 text-right">Valor Inicial</th>
                            <th class="px-3 py-3 text-left">Fecha Inicio</th>
                            <th class="px-3 py-3 text-left">Fecha Fin</th>
                            <th class="px-3 py-3 text-right">Pagos ($)</th>
                            <th class="px-3 py-3 text-center">Adición</th>
                            <th class="px-3 py-3 text-center">Prórroga</th>
                            <th class="px-3 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2.5 font-medium text-gray-900">{{ $row['no_rp'] !== 'ND' ? 'RP '.$row['no_rp'] : '' }} / CDP {{ $row['no_cdp'] }}</td>
                            <td class="px-3 py-2.5">{{ $row['nombre_contratista'] }} {{ $row['apellidos_contratista'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $row['cedula_contratista'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $row['fuente_recurso'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $row['modalidad_seleccion'] }}</td>
                            <td class="px-3 py-2.5 text-right font-medium">${{ number_format($row['valor_inicial'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $row['fecha_inicio'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $row['fecha_terminacion'] }}</td>
                            <td class="px-3 py-2.5 text-right text-emerald-600 font-medium">${{ number_format($row['valor_pagos'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $row['hubo_adicion'] === 'SI' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $row['hubo_adicion'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $row['hubo_prorroga'] === 'SI' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $row['hubo_prorroga'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $row['status_color'] }}-100 text-{{ $row['status_color'] }}-700">
                                    {{ $row['status_name'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="px-4 py-10 text-center text-gray-400">
                                No hay contratos registrados para la vigencia {{ $filterYear }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Data para JS --}}
        <div id="contraloria-data" class="hidden"
            data-school='@json(["nit" => preg_replace('/-\d+$/', '', $school->nit ?? ''), "name" => $school->name, "address" => $school->address ?? "", "municipality" => $school->municipality ?? ""])'
            data-year="{{ $filterYear }}"
            data-total-budget="{{ $totalBudget }}"
            data-rows='@json($rows)'>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    function getData() {
        var el = document.getElementById('contraloria-data');
        if (!el) return null;
        return {
            school:      JSON.parse(el.dataset.school   || '{}'),
            year:        el.dataset.year                || '',
            totalBudget: parseFloat(el.dataset.totalBudget) || 0,
            rows:        JSON.parse(el.dataset.rows     || '[]'),
        };
    }

    var btnExport = document.getElementById('btn-export-contraloria');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando librería...'); return; }
            var d = getData();
            if (!d || !d.rows.length) { alert('No hay datos para exportar.'); return; }

            var wb = XLSX.utils.book_new();

            // Cabeceras de las 67 columnas exactas
            var headers = [
                '(N) (N) Nit Sujeto Vigilados Sin Dígito De Verificación',
                '(C) (C) Nombre Del Sujeto Vigilado',
                '(C) (C) Direccion_ubicacion',
                '(N) (N) Presupuesto Del Sujeto Vigilado',
                '(C) (C) Número Del Contrato',
                '(C) (C) Regimen De Contratación',
                '(C) (C) Origen Del Presupuesto',
                '(C) (C) Fuente Del Recurso',
                '(C) (C) Modalidad De Selección',
                '(C) (C) Procedimiento',
                '(C) (C) Clase De Contrato',
                '(C) (C) Tipo De Gasto',
                '(C) (C) Sector Al Que Corresponde El Gasto',
                '(C) (C) Se Público En El Secop',
                '(C) (C) Se Actualizó En El Secop',
                '(C) (C) Objeto Del Contrato',
                '(N) (N) Valor Inicial Del Contrato',
                '(C) (C) No Cdp',
                '(F) (F) Fecha Cdp',
                '(C) (C) Rubro Del Cdp',
                '(D) (D) Valor Cdp',
                '(C) (C) No Rp',
                '(F) (F) Fecha Rp',
                '(D) (D) Valor Rp',
                '(C) (C) Rubro Del Rp',
                '(C) (C) Poliza No',
                '(F) (F) Fecha De La Poliza',
                '(C) (C) Nombre De La Aseguardora',
                '(N) (N) Cédula Nit Del Contratista',
                '(C) (C) Nombre Del Contratista',
                '(C) (C) Apellidos Del Contratista',
                '(C) (C) Persona Natural O Jurídica',
                '(F) (F) Fecha De Suscripción Del Contrato',
                '(N) (N) Cédula Nit Del Interventor O Supervisor',
                '(C) (C) Nombres Del Interventor O Supervisor',
                '(C) (C) Apellidos Del Interventor O Supervisor',
                '(C) (C) Tipo De Vinculación Interventor O Supervisor',
                '(C) (C) Plazo De Ejecución Unidad De Ejecución',
                '(N) (N) Plazo De Ejecución Número De Unidades',
                '(C) (C) Se Pactó Anticipo Al Contrato',
                '(N) (N) Valor De Los Anticipos',
                '(C) (C) Se Constituyo Fiducia',
                '(C) (C) Se Realizo Adicion',
                '(N) (N) Adiciones Realizadas',
                '(D) (D) Valor Total De Las Adiciones En Pesos',
                '(C) (C) Se Realizo Prorroga',
                '(C) (C) Prorrogas Unidad De Ejecución',
                '(N) (N) Prorrogas Número De Unidades',
                '(F) (F) Fecha Inicio Del Contrato',
                '(F) (F) Fecha Terminación Del Contrato',
                '(N) (N) Pagos Efectuados',
                '(N) (N) Valor Pagos Efectuados',
                '(F) (F) Fecha De Suscripción Del Acta De Liquidación',
                '(C) (C) Acto Administrativo Urgencia Manifiesta Número Del Acto Que La Decreta',
                '(F) (F) Acto Administrativo Urgencia Manifiesta Fecha Del Acto',
                '(N) (N) Valor De Los Recursos Comprometdosclasificados Por Fuente Recursos Propios En Pesos',
                '(N) (N) Valor De Los Recursos Comprometidosclasificados Por Fuente Regalías En Pesos',
                '(N) (N) Valor De Los Recursos Comprometidosclasificados Por Fuentesgpen Pesos',
                '(N) (N) Valor De Los Recursos Comprometdosclasificados Por Fuentefnc Colombia Humanitaria En Pesos',
                '(F) (F) Fecha De Autorizacion De La Vigencia Futura',
                '(N) (N) Vigencias Futuras Autorizada Año Inicia',
                '(N) (N) Vigencias Futuras Autorizada Año Final',
                '(N) (N) Monto Total De La Vigencia Futura Autorizada',
                '(N) (N) Monto De La V.F Apropiado En La Vigencia Inicial',
                '(N) (N) Monto De La Vigencia Futura Ejecutada En La Vigencia Que Se Reporta',
                '(N) (N) Saldo Total De La Vigencias Futuras Por Comprometer',
                '(C) (C) Observaciones'
            ];

            var dataRows = d.rows.map(function(r) {
                return [
                    r.nit_sujeto,
                    r.nombre_sujeto,
                    r.direccion,
                    r.presupuesto_sujeto,
                    r.numero_contrato,
                    r.regimen_contratacion,
                    r.origen_presupuesto,
                    r.fuente_recurso,
                    r.modalidad_seleccion,
                    r.procedimiento,
                    r.clase_contrato,
                    r.tipo_gasto,
                    r.sector,
                    r.publicado_secop,
                    r.actualizado_secop,
                    r.objeto,
                    r.valor_inicial,
                    r.no_cdp,
                    r.fecha_cdp,
                    r.rubro_cdp,
                    r.valor_cdp,
                    r.no_rp,
                    r.fecha_rp,
                    r.valor_rp,
                    r.rubro_rp,
                    r.poliza_no,
                    r.fecha_poliza,
                    r.aseguradora,
                    r.cedula_contratista,
                    r.nombre_contratista,
                    r.apellidos_contratista,
                    r.persona_tipo,
                    r.fecha_suscripcion,
                    r.cedula_supervisor,
                    r.nombre_supervisor,
                    r.apellido_supervisor,
                    r.vinculacion_supervisor,
                    r.plazo_unidad,
                    r.plazo_numero,
                    r.anticipo,
                    r.valor_anticipo,
                    r.fiducia,
                    r.hubo_adicion,
                    r.num_adiciones,
                    r.valor_adiciones,
                    r.hubo_prorroga,
                    r.prorroga_unidad,
                    r.prorroga_numero,
                    r.fecha_inicio,
                    r.fecha_terminacion,
                    r.num_pagos,
                    r.valor_pagos,
                    r.fecha_acta_liquidacion,
                    r.urgencia_numero,
                    r.urgencia_fecha,
                    r.valor_propios,
                    r.valor_regalias,
                    r.valor_sgp,
                    r.valor_fnc,
                    r.fecha_aut_vf,
                    r.vf_anio_inicio,
                    r.vf_anio_fin,
                    r.vf_monto_total,
                    r.vf_apropiado,
                    r.vf_ejecutada,
                    r.vf_saldo,
                    r.observaciones
                ];
            });

            var all = [headers].concat(dataRows);
            var ws = XLSX.utils.aoa_to_sheet(all);

            // Anchos de columna
            ws['!cols'] = [
                {wch:20},{wch:35},{wch:30},{wch:20},{wch:30},
                {wch:15},{wch:15},{wch:20},{wch:20},{wch:12},
                {wch:15},{wch:18},{wch:15},{wch:10},{wch:10},
                {wch:60},{wch:18},{wch:10},{wch:14},{wch:35},
                {wch:15},{wch:10},{wch:14},{wch:15},{wch:35},
                {wch:12},{wch:14},{wch:25},{wch:18},{wch:20},
                {wch:25},{wch:12},{wch:16},{wch:18},{wch:20},
                {wch:25},{wch:15},{wch:10},{wch:10},{wch:8},
                {wch:15},{wch:8},{wch:10},{wch:10},{wch:18},
                {wch:10},{wch:10},{wch:10},{wch:14},{wch:14},
                {wch:10},{wch:18},{wch:16},{wch:30},{wch:14},
                {wch:18},{wch:18},{wch:18},{wch:18},{wch:14},
                {wch:12},{wch:12},{wch:18},{wch:18},{wch:18},
                {wch:18},{wch:25}
            ];

            // Formato numérico en columnas de valor (índices 0-based)
            // Cols: 3,16,20,23,40,44,51,55,56,57,58 (presupuesto, valores, pagos, recursos)
            var numericCols = [3,16,20,23,40,44,51,55,56,57,58];
            for (var ri = 1; ri < all.length; ri++) {
                numericCols.forEach(function(ci) {
                    var cellAddr = XLSX.utils.encode_cell({r: ri, c: ci});
                    if (ws[cellAddr] && typeof ws[cellAddr].v === 'number') {
                        ws[cellAddr].z = '#,##0';
                    }
                });
            }

            // Estilo de la cabecera (fila 0) — fondo azul si el entorno lo soporta
            var headerStyle = { font: { bold: true }, fill: { fgColor: { rgb: '1D4ED8' } } };
            for (var ci = 0; ci < headers.length; ci++) {
                var cellAddr = XLSX.utils.encode_cell({r: 0, c: ci});
                if (ws[cellAddr]) {
                    ws[cellAddr].s = headerStyle;
                }
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Resumen Contratación');
            var schoolSlug = (d.school.name || 'Colegio').replace(/[^a-zA-Z0-9]/g, '_');
            XLSX.writeFile(wb, 'Contraloria_Contratacion_' + schoolSlug + '_' + d.year + '.xlsx');
        });
    }

    // Re-bind after Livewire navigate
    document.addEventListener('livewire:navigated', function() {
        var btn = document.getElementById('btn-export-contraloria');
        if (btn) btn.dispatchEvent(new Event('click'));
    });
});
</script>
@endpush
