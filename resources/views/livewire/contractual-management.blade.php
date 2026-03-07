<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($currentView === 'list')
            {{-- ==================== VISTA LISTADO ==================== --}}

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Etapa Contractual</h1>
                    <p class="text-gray-500 mt-1">Gestión de contratos y registros presupuestales (RP)</p>
                </div>
                @can('contractual.create')
                    <button wire:click="openCreateView" class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo Contrato
                    </button>
                @endcan
            </div>

            {{-- Resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Borrador</p>
                    <p class="text-2xl font-bold text-gray-500">{{ $summary['draft'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-blue-500 uppercase">Activos</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $summary['active'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-yellow-500 uppercase">En Ejecución</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $summary['in_execution'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-green-500 uppercase">Finalizados</p>
                    <p class="text-2xl font-bold text-green-600">{{ $summary['completed'] }}</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="N° contrato, objeto, proveedor...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal</label>
                        <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                            @for($y = date('Y') + 1; $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                            <option value="">Todos</option>
                            @foreach(\App\Models\Contract::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="$set('search', '')" class="w-full px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla de Contratos --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Objeto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">RPs</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fechas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($contracts as $ct)
                                <tr class="hover:bg-gray-50" wire:key="ct-{{ $ct->id }}">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-indigo-600">{{ $ct->formatted_number }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs">
                                            <p class="font-medium text-gray-900 truncate">{{ Str::limit($ct->object, 50) }}</p>
                                            <p class="text-xs text-gray-500">{{ \App\Models\Contract::MODALITIES[$ct->contracting_modality] ?? '' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $ct->supplier?->full_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $ct->supplier?->full_document }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold">${{ number_format($ct->total, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            {{ $ct->rps->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $colors = [
                                                'draft' => 'bg-gray-100 text-gray-700',
                                                'active' => 'bg-blue-100 text-blue-700',
                                                'in_execution' => 'bg-yellow-100 text-yellow-700',
                                                'completed' => 'bg-green-100 text-green-700',
                                                'terminated' => 'bg-red-100 text-red-700',
                                                'annulled' => 'bg-red-100 text-red-700',
                                                'suspended' => 'bg-orange-100 text-orange-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $colors[$ct->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $ct->status_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-gray-500">
                                        <div>{{ $ct->start_date?->format('d/m/Y') }}</div>
                                        <div>{{ $ct->end_date?->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="viewDetail({{ $ct->id }})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver detalle">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            @if($ct->status === 'draft')
                                                @can('contractual.delete')
                                                    <button wire:click="viewDetail({{ $ct->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="mt-2">No hay contratos para este período</p>
                                        <p class="text-sm text-gray-400 mt-1">Cree un contrato desde una convocatoria adjudicada</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($contracts->hasPages())
                    <div class="px-6 py-4 border-t">{{ $contracts->links() }}</div>
                @endif
            </div>

        @elseif($currentView === 'create')
            {{-- ==================== VISTA CREAR CONTRATO ==================== --}}

            {{-- Breadcrumb --}}
            <div class="mb-6">
                <button wire:click="backToList" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver al listado
                </button>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Nuevo Contrato</h1>
                <span class="text-sm text-gray-500">Etapa Contractual</span>
            </div>

            <form wire:submit.prevent="saveContract" class="space-y-6">

                {{-- ─── SECCIÓN 1: Seleccionar Convocatoria ────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Seleccione la Convocatoria
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Convocatoria Adjudicada *</label>
                            <select wire:model.live="selectedConvocatoriaId" wire:change="onConvocatoriaSelected" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Seleccione --</option>
                                @foreach($awardedConvocatorias as $ac)
                                    <option value="{{ $ac['id'] }}">#{{ $ac['number'] }} - {{ Str::limit($ac['object'], 60) }}</option>
                                @endforeach
                            </select>
                            @if(empty($awardedConvocatorias))
                                <p class="text-xs text-amber-600 mt-1">No hay convocatorias adjudicadas disponibles para este año.</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contrato N°</label>
                            <input type="text" value="{{ str_pad($contractNumber, 4, '0', STR_PAD_LEFT) }}" class="w-full rounded-xl border-gray-300 bg-gray-50" disabled>
                            <p class="text-xs text-gray-400 mt-1">Número consecutivo por defecto</p>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 2: Datos Generales del Contrato ────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Datos Generales del Contrato
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad de Contratación *</label>
                            <select wire:model="contractingModality" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Seleccione --</option>
                                @foreach(\App\Models\Contract::MODALITIES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Ejecución</label>
                            <input type="text" wire:model="executionPlace" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: Sede principal del colegio">
                        </div>
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.startInput, {
                                    dateFormat: 'Y-m-d',
                                    disable: [function(date) { return date.getDay() === 0 || date.getDay() === 6; }],
                                    onChange: (selectedDates, dateStr) => { $wire.set('startDate', dateStr); }
                                });
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                            <input type="text" x-ref="startInput" value="{{ $startDate }}" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white" placeholder="Seleccionar fecha..." readonly>
                        </div>
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.endInput, {
                                    dateFormat: 'Y-m-d',
                                    disable: [function(date) { return date.getDay() === 0 || date.getDay() === 6; }],
                                    onChange: (selectedDates, dateStr) => { $wire.set('endDate', dateStr); }
                                });
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Terminación *</label>
                            <input type="text" x-ref="endInput" value="{{ $endDate }}" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white" placeholder="Seleccionar fecha..." readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Duración del Contrato (días hábiles)</label>
                            <input type="number" value="{{ $durationDays }}" class="w-full rounded-xl border-gray-300 bg-gray-50" disabled>
                            <p class="text-xs text-gray-400 mt-1">Solo días hábiles (lunes a viernes)</p>
                        </div>
                    </div>

                    {{-- Objeto y Justificación (auto) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Objeto a Contratar</label>
                            <textarea wire:model="contractObject" rows="3" class="w-full rounded-xl border-gray-300 bg-blue-50 text-gray-700" readonly></textarea>
                            <p class="text-xs text-blue-500 mt-1">Automático de la convocatoria (informativo)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Necesidad a Satisfacer</label>
                            <textarea wire:model="contractJustification" rows="3" class="w-full rounded-xl border-gray-300 bg-blue-50 text-gray-700" readonly></textarea>
                            <p class="text-xs text-blue-500 mt-1">Automático de la convocatoria (informativo)</p>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 3: Datos del Proveedor (auto) ──────────── --}}
                @if(!empty($supplierData))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Datos del Proveedor
                        <span class="text-xs font-normal text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Automático</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nombre del Proveedor</label>
                            <p class="text-sm font-semibold text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['name'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Número de Documento</label>
                            <p class="text-sm font-semibold text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['document'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Dirección</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['address'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Municipio</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['municipality'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['phone'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Régimen Tributario</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['tax_regime'] ?? 'N/D' }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ─── SECCIÓN 4: Supervisor ──────────────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Datos del Supervisor
                    </h2>
                    <div class="max-w-md">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seleccione al Supervisor de este Contrato</label>
                        <select wire:model="supervisorId" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Sin supervisor --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup['id'] }}">{{ $sup['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ─── SECCIÓN 5: Valor del Contrato ──────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Valor del Contrato
                        <span class="text-xs font-normal text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Desde propuesta ganadora</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model="contractSubtotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 bg-gray-50" readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model="contractIva" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 bg-gray-50" readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model="contractTotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 font-bold bg-gray-50" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 6: CDPs y Creación de RPs ──────────────── --}}
                @if(count($cdpsData) > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        Creación de RPs según CDPs Creados
                    </h2>
                    <p class="text-sm text-gray-500 mb-6">Asigne los Registros Presupuestales (RP) para cada CDP de la convocatoria. Los montos no deben exceder el disponible del CDP.</p>

                    @foreach($cdpsData as $index => $cdp)
                        <div class="border border-gray-200 rounded-xl p-5 mb-5 {{ !$loop->last ? '' : '' }}" wire:key="cdp-section-{{ $cdp['id'] }}">
                            {{-- Header CDP --}}
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                                <div>
                                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-bold">{{ $cdp['cdp_number'] }}</span>
                                        CDP N° {{ $cdp['cdp_number'] }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <span class="font-medium">Rubro:</span> {{ $cdp['budget_item_code'] }} - {{ $cdp['budget_item'] }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Total CDP</p>
                                    <p class="text-lg font-bold text-indigo-600">${{ number_format($cdp['total_amount'], 0, ',', '.') }}</p>
                                </div>
                            </div>

                            {{-- Fuentes del CDP (informativo) --}}
                            <div class="bg-blue-50 rounded-xl p-4 mb-4">
                                <p class="text-xs font-medium text-blue-700 uppercase mb-2">Disponible por Fuente de Financiación</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    @foreach($cdp['funding_sources'] as $fs)
                                        <div class="bg-white rounded-lg p-3 border border-blue-100">
                                            <p class="text-xs text-gray-500">{{ $fs['name'] }}</p>
                                            <p class="text-sm font-semibold text-gray-900">${{ number_format($fs['amount'], 0, ',', '.') }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Asignación de RP --}}
                            <div class="bg-amber-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <p class="text-sm font-semibold text-amber-800">Asignación de RP</p>
                                </div>

                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">N° RP Asignado a este CDP</label>
                                    <input type="text" value="{{ str_pad($rpAssignments[$cdp['id']]['rp_number'] ?? 0, 4, '0', STR_PAD_LEFT) }}" class="w-40 rounded-lg border-gray-300 bg-white text-sm" disabled>
                                    <span class="text-xs text-gray-400 ml-2">Consecutivo automático</span>
                                </div>

                                <p class="text-xs font-medium text-gray-600 mb-2">Seleccione fuente de financiación y asigne valor a contratar:</p>

                                @if(isset($rpAssignments[$cdp['id']]))
                                    @foreach($rpAssignments[$cdp['id']]['funding_sources'] as $fsIndex => $rpFs)
                                        <div class="bg-white rounded-lg p-4 mb-3 border border-amber-200" wire:key="rp-fs-{{ $cdp['id'] }}-{{ $fsIndex }}">
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Fuente de Financiación</label>
                                                    <p class="text-sm font-medium text-gray-900 bg-gray-50 rounded-lg px-3 py-2">{{ $rpFs['name'] }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Valor a Contratar *</label>
                                                    <div class="relative">
                                                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">$</span>
                                                        <input type="number" step="0.01" wire:model="rpAssignments.{{ $cdp['id'] }}.funding_sources.{{ $fsIndex }}.amount"
                                                            class="w-full rounded-lg border-gray-300 pl-6 text-sm bg-gray-50" readonly>
                                                    </div>
                                                    <p class="text-xs text-gray-400 mt-0.5">Valor desde propuesta ganadora</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Banco</label>
                                                    <select wire:model.live="rpAssignments.{{ $cdp['id'] }}.funding_sources.{{ $fsIndex }}.bank_id"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">— Seleccionar banco —</option>
                                                        @foreach($availableBanks as $bank)
                                                            <option value="{{ $bank['id'] }}">{{ $bank['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cuenta Bancaria</label>
                                                    <select wire:model="rpAssignments.{{ $cdp['id'] }}.funding_sources.{{ $fsIndex }}.bank_account_id"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">— Seleccionar cuenta —</option>
                                                        @foreach(($rpLineAccounts[$cdp['id'] . '_' . $fsIndex] ?? []) as $account)
                                                            <option value="{{ $account['id'] }}">{{ $account['account_number'] }} - {{ ucfirst($account['account_type']) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif

                {{-- ─── SECCIÓN 7: Forma de Pago ──────────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Seleccione Forma de Pago
                    </h2>
                    <div class="max-w-md">
                        <select wire:model="paymentMethod" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Models\Contract::PAYMENT_METHODS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ─── Botones de Acción ──────────────────────────────── --}}
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="backToList" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
                        <svg wire:loading wire:target="saveContract" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg wire:loading.remove wire:target="saveContract" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Crear Contrato
                    </button>
                </div>
            </form>

        @elseif($currentView === 'detail')
            {{-- ==================== VISTA DETALLE CONTRATO ==================== --}}
            @if($contract)
                {{-- Breadcrumb --}}
                <div class="mb-6">
                    <button wire:click="backToList" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver al listado
                    </button>
                </div>

                {{-- Header del Contrato --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-2xl font-bold text-gray-900">Contrato N° {{ $contract->formatted_number }}</h1>
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'active' => 'bg-blue-100 text-blue-700',
                                        'in_execution' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'terminated' => 'bg-red-100 text-red-700',
                                        'annulled' => 'bg-red-100 text-red-700',
                                        'suspended' => 'bg-orange-100 text-orange-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$contract->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $contract->status_name }}
                                </span>
                            </div>
                            <p class="text-gray-700 mb-2">{{ $contract->object }}</p>
                            @if($contract->justification)
                                <p class="text-sm text-gray-500">{{ $contract->justification }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                                <span><strong>Modalidad:</strong> {{ $contract->modality_name }}</span>
                                <span><strong>Lugar:</strong> {{ $contract->execution_place ?: 'N/D' }}</span>
                                <span><strong>Inicio:</strong> {{ $contract->start_date?->format('d/m/Y') }}</span>
                                <span><strong>Fin:</strong> {{ $contract->end_date?->format('d/m/Y') }}</span>
                                <span><strong>Duración:</strong> {{ $contract->duration_days }} días hábiles</span>
                                <span><strong>Año fiscal:</strong> {{ $contract->fiscal_year }}</span>
                            </div>
                            @if($contract->extension_days > 0)
                                <div class="mt-2 bg-indigo-50 rounded-lg px-3 py-2 text-sm">
                                    <span class="font-medium text-indigo-700">Prórroga:</span>
                                    <span class="text-indigo-600">+{{ $contract->extension_days }} días hábiles</span>
                                    @if($contract->original_end_date)
                                        <span class="text-gray-500 ml-2">(Fecha original: {{ $contract->original_end_date->format('d/m/Y') }})</span>
                                    @endif
                                    @if($contract->extension_document_path)
                                        <a href="{{ Storage::url($contract->extension_document_path) }}" target="_blank" class="ml-2 text-indigo-600 underline hover:text-indigo-800">Ver Otrosí</a>
                                    @endif
                                </div>
                            @endif
                            @if($contract->addition_amount > 0)
                                <div class="mt-2 bg-emerald-50 rounded-lg px-3 py-2 text-sm">
                                    <span class="font-medium text-emerald-700">Adición:</span>
                                    <span class="text-emerald-600">${{ number_format($contract->addition_amount, 0, ',', '.') }}</span>
                                    @if($contract->original_total)
                                        <span class="text-gray-500 ml-2">(Valor original: ${{ number_format($contract->original_total, 0, ',', '.') }})</span>
                                    @endif
                                    @if($contract->addition_document_path)
                                        <a href="{{ Storage::url($contract->addition_document_path) }}" target="_blank" class="ml-2 text-emerald-600 underline hover:text-emerald-800">Ver Otrosí</a>
                                    @endif
                                </div>
                            @endif
                            @if($contract->status === 'annulled')
                                <div class="mt-2 bg-red-50 rounded-lg px-3 py-2 text-sm">
                                    <span class="font-medium text-red-700">Anulado:</span>
                                    <span class="text-red-600">{{ $contract->annulment_reason }}</span>
                                    @if($contract->annulment_date)
                                        <span class="text-gray-500 ml-2">({{ $contract->annulment_date->format('d/m/Y H:i') }})</span>
                                    @endif
                                </div>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                                <span><strong>Convocatoria:</strong> #{{ $contract->convocatoria?->formatted_number }}</span>
                                <span><strong>Forma de pago:</strong> {{ $contract->payment_method_name }}</span>
                                <span><strong>Creado por:</strong> {{ $contract->creator?->name ?? 'N/D' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Valor total del contrato</p>
                            <p class="text-3xl font-bold text-indigo-600">${{ number_format($contract->total, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Subtotal: ${{ number_format($contract->subtotal, 0, ',', '.') }}
                                @if($contract->iva > 0) | IVA: ${{ number_format($contract->iva, 0, ',', '.') }} @endif
                            </p>

                            {{-- Botones de acción según estado --}}
                            <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                @can('contractual.edit')
                                    @if($contract->status === 'draft')
                                        <button wire:click="openStatusModal('active')" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            Activar Contrato
                                        </button>
                                    @elseif($contract->status === 'active')
                                        <button wire:click="openStatusModal('in_execution')" class="px-3 py-1.5 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                                            Pasar a Ejecución
                                        </button>
                                        <button wire:click="openExtensionModal" class="px-3 py-1.5 text-sm bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors">
                                            Prórroga
                                        </button>
                                        <button wire:click="openAdditionModal" class="px-3 py-1.5 text-sm bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors">
                                            Adición de Recursos
                                        </button>
                                        <button wire:click="openStatusModal('suspended')" class="px-3 py-1.5 text-sm bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors">
                                            Suspender
                                        </button>
                                        <button wire:click="openAnnulModal" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            Anular
                                        </button>
                                    @elseif($contract->status === 'in_execution')
                                        <button wire:click="openStatusModal('completed')" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            Finalizar
                                        </button>
                                        <button wire:click="openExtensionModal" class="px-3 py-1.5 text-sm bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors">
                                            Prórroga
                                        </button>
                                        <button wire:click="openAdditionModal" class="px-3 py-1.5 text-sm bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors">
                                            Adición de Recursos
                                        </button>
                                        <button wire:click="openStatusModal('suspended')" class="px-3 py-1.5 text-sm bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors">
                                            Suspender
                                        </button>
                                        <button wire:click="openAnnulModal" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            Anular
                                        </button>
                                    @elseif($contract->status === 'suspended')
                                        <button wire:click="openStatusModal('active')" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            Reactivar
                                        </button>
                                        <button wire:click="openStatusModal('in_execution')" class="px-3 py-1.5 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                                            Pasar a Ejecución
                                        </button>
                                        <button wire:click="openAnnulModal" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            Anular
                                        </button>
                                    @endif
                                @endcan
                                @can('contractual.delete')
                                    @if($contract->status === 'draft')
                                        <button wire:click="confirmDelete" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            Eliminar
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datos del Proveedor --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Datos del Proveedor
                    </h2>
                    @if($contract->supplier)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Nombre</p>
                                <p class="font-semibold text-gray-900">{{ $contract->supplier->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Documento</p>
                                <p class="font-semibold text-gray-900">{{ $contract->supplier->full_document }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Dirección</p>
                                <p class="text-gray-900">{{ $contract->supplier->address ?? 'No registrada' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Municipio</p>
                                <p class="text-gray-900">{{ $contract->supplier->city ?? 'No registrado' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Teléfono</p>
                                <p class="text-gray-900">{{ $contract->supplier->phone ?? $contract->supplier->mobile ?? 'No registrado' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Régimen Tributario</p>
                                <p class="text-gray-900">{{ \App\Models\Supplier::TAX_REGIMES[$contract->supplier->tax_regime] ?? $contract->supplier->tax_regime ?? 'No registrado' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Supervisor --}}
                @if($contract->supervisor)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Supervisor del Contrato
                    </h2>
                    <p class="text-gray-900 font-semibold">{{ $contract->supervisor->name }}</p>
                    <p class="text-sm text-gray-500">{{ $contract->supervisor->email }}</p>
                </div>
                @endif

                {{-- Registros Presupuestales (RPs) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        Registros Presupuestales (RPs)
                    </h2>

                    @forelse($contract->rps as $rp)
                        <div class="border border-gray-200 rounded-xl p-5 mb-4" wire:key="rp-detail-{{ $rp->id }}">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-700 rounded-lg text-sm font-bold">{{ $rp->formatted_number }}</span>
                                        RP N° {{ $rp->formatted_number }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <span class="font-medium">CDP:</span> #{{ $rp->cdp?->formatted_number }}
                                        — <span class="font-medium">Rubro:</span> {{ $rp->cdp?->budgetItem?->code }} - {{ $rp->cdp?->budgetItem?->name }}
                                    </p>
                                </div>
                                <div class="text-right mt-2 md:mt-0">
                                    <p class="text-xs text-gray-500">Total RP</p>
                                    <p class="text-lg font-bold text-green-600">${{ number_format($rp->total_amount, 0, ',', '.') }}</p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rp->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $rp->status_name }}
                                    </span>
                                </div>
                            </div>

                            {{-- Fuentes del RP --}}
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fuente de Financiación</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto Asignado</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">N° Cuenta Bancaria</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($rp->fundingSources as $rpFs)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-900">{{ $rpFs->fundingSource?->name ?? 'N/D' }}</td>
                                                <td class="px-4 py-2 text-right font-semibold">${{ number_format($rpFs->amount, 0, ',', '.') }}</td>
                                                <td class="px-4 py-2 text-gray-600">{{ $rpFs->bankAccount?->account_number ?? '-' }}</td>
                                                <td class="px-4 py-2 text-gray-600">{{ $rpFs->bank?->name ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-4">No hay RPs asociados a este contrato.</p>
                    @endforelse
                </div>
            @endif
        @endif
    </div>

    {{-- ==================== MODALES ==================== --}}

    {{-- Modal Confirmación de Cambio de Estado --}}
    @if($showStatusModal && $contract && $newStatus)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showStatusModal', false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="text-center">
                @php
                    $iconConfigs = [
                        'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'M5 13l4 4L19 7'],
                        'in_execution' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'suspended' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'terminated' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'icon' => 'M6 18L18 6M6 6l12 12'],
                        'annulled' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'icon' => 'M6 18L18 6M6 6l12 12'],
                    ];
                    $cfg = $iconConfigs[$newStatus] ?? ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'icon' => 'M5 13l4 4L19 7'];
                    $btnColors = match($newStatus) {
                        'active', 'completed' => 'bg-green-600 hover:bg-green-700',
                        'in_execution' => 'bg-yellow-500 hover:bg-yellow-600',
                        'suspended' => 'bg-orange-500 hover:bg-orange-600',
                        'terminated' => 'bg-red-600 hover:bg-red-700',
                        'annulled' => 'bg-red-600 hover:bg-red-700',
                        default => 'bg-indigo-600 hover:bg-indigo-700',
                    };
                @endphp
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full {{ $cfg['bg'] }} mb-4">
                    <svg class="h-6 w-6 {{ $cfg['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Cambio de Estado</h3>
                <p class="text-sm text-gray-500 mb-1">El Contrato N° <span class="font-semibold">{{ $contract->formatted_number }}</span> pasará de:</p>
                <p class="text-sm mb-4">
                    <span class="font-semibold text-gray-700">{{ $contract->status_name }}</span>
                    <svg class="inline w-4 h-4 text-gray-400 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <span class="font-bold text-indigo-600">{{ \App\Models\Contract::STATUSES[$newStatus] ?? $newStatus }}</span>
                </p>
            </div>
            <div class="flex justify-center gap-3">
                <button wire:click="$set('showStatusModal', false)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button wire:click="changeStatus" class="px-4 py-2 text-white rounded-xl transition-colors {{ $btnColors }}">
                    Sí, confirmar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Eliminar --}}
    @if($showDeleteModal && $contract)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showDeleteModal', false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Eliminar Contrato</h3>
                <p class="text-sm text-gray-500 mb-4">¿Está seguro de eliminar el Contrato N° {{ $contract->formatted_number }}? Los RPs asociados también serán eliminados y los CDPs volverán al estado activo.</p>
            </div>
            <div class="flex justify-center gap-3">
                <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button wire:click="deleteContract" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Anular Contrato --}}
    @if($showAnnulModal && $contract)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showAnnulModal', false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b bg-red-50">
                <h3 class="text-lg font-bold text-red-900">Anular Contrato</h3>
                <p class="text-sm text-red-700">Contrato N° {{ $contract->formatted_number }}</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-red-50 rounded-lg p-3 text-sm text-red-700">
                    Esta acción es irreversible. El contrato quedará en estado anulado permanentemente.
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razón de anulación <span class="text-red-500">*</span></label>
                    <textarea wire:model="annulmentReason" rows="3" class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-red-500" placeholder="Describa la razón de la anulación (mín. 10 caracteres)..."></textarea>
                    @error('annulmentReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                <button wire:click="$set('showAnnulModal', false)" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                <button wire:click="annulContract" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Anular Contrato</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Prórroga --}}
    @if($showExtensionModal && $contract)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showExtensionModal', false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full">
            <form wire:submit="saveExtension">
                <div class="px-6 py-4 border-b bg-indigo-50">
                    <h3 class="text-lg font-bold text-indigo-900">Prórroga de Tiempo</h3>
                    <p class="text-sm text-indigo-700">Contrato N° {{ $contract->formatted_number }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-blue-50 rounded-lg p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Fecha de terminación actual:</span>
                            <span class="font-semibold text-blue-700">{{ $contract->end_date->format('d/m/Y') }}</span>
                        </div>
                        @if($contract->original_end_date)
                            <div class="flex justify-between mt-1">
                                <span class="text-gray-600">Fecha original:</span>
                                <span class="text-gray-500">{{ $contract->original_end_date->format('d/m/Y') }}</span>
                            </div>
                        @endif
                    </div>

                    <div x-data="{
                        init() {
                            if (window.flatpickr) {
                                flatpickr(this.$refs.extDateInput, {
                                    dateFormat: 'Y-m-d',
                                    minDate: '{{ $contract->end_date->addDay()->format("Y-m-d") }}',
                                    disable: [function(date) { return date.getDay() === 0 || date.getDay() === 6; }],
                                    onChange: (selectedDates, dateStr) => { $wire.set('extensionNewEndDate', dateStr); }
                                });
                            }
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nueva fecha de terminación <span class="text-red-500">*</span></label>
                        <input type="text" x-ref="extDateInput" value="{{ $extensionNewEndDate }}" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white" placeholder="Seleccionar nueva fecha..." readonly>
                        @error('extensionNewEndDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Documento Otrosí <span class="text-red-500">*</span></label>
                        <input type="file" wire:model="extensionDocument" accept=".pdf,.doc,.docx" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-gray-400 mt-1">PDF, DOC o DOCX. Máximo 10MB.</p>
                        @error('extensionDocument') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showExtensionModal', false)" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="saveExtension">Registrar Prórroga</span>
                        <span wire:loading wire:target="saveExtension">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Adición de Recursos --}}
    @if($showAdditionModal && $contract)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showAdditionModal', false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
            <form wire:submit="saveAddition">
                <div class="px-6 py-4 border-b bg-emerald-50">
                    <h3 class="text-lg font-bold text-emerald-900">Adición de Recursos</h3>
                    <p class="text-sm text-emerald-700">Contrato N° {{ $contract->formatted_number }}</p>
                </div>
                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div class="bg-emerald-50 rounded-lg p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Valor actual del contrato:</span>
                            <span class="font-semibold text-emerald-700">${{ number_format($contract->total, 0, ',', '.') }}</span>
                        </div>
                        @if($contract->original_total)
                            <div class="flex justify-between mt-1">
                                <span class="text-gray-600">Valor original:</span>
                                <span class="text-gray-500">${{ number_format($contract->original_total, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between mt-1">
                            <span class="text-gray-600">Máximo adición permitida (50%):</span>
                            <span class="font-bold text-emerald-700">${{ number_format($contract->max_addition, 0, ',', '.') }}</span>
                        </div>
                        @if($contract->addition_amount > 0)
                            <div class="flex justify-between mt-1">
                                <span class="text-gray-600">Ya adicionado:</span>
                                <span class="text-orange-600">${{ number_format($contract->addition_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Rubro presupuestal --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal <span class="text-red-500">*</span></label>
                        <select wire:model.live="additionCdpBudgetItemId" wire:change="onAdditionBudgetItemSelected" class="w-full rounded-xl border-gray-300">
                            <option value="">Seleccionar rubro...</option>
                            @foreach($additionBudgetItems as $item)
                                <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                            @endforeach
                        </select>
                        @error('additionCdpBudgetItemId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fuentes disponibles --}}
                    @if(count($additionAvailableFundingSources) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fuentes Disponibles</label>
                            <div class="space-y-2">
                                @foreach($additionAvailableFundingSources as $afs)
                                    @if(!collect($additionCdpFundingSources)->contains('id', $afs['id']))
                                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                            <div>
                                                <span class="text-sm font-medium">{{ $afs['name'] }}</span>
                                                <span class="text-xs text-gray-500 ml-2">Disponible: ${{ number_format($afs['available'], 0, ',', '.') }}</span>
                                            </div>
                                            <button type="button" wire:click="addAdditionFundingSource({{ $afs['id'] }})" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-lg hover:bg-emerald-200">
                                                + Agregar
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @elseif($additionCdpBudgetItemId)
                        <div class="bg-yellow-50 rounded-lg p-3 text-sm text-yellow-700">
                            No hay fuentes con saldo disponible. Verifique que existan ingresos reales registrados.
                        </div>
                    @endif

                    {{-- Fuentes seleccionadas --}}
                    @if(count($additionCdpFundingSources) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fuentes Seleccionadas</label>
                            <div class="space-y-3">
                                @foreach($additionCdpFundingSources as $index => $fs)
                                    <div class="border rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">{{ $fs['name'] }}</span>
                                            <button type="button" wire:click="removeAdditionFundingSource({{ $index }})" class="text-red-500 hover:text-red-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500">Monto:</span>
                                            <div class="flex flex-1">
                                                <span class="inline-flex items-center px-2 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                                <input type="number" wire:model="additionCdpFundingSources.{{ $index }}.amount" step="0.01" min="0.01" max="{{ $fs['available'] }}" class="flex-1 min-w-0 rounded-r-lg border-gray-300 text-sm" placeholder="0.00">
                                            </div>
                                            <span class="text-xs text-gray-400 shrink-0">/ ${{ number_format($fs['available'], 0, ',', '.') }}</span>
                                        </div>
                                        @error("additionCdpFundingSources.{$index}.amount") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                            @php $totalAdd = collect($additionCdpFundingSources)->sum(fn($fs) => (float) ($fs['amount'] ?? 0)); @endphp
                            <div class="mt-3 pt-3 border-t flex justify-between items-center">
                                <span class="font-medium text-gray-700">Total adición</span>
                                <span class="text-lg font-bold {{ $totalAdd > ($contract->max_addition ?? 0) ? 'text-red-600' : 'text-emerald-600' }}">${{ number_format($totalAdd, 0, ',', '.') }}</span>
                            </div>
                            @if($totalAdd > ($contract->max_addition ?? 0))
                                <p class="text-xs text-red-600 text-right">Excede el máximo permitido</p>
                            @endif
                        </div>
                    @endif
                    @error('additionCdpFundingSources') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                    {{-- Documento Otrosí --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Documento Otrosí <span class="text-red-500">*</span></label>
                        <input type="file" wire:model="additionDocument" accept=".pdf,.doc,.docx" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <p class="text-xs text-gray-400 mt-1">PDF, DOC o DOCX. Máximo 10MB.</p>
                        @error('additionDocument') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showAdditionModal', false)" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700" {{ count($additionCdpFundingSources) === 0 ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="saveAddition">Registrar Adición</span>
                        <span wire:loading wire:target="saveAddition">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
