@php
    $isExpanded = in_array($account->id, $expandedAccounts);
    $hasChildren = $account->children->count() > 0;
    $paddingLeft = $depth * 24;
@endphp

<div class="border-b border-gray-50 last:border-b-0">
    <div 
        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors group"
        style="padding-left: {{ 16 + $paddingLeft }}px;"
    >
        <!-- Expand/Collapse Button -->
        <div class="w-6 flex-shrink-0">
            @if($hasChildren)
                <button 
                    wire:click="toggleExpand({{ $account->id }})"
                    class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition-colors"
                >
                    <svg class="w-4 h-4 transform transition-transform {{ $isExpanded ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif
        </div>

        <!-- Code -->
        <div class="w-24 flex-shrink-0">
            <span class="font-mono text-sm font-semibold text-gray-900">{{ $account->code }}</span>
        </div>

        <!-- Level Badge -->
        <div class="w-24 flex-shrink-0">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $account->level_color }}">
                {{ $account->level_name }}
            </span>
        </div>

        <!-- Name -->
        <div class="flex-1 min-w-0">
            <span class="text-sm font-medium text-gray-900 {{ !$account->is_active ? 'line-through text-gray-400' : '' }}">
                {{ $account->name }}
            </span>
            @if($account->description)
                <p class="text-xs text-gray-500 truncate">{{ $account->description }}</p>
            @endif
        </div>

        <!-- Nature -->
        <div class="w-16 flex-shrink-0 text-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $account->nature === 'D' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $account->nature_name }}
            </span>
        </div>

        <!-- Movement indicator -->
        <div class="w-10 flex-shrink-0 text-center">
            @if($account->allows_movement)
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600" title="Permite movimientos">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            @endif
        </div>

        <!-- Status -->
        <div class="w-10 flex-shrink-0 text-center">
            <button 
                wire:click="toggleStatus({{ $account->id }})"
                class="inline-flex items-center justify-center w-6 h-6 rounded-full transition-colors {{ $account->is_active ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}"
                title="{{ $account->is_active ? 'Activa' : 'Inactiva' }}"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8"/>
                </svg>
            </button>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            @if($account->level < 5)
                @can('accounting_accounts.create')
                <button 
                    wire:click="openCreateModal({{ $account->id }})"
                    class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                    title="Agregar subcuenta"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                @endcan
            @endif

            @can('accounting_accounts.edit')
            <button 
                wire:click="editAccount({{ $account->id }})"
                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Editar"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            @endcan

            @can('accounting_accounts.delete')
            <button 
                wire:click="confirmDelete({{ $account->id }})"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Eliminar"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            @endcan
        </div>
    </div>

    <!-- Children (recursive) -->
    @if($hasChildren && $isExpanded)
        <div class="bg-gray-50/50">
            @foreach($account->children as $child)
                @include('livewire.partials.accounting-account-row', ['account' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
