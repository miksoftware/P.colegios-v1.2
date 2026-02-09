<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($currentView === 'list')
            {{-- ==================== VISTA LISTADO ==================== --}}
            
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Etapa Precontractual</h1>
                    <p class="text-gray-500 mt-1">Gestión de convocatorias, CDPs y propuestas</p>
                </div>
                @can('precontractual.create')
                    <button wire:click="openCreateModal" class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva Convocatoria
                    </button>
                @endcan
            </div>

            {{-- Resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $this->summary['total'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Borrador</p>
                    <p class="text-2xl font-bold text-gray-500">{{ $this->summary['draft'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-blue-500 uppercase">Abiertas</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $this->summary['open'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-yellow-500 uppercase">En Evaluación</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $this->summary['evaluation'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-green-500 uppercase">Adjudicadas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $this->summary['awarded'] }}</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Objeto o número...">
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
                            @foreach(\App\Models\Convocatoria::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="clearFilters" class="w-full px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla de Convocatorias --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Objeto</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Presupuesto</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">CDPs</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Propuestas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fechas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($this->convocatorias as $conv)
                                <tr class="hover:bg-gray-50" wire:key="conv-{{ $conv->id }}">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-indigo-600">{{ $conv->formatted_number }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs">
                                            <p class="font-medium text-gray-900 truncate">{{ Str::limit($conv->object, 60) }}</p>
                                            <p class="text-xs text-gray-500">{{ $conv->expenseDistribution?->expenseCode?->code }} - {{ Str::limit($conv->expenseDistribution?->expenseCode?->name, 40) }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold">${{ number_format($conv->assigned_budget, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            {{ $conv->cdps->where('status', 'active')->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700">
                                            {{ $conv->proposals->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $conv->status_color }}">
                                            {{ $conv->status_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-gray-500">
                                        <div>{{ $conv->start_date?->format('d/m/Y') }}</div>
                                        <div>{{ $conv->end_date?->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="viewDetail({{ $conv->id }})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver detalle">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            @if($conv->status === 'draft')
                                                @can('precontractual.delete')
                                                    <button wire:click="confirmDeleteConvocatoria({{ $conv->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar">
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
                                        <p class="mt-2">No hay convocatorias para este período</p>
                                        <p class="text-sm text-gray-400 mt-1">Cree una nueva convocatoria desde una distribución de gasto</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($this->convocatorias->hasPages())
                    <div class="px-6 py-4 border-t">{{ $this->convocatorias->links() }}</div>
                @endif
            </div>

        @else
            {{-- ==================== VISTA DETALLE ==================== --}}
            @if($convocatoria)
                {{-- Breadcrumb --}}
                <div class="mb-6">
                    <button wire:click="backToList" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver al listado
                    </button>
                </div>

                {{-- Header Convocatoria --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-2xl font-bold text-gray-900">Convocatoria #{{ $convocatoria->formatted_number }}</h1>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $convocatoria->status_color }}">
                                    {{ $convocatoria->status_name }}
                                </span>
                            </div>
                            <p class="text-gray-700 mb-2">{{ $convocatoria->object }}</p>
                            <p class="text-sm text-gray-500">{{ $convocatoria->justification }}</p>
                            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                                <span><strong>Inicio:</strong> {{ $convocatoria->start_date?->format('d/m/Y') }}</span>
                                <span><strong>Cierre:</strong> {{ $convocatoria->end_date?->format('d/m/Y') }}</span>
                                <span><strong>Año fiscal:</strong> {{ $convocatoria->fiscal_year }}</span>
                                <span><strong>Creado por:</strong> {{ $convocatoria->creator?->name ?? 'N/D' }}</span>
                            </div>
                            @if($convocatoria->expenseDistribution)
                                <div class="mt-2 text-sm text-gray-500">
                                    <strong>Distribución:</strong> {{ $convocatoria->expenseDistribution->expenseCode?->code }} - {{ $convocatoria->expenseDistribution->expenseCode?->name }}
                                    ({{ $convocatoria->expenseDistribution->budget?->budgetItem?->name }} / {{ $convocatoria->expenseDistribution->budget?->fundingSource?->name }})
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Presupuesto asignado</p>
                            <p class="text-3xl font-bold text-indigo-600">${{ number_format($convocatoria->assigned_budget, 0, ',', '.') }}</p>
                            
                            {{-- Botones de acción según estado --}}
                            <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                @can('precontractual.edit')
                                    @if($convocatoria->status === 'draft')
                                        <button wire:click="openStatusModal('open')" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                            Abrir Convocatoria
                                        </button>
                                        <button wire:click="openStatusModal('cancelled')" class="px-3 py-1.5 bg-red-100 text-red-700 text-sm rounded-lg hover:bg-red-200">
                                            Cancelar
                                        </button>
                                    @elseif($convocatoria->status === 'open')
                                        <button wire:click="openStatusModal('evaluation')" class="px-3 py-1.5 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600">
                                            Pasar a Evaluación
                                        </button>
                                        <button wire:click="openStatusModal('cancelled')" class="px-3 py-1.5 bg-red-100 text-red-700 text-sm rounded-lg hover:bg-red-200">
                                            Cancelar
                                        </button>
                                    @elseif($convocatoria->status === 'evaluation')
                                        <button wire:click="openStatusModal('awarded')" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                            Adjudicar
                                        </button>
                                        <button wire:click="openStatusModal('open')" class="px-3 py-1.5 bg-blue-100 text-blue-700 text-sm rounded-lg hover:bg-blue-200">
                                            Reabrir
                                        </button>
                                    @elseif($convocatoria->status === 'awarded')
                                        @can('contractual.create')
                                            <a href="{{ route('contractual.index', ['convocatoria_id' => $convocatoria->id]) }}" class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 inline-flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                                Crear Contrato
                                            </a>
                                        @endcan
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sección CDPs --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Certificados de Disponibilidad Presupuestal (CDP)
                        </h3>
                        @if($convocatoria->status === 'draft')
                            @can('precontractual.create')
                                <button wire:click="openCdpModal" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Agregar CDP
                                </button>
                            @endcan
                        @endif
                    </div>
                    <div class="p-6">
                        @if($convocatoria->cdps->count() > 0)
                            <div class="space-y-4">
                                @foreach($convocatoria->cdps as $cdp)
                                    <div class="border rounded-xl overflow-hidden {{ $cdp->status === 'cancelled' ? 'opacity-50' : '' }}">
                                        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono font-bold text-blue-600">CDP #{{ $cdp->formatted_number }}</span>
                                                <span class="text-sm text-gray-600">{{ $cdp->budgetItem?->code }} - {{ $cdp->budgetItem?->name }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cdp->status_color }}">
                                                    {{ $cdp->status_name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-semibold text-gray-900">${{ number_format($cdp->total_amount, 0, ',', '.') }}</span>
                                                @if($cdp->status === 'active' && $convocatoria->status === 'draft')
                                                    @can('precontractual.edit')
                                                        <button wire:click="cancelCdp({{ $cdp->id }})" wire:confirm="¿Está seguro de anular este CDP?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Anular CDP">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </div>
                                        {{-- Fuentes del CDP --}}
                                        @if($cdp->fundingSources->count() > 0)
                                            <div class="px-4 py-2 divide-y divide-gray-100">
                                                @foreach($cdp->fundingSources as $cdpFs)
                                                    <div class="py-2 flex justify-between items-center text-sm">
                                                        <span class="text-gray-600">{{ $cdpFs->fundingSource?->code }} - {{ $cdpFs->fundingSource?->name }}</span>
                                                        <div class="text-right">
                                                            <span class="font-medium">${{ number_format($cdpFs->amount, 0, ',', '.') }}</span>
                                                            <span class="text-xs text-gray-400 ml-2">(Saldo al crear: ${{ number_format($cdpFs->available_balance_at_creation, 0, ',', '.') }})</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Total CDPs --}}
                            @php $totalCdps = $convocatoria->cdps->where('status', 'active')->sum('total_amount'); @endphp
                            <div class="mt-4 pt-4 border-t flex justify-between items-center">
                                <span class="font-medium text-gray-700">Total CDPs activos</span>
                                <span class="text-xl font-bold text-blue-600">${{ number_format($totalCdps, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="mt-2">No hay CDPs registrados</p>
                                @if($convocatoria->status === 'draft')
                                    <p class="text-sm text-gray-400">Agregue al menos un CDP para poder abrir la convocatoria</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Sección Propuestas --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Propuestas ({{ $convocatoria->proposals->count() }})
                        </h3>
                        <div class="flex gap-2">
                            @if(in_array($convocatoria->status, ['open', 'evaluation']))
                                @can('precontractual.create')
                                    <button wire:click="openProposalModal" class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Registrar Propuesta
                                    </button>
                                @endcan
                            @endif
                            @if($convocatoria->status === 'evaluation')
                                @can('precontractual.evaluate')
                                    <button wire:click="openEvaluateModal" class="px-3 py-1.5 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                        Evaluar
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        @if($convocatoria->proposals->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Puntaje</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($convocatoria->proposals->sortByDesc('score') as $proposal)
                                            <tr class="{{ $proposal->is_selected ? 'bg-green-50' : 'hover:bg-gray-50' }}">
                                                <td class="px-4 py-3 font-mono text-sm">{{ $proposal->proposal_number }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900">{{ $proposal->supplier?->full_name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $proposal->supplier?->full_document }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-right text-sm">${{ number_format($proposal->subtotal, 0, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-right text-sm">${{ number_format($proposal->iva, 0, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-right font-semibold">${{ number_format($proposal->total, 0, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    @if($proposal->score !== null)
                                                        <span class="font-bold {{ $proposal->score >= 70 ? 'text-green-600' : ($proposal->score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                            {{ number_format($proposal->score, 1) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    @if($proposal->is_selected)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                            ★ Ganadora
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 text-sm">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    @if(!in_array($convocatoria->status, ['awarded', 'cancelled']))
                                                        @can('precontractual.delete')
                                                            <button wire:click="confirmDeleteProposal({{ $proposal->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        @endcan
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p class="mt-2">No hay propuestas registradas</p>
                                @if(in_array($convocatoria->status, ['open', 'evaluation']))
                                    <p class="text-sm text-gray-400">Registre las propuestas de los proveedores</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Flujo de trabajo --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Flujo de Trabajo</h3>
                    <div class="flex items-center justify-between">
                        @php
                            $steps = [
                                'draft' => ['label' => 'Borrador', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                                'open' => ['label' => 'Abierta', 'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'],
                                'evaluation' => ['label' => 'Evaluación', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                                'awarded' => ['label' => 'Adjudicada', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ];
                            $statusOrder = array_keys($steps);
                            $currentIdx = array_search($convocatoria->status, $statusOrder);
                            if ($currentIdx === false) $currentIdx = -1;
                        @endphp
                        @foreach($steps as $key => $step)
                            @php
                                $idx = array_search($key, $statusOrder);
                                $isActive = $key === $convocatoria->status;
                                $isDone = $idx < $currentIdx;
                                $isCancelled = $convocatoria->status === 'cancelled';
                            @endphp
                            <div class="flex-1 text-center">
                                <div class="mx-auto w-10 h-10 rounded-full flex items-center justify-center {{ $isCancelled ? 'bg-red-100' : ($isDone ? 'bg-green-100' : ($isActive ? 'bg-indigo-100' : 'bg-gray-100')) }}">
                                    <svg class="w-5 h-5 {{ $isCancelled ? 'text-red-500' : ($isDone ? 'text-green-500' : ($isActive ? 'text-indigo-600' : 'text-gray-400')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-xs font-medium {{ $isActive ? 'text-indigo-600' : ($isDone ? 'text-green-600' : 'text-gray-500') }}">{{ $step['label'] }}</p>
                            </div>
                            @if(!$loop->last)
                                <div class="flex-shrink-0 w-12 h-0.5 {{ $isDone ? 'bg-green-300' : 'bg-gray-200' }} mt-[-1rem]"></div>
                            @endif
                        @endforeach
                    </div>
                    @if($convocatoria->status === 'cancelled')
                        <div class="mt-4 bg-red-50 rounded-lg p-3 text-center text-sm text-red-700">
                            Esta convocatoria fue cancelada
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- ==================== MODALES ==================== --}}

    {{-- Modal Crear Convocatoria --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeCreateModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-2xl">
                <form wire:submit="saveConvocatoria">
                    <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                        <h3 class="text-lg font-bold text-indigo-900">Nueva Convocatoria</h3>
                        <p class="text-sm text-indigo-700">Crear proceso precontractual desde una distribución de gasto</p>
                    </div>
                    
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Distribución de Gasto <span class="text-red-500">*</span></label>
                            <select wire:model.live="selectedDistributionId" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar distribución...</option>
                                @foreach($distributions as $dist)
                                    <option value="{{ $dist['id'] }}">
                                        {{ $dist['label'] }} — {{ $dist['budget_item'] }} / {{ $dist['funding_source'] }} (Disponible: ${{ number_format($dist['available'], 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedDistributionId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Objeto de la Convocatoria <span class="text-red-500">*</span></label>
                            <textarea wire:model="convObject" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Descripción del objeto a contratar..."></textarea>
                            @error('convObject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Justificación <span class="text-red-500">*</span></label>
                            <textarea wire:model="convJustification" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Justificación de la necesidad..."></textarea>
                            @error('convJustification') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="convStartDate" class="w-full rounded-xl border-gray-300">
                                @error('convStartDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Cierre <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="convEndDate" class="w-full rounded-xl border-gray-300">
                                @error('convEndDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Presupuesto Asignado <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                <input type="number" wire:model="convAssignedBudget" step="0.01" min="1" class="flex-1 rounded-r-xl border-gray-300" placeholder="0.00">
                            </div>
                            @error('convAssignedBudget') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">Crear Convocatoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal CDP --}}
    @if($showCdpModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeCdpModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-2xl">
                <form wire:submit="saveCdp">
                    <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                        <h3 class="text-lg font-bold text-blue-900">Registrar CDP</h3>
                        <p class="text-sm text-blue-700">Certificado de Disponibilidad Presupuestal</p>
                    </div>
                    
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal <span class="text-red-500">*</span></label>
                            <select wire:model.live="cdpBudgetItemId" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar rubro...</option>
                                @foreach($budgetItems as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                @endforeach
                            </select>
                            @error('cdpBudgetItemId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Fuentes disponibles --}}
                        @if(count($availableFundingSources) > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fuentes Disponibles</label>
                                <div class="space-y-2">
                                    @foreach($availableFundingSources as $afs)
                                        @if(!collect($cdpFundingSources)->contains('id', $afs['id']))
                                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                                <div>
                                                    <span class="text-sm font-medium">{{ $afs['name'] }}</span>
                                                    <span class="text-xs text-gray-500 ml-2">Disponible: ${{ number_format($afs['available'], 0, ',', '.') }}</span>
                                                </div>
                                                <button type="button" wire:click="addCdpFundingSource({{ $afs['id'] }})" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-lg hover:bg-blue-200">
                                                    + Agregar
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @elseif($cdpBudgetItemId)
                            <div class="bg-yellow-50 rounded-lg p-3 text-sm text-yellow-700">
                                No hay fuentes de financiación con saldo disponible para este rubro.
                            </div>
                        @endif

                        {{-- Fuentes seleccionadas --}}
                        @if(count($cdpFundingSources) > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fuentes Seleccionadas</label>
                                <div class="space-y-3">
                                    @foreach($cdpFundingSources as $index => $fs)
                                        <div class="border rounded-lg p-3">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-sm font-medium">{{ $fs['name'] }}</span>
                                                <button type="button" wire:click="removeCdpFundingSource({{ $index }})" class="text-red-500 hover:text-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-500">Monto:</span>
                                                <div class="flex flex-1">
                                                    <span class="inline-flex items-center px-2 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                                    <input type="number" wire:model="cdpFundingSources.{{ $index }}.amount" step="0.01" min="0.01" max="{{ $fs['available'] }}" class="flex-1 rounded-r-lg border-gray-300 text-sm" placeholder="0.00">
                                                </div>
                                                <span class="text-xs text-gray-400">/ ${{ number_format($fs['available'], 0, ',', '.') }}</span>
                                            </div>
                                            @error("cdpFundingSources.{$index}.amount") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                                @php $totalCdp = collect($cdpFundingSources)->sum(fn($fs) => (float) ($fs['amount'] ?? 0)); @endphp
                                <div class="mt-3 pt-3 border-t flex justify-between items-center">
                                    <span class="font-medium text-gray-700">Total CDP</span>
                                    <span class="text-lg font-bold text-blue-600">${{ number_format($totalCdp, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endif
                        @error('cdpFundingSources') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeCdpModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700" {{ count($cdpFundingSources) === 0 ? 'disabled' : '' }}>Registrar CDP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Propuesta --}}
    @if($showProposalModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeProposalModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <form wire:submit="saveProposal">
                    <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                        <h3 class="text-lg font-bold text-purple-900">Registrar Propuesta</h3>
                        <p class="text-sm text-purple-700">Convocatoria #{{ $convocatoria?->formatted_number }}</p>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                            <select wire:model="proposalSupplierId" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar proveedor...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                @endforeach
                            </select>
                            @error('proposalSupplierId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                    <input type="number" wire:model="proposalSubtotal" step="0.01" min="0.01" class="flex-1 rounded-r-xl border-gray-300" placeholder="0.00">
                                </div>
                                @error('proposalSubtotal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                    <input type="number" wire:model="proposalIva" step="0.01" min="0" class="flex-1 rounded-r-xl border-gray-300" placeholder="0.00">
                                </div>
                                @error('proposalIva') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if($proposalSubtotal)
                            <div class="bg-gray-50 rounded-xl p-3 text-right">
                                <span class="text-sm text-gray-500">Total:</span>
                                <span class="text-lg font-bold text-gray-900 ml-2">${{ number_format(($proposalSubtotal ?: 0) + ($proposalIva ?: 0), 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeProposalModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Evaluar --}}
    @if($showEvaluateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeEvaluateModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-3xl">
                <form wire:submit="saveEvaluation">
                    <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                        <h3 class="text-lg font-bold text-yellow-900">Evaluación de Propuestas</h3>
                        <p class="text-sm text-yellow-700">Asigne puntajes (0-100) y seleccione la propuesta ganadora</p>
                    </div>
                    
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-4">
                            @foreach($proposalScores as $index => $ps)
                                <div class="border rounded-xl p-4 {{ $ps['is_selected'] ? 'border-green-500 bg-green-50' : '' }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="font-medium text-gray-900">{{ $ps['supplier'] }}</span>
                                            <span class="text-sm text-gray-500 ml-3">Total: ${{ number_format($ps['total'], 0, ',', '.') }}</span>
                                        </div>
                                        <button type="button" wire:click="selectProposal({{ $index }})" class="px-3 py-1 text-sm rounded-lg {{ $ps['is_selected'] ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-green-100' }}">
                                            {{ $ps['is_selected'] ? '★ Seleccionada' : 'Seleccionar' }}
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="text-sm text-gray-500">Puntaje:</label>
                                        <input type="number" wire:model="proposalScores.{{ $index }}.score" min="0" max="100" step="0.1" class="w-24 rounded-lg border-gray-300 text-sm" placeholder="0-100">
                                        @error("proposalScores.{$index}.score") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeEvaluateModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-xl hover:bg-yellow-600">Guardar Evaluación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Estado --}}
    @if($showStatusModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeStatusModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-indigo-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Cambiar Estado</h3>
                    <p class="text-sm text-gray-500 text-center">
                        ¿Está seguro de cambiar el estado de la convocatoria a 
                        <strong>{{ \App\Models\Convocatoria::STATUSES[$newStatus] ?? $newStatus }}</strong>?
                    </p>
                    @if($newStatus === 'cancelled')
                        <p class="mt-2 text-sm text-red-600 text-center">Esta acción no se puede deshacer.</p>
                    @endif
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeStatusModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="button" wire:click="changeStatus" class="px-4 py-2 {{ $newStatus === 'cancelled' ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white rounded-xl">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Eliminar --}}
    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">
                        Eliminar {{ $deleteType === 'convocatoria' ? 'Convocatoria' : 'Propuesta' }}
                    </h3>
                    <p class="text-sm text-gray-500 text-center">¿Estás seguro? Esta acción no se puede deshacer.</p>
                    @if($deleteType === 'convocatoria')
                        <p class="text-xs text-red-500 text-center mt-1">Se eliminarán también los CDPs y propuestas asociadas.</p>
                    @endif
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="button" wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
