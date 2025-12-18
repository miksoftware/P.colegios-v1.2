<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Presupuesto</h1>
                <p class="text-gray-500 mt-1">Gestión del presupuesto inicial y modificaciones</p>
            </div>
            @can('budgets.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Presupuesto
            </button>
            @endcan
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Código o nombre...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select wire:model.live="filterType" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        <option value="income">Ingreso</option>
                        <option value="expense">Gasto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        @foreach($this->availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Inicial</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actual</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Año</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Mods</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->budgets as $budget)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $budget->budgetItem->code }}</div>
                            <div class="text-sm text-gray-500">{{ $budget->budgetItem->name }}</div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $budget->type_color }}">{{ $budget->type_name }}</span></td>
                        <td class="px-6 py-4 text-right text-sm">${{ number_format($budget->initial_amount, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-semibold {{ $budget->current_amount >= $budget->initial_amount ? 'text-green-600' : 'text-orange-600' }}">${{ number_format($budget->current_amount, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center text-sm">{{ $budget->fiscal_year }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($budget->modifications->count() > 0)
                            <button wire:click="openHistoryModal({{ $budget->id }})" class="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200">{{ $budget->modifications->count() }}</button>
                            @else
                            <span class="text-xs text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="toggleStatus({{ $budget->id }})" class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $budget->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $budget->is_active ? 'Activo' : 'Inactivo' }}</button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                @can('budgets.modify')<button wire:click="openModificationModal({{ $budget->id }})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg" title="Modificar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></button>@endcan
                                @can('budgets.edit')<button wire:click="editBudget({{ $budget->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endcan
                                @can('budgets.delete')<button wire:click="confirmDelete({{ $budget->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>@endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">No se encontraron presupuestos</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->budgets->hasPages())<div class="px-6 py-4 border-t">{{ $this->budgets->links() }}</div>@endif
        </div>
    </div>


    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $isEditing ? 'Editar Presupuesto' : 'Nuevo Presupuesto' }}</h3>
                            <p class="text-blue-100 text-sm mt-1">Presupuesto inicial por rubro</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal *</label>
                        <x-searchable-select
                            wire:model="budget_item_id"
                            :options="$budgetItems"
                            placeholder="Seleccione un rubro..."
                            searchPlaceholder="Buscar por código o nombre..."
                        />
                        @error('budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select wire:model="type" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="expense">Gasto</option>
                            <option value="income">Ingreso</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial *</label>
                            <input type="number" wire:model="initial_amount" step="0.01" min="0" class="w-full rounded-xl border-gray-300" placeholder="0.00">
                            @error('initial_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal *</label>
                            <input type="number" wire:model="fiscal_year" min="2020" max="2100" class="w-full rounded-xl border-gray-300">
                            @error('fiscal_year') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border-gray-300" placeholder="Descripción opcional..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">{{ $isEditing ? 'Actualizar' : 'Crear' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($showModificationModal && $modificationBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModificationModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Modificar Presupuesto</h3>
                            <p class="text-blue-100 text-sm mt-1">Adición o reducción</p>
                        </div>
                        <button type="button" wire:click="closeModificationModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="saveModification" class="px-6 py-5 space-y-5">
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                        <p class="text-sm text-gray-600">{{ $modificationBudget->budgetItem->code }} - {{ $modificationBudget->budgetItem->name }}</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">Saldo actual: ${{ number_format($modificationBudget->current_amount, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Modificación *</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center"><input type="radio" wire:model="modification_type" value="addition" class="text-green-600"><span class="ml-2 text-sm">Adición</span></label>
                            <label class="inline-flex items-center"><input type="radio" wire:model="modification_type" value="reduction" class="text-orange-600"><span class="ml-2 text-sm">Reducción</span></label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <input type="number" wire:model="modification_amount" step="0.01" min="0.01" class="w-full rounded-xl border-gray-300" placeholder="0.00">
                            @error('modification_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Modificación</label>
                            <input type="text" wire:model="modification_document_number" class="w-full rounded-xl border-gray-300" placeholder="Ej: MOD-001">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón / Justificación *</label>
                        <textarea wire:model="modification_reason" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Describa la razón de esta modificación..."></textarea>
                        @error('modification_reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModificationModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">Registrar Modificación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif


    @if($showHistoryModal && $historyBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeHistoryModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-3xl">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Historial de Modificaciones</h3>
                            <p class="text-blue-100 text-sm mt-1">{{ $historyBudget->budgetItem->code }} - {{ $historyBudget->budgetItem->name }}</p>
                        </div>
                        <button type="button" wire:click="closeHistoryModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div><span class="text-gray-500">Inicial:</span> <span class="font-semibold">${{ number_format($historyBudget->initial_amount, 2, ',', '.') }}</span></div>
                            <div><span class="text-gray-500">Actual:</span> <span class="font-semibold text-blue-600">${{ number_format($historyBudget->current_amount, 2, ',', '.') }}</span></div>
                            <div><span class="text-gray-500">Diferencia:</span> @php $diff = $historyBudget->current_amount - $historyBudget->initial_amount; @endphp<span class="font-semibold {{ $diff >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 2, ',', '.') }}</span></div>
                        </div>
                    </div>
                    @if($historyBudget->modifications->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">#</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Monto</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Nuevo Saldo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Razón</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($historyBudget->modifications as $mod)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $mod->formatted_number }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $mod->type_color }}">{{ $mod->type_name }}</span></td>
                                <td class="px-4 py-3 text-right {{ $mod->type === 'addition' ? 'text-green-600' : 'text-orange-600' }}">{{ $mod->type === 'addition' ? '+' : '-' }}${{ number_format($mod->amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($mod->new_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 max-w-xs truncate" title="{{ $mod->reason }}">{{ Str::limit($mod->reason, 35) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $mod->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-center text-gray-500 py-8">No hay modificaciones registradas</p>
                    @endif
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="button" wire:click="closeHistoryModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Eliminar Presupuesto</h3>
                        <button type="button" wire:click="closeDeleteModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <p class="text-gray-600">¿Está seguro de eliminar el presupuesto del rubro <strong class="text-gray-900">{{ $itemToDelete->budgetItem->name }}</strong>?</p>
                    <p class="text-sm text-gray-500 mt-2">Esta acción eliminará todas las modificaciones asociadas.</p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="deleteBudget" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
