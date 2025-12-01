<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Registro de Actividad</h1>
                <p class="text-gray-500 mt-1">Historial de cambios y acciones en el sistema</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Búsqueda -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            class="w-full pl-10 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Buscar por descripción o usuario..."
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Colegio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Colegio</label>
                    <select wire:model.live="filterSchool" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($this->schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Usuario -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                    <select wire:model.live="filterUser" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($this->users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Módulo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
                    <select wire:model.live="filterModule" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($this->modules as $module)
                            <option value="{{ $module }}">{{ ucfirst(str_replace('_', ' ', $module)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Acción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                    <select wire:model.live="filterAction" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        <option value="created">Creación</option>
                        <option value="updated">Actualización</option>
                        <option value="deleted">Eliminación</option>
                    </select>
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                    <input 
                        type="date" 
                        wire:model.live="filterDateFrom"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                    <input 
                        type="date" 
                        wire:model.live="filterDateTo"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button 
                    wire:click="clearFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Tabla de logs -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colegio</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Detalles</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-teal-400 flex items-center justify-center text-white font-bold text-xs">
                                            {{ $log->user ? substr($log->user->name, 0, 1) : '?' }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $log->user?->name ?? 'Sistema' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $actionColors = [
                                            'created' => 'bg-green-100 text-green-800',
                                            'updated' => 'bg-blue-100 text-blue-800',
                                            'deleted' => 'bg-red-100 text-red-800',
                                        ];
                                        $actionIcons = [
                                            'created' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
                                            'updated' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                            'deleted' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            {!! $actionIcons[$log->action] ?? '' !!}
                                        </svg>
                                        {{ $log->action_display_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $log->module_display_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                    {{ $log->description ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->school?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button 
                                        wire:click="showDetails({{ $log->id }})"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        Ver más
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-gray-500">No hay registros de actividad</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $this->logs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de Detalles -->
    @if($showDetailModal && $selectedLog)
    <div 
        x-data="{ show: true }"
        x-show="show"
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeDetailModal"></div>

            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl w-full">
                <div class="bg-white px-6 py-5">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Detalle de Actividad</h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Fecha y Hora</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Usuario</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->user?->name ?? 'Sistema' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Módulo</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->module_display_name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Acción</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->action_display_name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Colegio</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->school?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">IP</label>
                                <p class="text-sm text-gray-900 font-mono">{{ $selectedLog->ip_address ?? '-' }}</p>
                            </div>
                        </div>

                        @if($selectedLog->description)
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Descripción</label>
                                <p class="text-sm text-gray-900">{{ $selectedLog->description }}</p>
                            </div>
                        @endif

                        @if($selectedLog->action === 'updated' && $selectedLog->old_values && $selectedLog->new_values)
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase mb-2 block">Cambios Realizados</label>
                                <div class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campo</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Antes</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Después</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($selectedLog->new_values as $field => $newValue)
                                                @if(!in_array($field, ['updated_at', 'created_at']))
                                                <tr>
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-700">
                                                        {{ $this->getFieldLabel($field) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span class="inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700">
                                                            {{ $this->formatFieldValue($field, $selectedLog->old_values[$field] ?? '-') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-700">
                                                            {{ $this->formatFieldValue($field, $newValue) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @elseif($selectedLog->action === 'created' && $selectedLog->new_values)
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase mb-2 block">Datos Creados</label>
                                <div class="bg-green-50 rounded-lg overflow-hidden border border-green-200">
                                    <table class="min-w-full">
                                        <tbody class="divide-y divide-green-200">
                                            @foreach($selectedLog->new_values as $field => $value)
                                                @if(!in_array($field, ['updated_at', 'created_at', 'id']))
                                                <tr>
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-700 w-1/3">
                                                        {{ $this->getFieldLabel($field) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-green-800">
                                                        {{ $this->formatFieldValue($field, $value) }}
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @elseif($selectedLog->action === 'deleted' && $selectedLog->old_values)
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase mb-2 block">Datos Eliminados</label>
                                <div class="bg-red-50 rounded-lg overflow-hidden border border-red-200">
                                    <table class="min-w-full">
                                        <tbody class="divide-y divide-red-200">
                                            @foreach($selectedLog->old_values as $field => $value)
                                                @if(!in_array($field, ['updated_at', 'created_at', 'id']))
                                                <tr>
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-700 w-1/3">
                                                        {{ $this->getFieldLabel($field) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-red-800">
                                                        {{ $this->formatFieldValue($field, $value) }}
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button 
                        wire:click="closeDetailModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
