<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Masthead ──────────────────────────────────── --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase tracking-widest rounded-full mb-4">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"/>
                    <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z"/>
                </svg>
                Noticias
            </div>
            <h1 class="text-4xl sm:text-5xl font-black text-gray-900 tracking-tight mb-2">
                Tablón de <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500">Noticias</span>
            </h1>
            <p class="text-gray-500 text-base">Información y comunicados de tu institución</p>
            <div class="mt-4 flex items-center justify-center gap-2">
                <div class="h-px bg-gradient-to-r from-transparent via-blue-300 to-transparent w-24"></div>
                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                <div class="h-px bg-gradient-to-r from-transparent via-blue-300 to-transparent w-24"></div>
            </div>
        </div>

        {{-- ── News grid ─────────────────────────────────── --}}
        @if($this->news->isEmpty())
            <div class="bg-white/70 backdrop-blur-sm rounded-3xl border border-gray-100 shadow-sm p-20 text-center">
                <div class="w-20 h-20 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Sin noticias por el momento</h3>
                <p class="text-gray-400">Aquí aparecerán las noticias y comunicados de tu institución</p>
            </div>
        @else
            {{-- First news (featured / hero) --}}
            @php $featured = $this->news->first(); @endphp
            <div
                wire:click="openNews({{ $featured->id }})"
                class="group cursor-pointer mb-8 bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    {{-- Featured image / PDF preview --}}
                    <div class="relative h-64 lg:h-auto overflow-hidden bg-slate-800">
                        @if($featured->file_path && $featured->file_type === 'image')
                            <img
                                src="{{ Storage::url($featured->file_path) }}"
                                alt="{{ $featured->title }}"
                                class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500"
                            />
                        @elseif($featured->file_path && $featured->file_type === 'pdf')
                            <div class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900 flex flex-col items-center justify-center gap-3">
                                <div class="w-20 h-20 bg-white/15 rounded-2xl flex items-center justify-center shadow-lg border border-white/20">
                                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <span class="text-white font-bold text-lg">Documento PDF</span>
                                <span class="px-4 py-1.5 bg-white/20 text-white text-sm rounded-full border border-white/40">
                                    Click para ver
                                </span>
                            </div>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-20 h-20 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-lg">
                                Destacado
                            </span>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-8 lg:p-10 flex flex-col justify-center">
                        <div class="flex items-center gap-2 text-xs text-gray-400 mb-4">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ $featured->created_at->translatedFormat('d \d\e F \d\e Y') }}
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-black text-gray-900 leading-tight mb-4 group-hover:text-blue-600 transition-colors">
                            {{ $featured->title }}
                        </h2>
                        @if($featured->description)
                            <p class="text-gray-500 leading-relaxed line-clamp-4 text-base">
                                {{ $featured->description }}
                            </p>
                        @endif
                        <div class="mt-6 inline-flex items-center gap-2 text-blue-600 font-semibold text-sm group-hover:gap-3 transition-all">
                            Leer más
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rest of news (grid) --}}
            @if($this->news->count() > 1)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    @foreach($this->news->skip(1) as $item)
                        <div
                            wire:click="openNews({{ $item->id }})"
                            class="group cursor-pointer bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col"
                        >
                            {{-- Thumbnail --}}
                            <div class="h-44 overflow-hidden flex-shrink-0 relative bg-slate-800">
                                @if($item->file_path && $item->file_type === 'image')
                                    <img
                                        src="{{ Storage::url($item->file_path) }}"
                                        alt="{{ $item->title }}"
                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                    />
                                @elseif($item->file_path && $item->file_type === 'pdf')
                                    <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-red-100 flex flex-col items-center justify-center gap-2">
                                        <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-xs font-bold text-red-500 uppercase tracking-wide">PDF</span>
                                    </div>
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-5 flex flex-col flex-1">
                                <p class="text-xs text-gray-400 mb-2">
                                    {{ $item->created_at->translatedFormat('d \d\e F \d\e Y') }}
                                </p>
                                <h3 class="font-bold text-gray-900 text-base leading-snug mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                                    {{ $item->title }}
                                </h3>
                                @if($item->description)
                                    <p class="text-gray-500 text-sm leading-relaxed line-clamp-3 flex-1">
                                        {{ $item->description }}
                                    </p>
                                @endif
                                <div class="mt-4 flex items-center gap-1 text-blue-600 text-xs font-semibold">
                                    Leer más
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{ $this->news->links() }}
        @endif
    </div>

    {{-- ────────────────────────────────────── --}}
    {{-- NEWS READER MODAL                     --}}
    {{-- ────────────────────────────────────── --}}
    @if($showViewer && $selectedNews)
    <div
        class="fixed inset-0 z-50 overflow-y-auto"
        x-data
        @keydown.escape.window="$wire.closeViewer()"
    >
        <div class="flex items-start justify-center min-h-screen px-4 pt-6 pb-20">
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm"
                wire:click="closeViewer"
            ></div>

            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl my-8 overflow-hidden" wire:click.stop>

                {{-- Close button --}}
                <button
                    wire:click="closeViewer"
                    class="absolute top-4 right-4 z-10 w-9 h-9 bg-white/90 backdrop-blur-sm rounded-full shadow-md flex items-center justify-center hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- Image viewer --}}
                @if($selectedNews->file_path && $selectedNews->file_type === 'image')
                    <div class="relative bg-black max-h-[55vh] flex items-center justify-center overflow-hidden">
                        <img
                            src="{{ Storage::url($selectedNews->file_path) }}"
                            alt="{{ $selectedNews->title }}"
                            class="max-w-full max-h-[55vh] object-contain"
                        />
                    </div>
                @endif

                {{-- Content body --}}
                <div class="p-8">
                    {{-- Meta --}}
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full uppercase tracking-wide">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"/>
                            </svg>
                            Noticia
                        </span>
                        <span class="text-sm text-gray-400">
                            {{ $selectedNews->created_at->translatedFormat('d \d\e F \d\e Y') }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 leading-tight mb-5">
                        {{ $selectedNews->title }}
                    </h2>

                    {{-- Description --}}
                    @if($selectedNews->description)
                        <div class="prose prose-slate prose-base max-w-none">
                            <p class="text-gray-600 leading-relaxed text-base whitespace-pre-line">{{ $selectedNews->description }}</p>
                        </div>
                    @endif

                    {{-- PDF Viewer --}}
                    @if($selectedNews->file_path && $selectedNews->file_type === 'pdf')
                        <div class="mt-6">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-semibold text-gray-700 text-sm">Documento adjunto</span>
                                @if($selectedNews->original_filename)
                                    <span class="text-xs text-gray-400">— {{ $selectedNews->original_filename }}</span>
                                @endif
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-inner bg-gray-50">
                                <object
                                    data="{{ Storage::url($selectedNews->file_path) }}"
                                    type="application/pdf"
                                    class="w-full"
                                    style="height: 600px;"
                                >
                                    <div class="flex flex-col items-center justify-center py-12 gap-4">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-gray-500 text-sm">Tu navegador no puede mostrar el PDF directamente.</p>
                                        <a
                                            href="{{ Storage::url($selectedNews->file_path) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Descargar PDF
                                        </a>
                                    </div>
                                </object>
                            </div>
                        </div>
                    @endif

                    {{-- Download image link --}}
                    @if($selectedNews->file_path && $selectedNews->file_type === 'image')
                        <div class="mt-6 flex items-center gap-3">
                            <a
                                href="{{ Storage::url($selectedNews->file_path) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-xl border border-gray-200 hover:border-blue-200 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Ver imagen completa
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
