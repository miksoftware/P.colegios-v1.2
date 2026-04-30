<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Carga de Inventario Inicial</h1>
        <p class="text-sm text-gray-500">Sube el archivo de Excel oficial para importar automáticamente el inventario inicial del colegio.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <div class="max-w-2xl mx-auto">
            
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Instrucciones de Subida</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>El archivo debe respetar exactamente el formato oficial (AP-AI-RG-170).</li>
                                <li>El sistema leerá los datos a partir de la <strong>fila 5</strong>.</li>
                                <li>Se creará una "Entrada Inicial" a la que se vincularán todos los artículos.</li>
                                <li>Si una Cuenta Contable o Proveedor no existe, el sistema lo generará automáticamente.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Archivo Excel (.xlsx, .xls)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl {{ $file ? 'bg-blue-50 border-blue-300' : 'hover:bg-gray-50' }}">
                        <div class="space-y-1 text-center">
                            @if($file)
                                <svg class="mx-auto h-12 w-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer bg-transparent rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>Cambiar archivo</span>
                                        <input wire:model="file" type="file" class="sr-only" accept=".xlsx,.xls,.csv">
                                    </label>
                                </div>
                                <p class="text-xs text-blue-600 font-bold mt-2">{{ $file->getClientOriginalName() }}</p>
                            @else
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center mt-2">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Seleccionar un archivo</span>
                                        <input wire:model="file" type="file" class="sr-only" accept=".xlsx,.xls,.csv">
                                    </label>
                                    <p class="pl-1">o arrastra y suelta aquí</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">XLSX, XLS hasta 10MB</p>
                            @endif
                        </div>
                    </div>
                    @error('file') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end">
                    <button type="button" wire:click="importExcel" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors w-full sm:w-auto {{ (!$file || $isUploading) ? 'opacity-50 cursor-not-allowed' : '' }}" {{ (!$file || $isUploading) ? 'disabled' : '' }}>
                        <svg wire:loading wire:target="importExcel" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="importExcel">Iniciar Importación</span>
                        <span wire:loading wire:target="importExcel">Procesando y Guardando... (Puede tardar)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
