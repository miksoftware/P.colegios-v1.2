<div>
    <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
            {{ substr(auth()->user()->name, 0, 1) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
        </div>
    </div>
    <button wire:click="logout" class="w-full mt-2 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Cerrar Sesión
    </button>
</div>
