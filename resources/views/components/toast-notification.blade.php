<div 
    x-data="{ 
        show: false, 
        message: '', 
        type: 'success',
        init() {
            // Listen for Livewire events (both 'notify' and 'toast')
            Livewire.on('notify', (data) => {
                this.handleNotification(data);
            });
            Livewire.on('toast', (data) => {
                this.handleNotification(data);
            });

            // Check for session flash messages
            @if(session()->has('message'))
                this.showNotification('{{ session('message') }}', 'success');
            @endif
            @if(session()->has('error'))
                this.showNotification('{{ session('error') }}', 'error');
            @endif
        },
        handleNotification(data) {
            // Handle both array format [message, type] and object format {message: '...', type: '...'}
            if (Array.isArray(data)) {
                this.showNotification(data[0].message || data[0], data[0].type || 'success');
            } else {
                this.showNotification(data.message, data.type);
            }
        },
        showNotification(message, type = 'success') {
            this.message = message;
            this.type = type;
            this.show = true;
            setTimeout(() => {
                this.show = false;
            }, 4000);
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed bottom-5 right-5 z-[9999] flex items-center gap-3 px-6 py-4 rounded-xl shadow-2xl border"
    :class="{
        'bg-white border-green-100 text-green-800': type === 'success',
        'bg-white border-red-100 text-red-800': type === 'error',
        'bg-white border-blue-100 text-blue-800': type === 'info'
    }"
    style="display: none;"
>
    <!-- Success Icon -->
    <svg x-show="type === 'success'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>

    <!-- Error Icon -->
    <svg x-show="type === 'error'" class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>

    <!-- Info Icon -->
    <svg x-show="type === 'info'" class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>

    <div class="flex flex-col">
        <span x-text="message" class="font-semibold text-sm"></span>
    </div>

    <button @click="show = false" class="ml-4 text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
