<div class="min-h-screen bg-gray-950 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full space-y-8">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-white">
                Selecciona un Colegio
            </h2>
            <p class="mt-2 text-center text-sm text-gray-400">
                Elige el colegio al que deseas acceder
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($schools as $school)
                <button
                    wire:click="selectSchool({{ $school->id }})"
                    class="group relative bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-blue-500 hover:bg-gray-800 transition-all duration-200 text-left"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white group-hover:text-blue-400 transition-colors">
                                {{ $school->name }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-400">
                                NIT: {{ $school->nit }}
                            </p>
                            <p class="text-sm text-gray-400">
                                {{ $school->municipality }}
                            </p>
                        </div>
                        <svg class="h-6 w-6 text-gray-600 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</div>
