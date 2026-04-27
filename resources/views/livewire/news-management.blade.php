<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Noticias</h1>
                <p class="text-gray-500 mt-1">Gestión y publicación de noticias para los colegios</p>
            </div>
            @can('news.create')
                <button
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-xl shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-blue-600 transition-all font-semibold text-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Noticia
                </button>
            @endcan
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por título o descripción..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    />
                </div>
                <select
                    wire:model.live="filterPublished"
                    class="px-4 py-2.5 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                    <option value="">Todas</option>
                    <option value="1">Publicadas</option>
                    <option value="0">Borradores</option>
                </select>
            </div>
        </div>

        {{-- News Grid --}}
        @if($this->news->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
                <p class="text-gray-500 font-medium">No hay noticias todavía</p>
                <p class="text-gray-400 text-sm mt-1">Crea la primera noticia para los colegios</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                @foreach($this->news as $item)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group">
                        {{-- File preview --}}
                        @if($item->file_path)
                            @if($item->file_type === 'image')
                                <div class="h-48 overflow-hidden bg-gray-100">
                                    <img
                                        src="{{ Storage::url($item->file_path) }}"
                                        alt="{{ $item->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                </div>
                            @else
                                <div class="h-48 bg-gradient-to-br from-red-50 to-red-100 flex flex-col items-center justify-center gap-2">
                                    <svg class="w-14 h-14 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-red-500">Documento PDF</span>
                                    @if($item->original_filename)
                                        <span class="text-xs text-red-400 px-4 text-center truncate w-full text-center">{{ $item->original_filename }}</span>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="h-48 bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                                <svg class="w-14 h-14 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-5">
                            {{-- Status badge + audience --}}
                            <div class="flex items-center gap-2 mb-3">
                                @if($item->is_published)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Publicada
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Borrador
                                    </span>
                                @endif

                                @if($item->for_all_schools)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Todos
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $item->schools->count() }} {{ $item->schools->count() === 1 ? 'colegio' : 'colegios' }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="font-bold text-gray-900 text-lg leading-tight mb-2 line-clamp-2">{{ $item->title }}</h3>

                            @if($item->description)
                                <p class="text-gray-500 text-sm leading-relaxed line-clamp-3 mb-4">{{ $item->description }}</p>
                            @endif

                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>

                                <div class="flex items-center gap-1">
                                    {{-- Toggle publish --}}
                                    @can('news.edit')
                                        <button
                                            wire:click="togglePublished({{ $item->id }})"
                                            title="{{ $item->is_published ? 'Despublicar' : 'Publicar' }}"
                                            class="p-2 rounded-lg {{ $item->is_published ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }} transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item->is_published ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21' }}"/>
                                            </svg>
                                        </button>
                                    @endcan
                                    @can('news.edit')
                                        <button
                                            wire:click="openEditModal({{ $item->id }})"
                                            class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Editar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endcan
                                    @can('news.delete')
                                        <button
                                            wire:click="confirmDelete({{ $item->id }})"
                                            class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                                            title="Eliminar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $this->news->links() }}
        @endif
    </div>

    {{-- ──────────────────────────────────────── --}}
    {{-- CREATE / EDIT MODAL                     --}}
    {{-- ──────────────────────────────────────── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-6 pb-20">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8" wire:click.stop>
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $isEditing ? 'Editar Noticia' : 'Nueva Noticia' }}</h3>
                    </div>
                    <button wire:click="closeModal" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="save" class="p-6 space-y-5">

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Título <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            wire:model="title"
                            placeholder="Título de la noticia..."
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        />
                        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Descripción</label>
                        <textarea
                            wire:model="description"
                            rows="4"
                            placeholder="Descripción o cuerpo de la noticia..."
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm resize-none"
                        ></textarea>
                        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- File upload --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Imagen o PDF</label>

                        {{-- Current file preview --}}
                        @if($isEditing && $currentFilePath && !$removeFile)
                            <div class="mb-3 p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                                @if($currentFileType === 'image')
                                    <img src="{{ Storage::url($currentFilePath) }}" alt="" class="w-16 h-12 object-cover rounded-lg"/>
                                @else
                                    <div class="w-16 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $currentOriginalFilename ?? 'Archivo actual' }}</p>
                                    <p class="text-xs text-gray-500">{{ strtoupper($currentFileType) }}</p>
                                </div>
                                <button type="button" wire:click="$set('removeFile', true)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                    Quitar
                                </button>
                            </div>
                        @endif

                        {{-- New file input --}}
                        @if(!($isEditing && $currentFilePath && !$removeFile) || $removeFile || $file)
                            <div
                                x-data="{ dragging: false }"
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="dragging = false"
                                :class="dragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                                class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()"
                            >
                                <input
                                    type="file"
                                    x-ref="fileInput"
                                    wire:model="file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                                    class="hidden"
                                />
                                @if($file)
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-green-700">{{ $file->getClientOriginalName() }}</p>
                                        <p class="text-xs text-gray-500">{{ round($file->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-sm text-gray-600">Arrastra o <span class="text-blue-600 font-semibold">haz clic</span> para subir</p>
                                        <p class="text-xs text-gray-400">JPG, PNG, GIF, WEBP o PDF — máx. 10 MB</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                        {{-- File upload progress --}}
                        <div wire:loading wire:target="file" class="mt-2 flex items-center gap-2 text-blue-600 text-sm">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Subiendo archivo...
                        </div>
                    </div>

                    {{-- School visibility --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Visibilidad</label>
                        <div class="flex flex-col gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ $for_all_schools ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input
                                    type="radio"
                                    wire:model.live="for_all_schools"
                                    value="1"
                                    class="text-blue-600 border-gray-300 focus:ring-blue-500"
                                />
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Todos los colegios</p>
                                    <p class="text-xs text-gray-500">La noticia será visible para todos los colegios del sistema</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ !$for_all_schools ? 'border-purple-300 bg-purple-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input
                                    type="radio"
                                    wire:model.live="for_all_schools"
                                    value="0"
                                    class="text-purple-600 border-gray-300 focus:ring-purple-500"
                                />
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Colegios específicos</p>
                                    <p class="text-xs text-gray-500">Selecciona los colegios que verán esta noticia</p>
                                </div>
                            </label>
                        </div>

                        {{-- Schools multi-select --}}
                        @if(!$for_all_schools)
                            <div class="mt-3 p-3 bg-gray-50 rounded-xl border border-gray-200 max-h-48 overflow-y-auto space-y-2">
                                @foreach($schoolsList as $school)
                                    <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors">
                                        <input
                                            type="checkbox"
                                            wire:model="selectedSchools"
                                            value="{{ $school['id'] }}"
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <span class="text-sm text-gray-700">{{ $school['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedSchools')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- Published toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Publicar inmediatamente</p>
                            <p class="text-xs text-gray-500">Si está activo, los colegios podrán ver la noticia</p>
                        </div>
                        <button
                            type="button"
                            wire:click="$toggle('is_published')"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $is_published ? 'bg-green-500' : 'bg-gray-300' }}"
                        >
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_published ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-500 rounded-xl hover:from-blue-700 hover:to-blue-600 shadow-lg shadow-blue-500/25 transition-all disabled:opacity-50 flex items-center gap-2"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $isEditing ? 'Actualizar' : 'Publicar Noticia' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Guardando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ──────────────────────────────────────── --}}
    {{-- DELETE CONFIRMATION MODAL               --}}
    {{-- ──────────────────────────────────────── --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" wire:click.stop>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Eliminar Noticia</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="closeDeleteModal" class="px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button
                        wire:click="delete"
                        wire:loading.attr="disabled"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors shadow-lg shadow-red-500/25"
                    >
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
