<div class="min-h-screen bg-gradient-to-br from-blue-600 via-blue-400 to-white flex items-center justify-center p-4 sm:p-6 lg:p-8 relative overflow-hidden">
    <!-- Decorative Circles -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    
    <!-- School Selection Container -->
    <div class="w-full max-w-7xl relative z-10">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-2xl shadow-blue-500/30 mb-6 transform hover:scale-105 transition-transform duration-300">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3 drop-shadow-lg">
                Selecciona un Colegio
            </h1>
            <p class="text-blue-100 text-lg sm:text-xl">Elige la institución que deseas administrar</p>
        </div>

        <!-- Schools Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($schools as $school)
                <button
                    wire:click="selectSchool({{ $school->id }})"
                    class="group bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl shadow-gray-200/50 p-6 border border-white/20 hover:shadow-2xl hover:shadow-blue-500/20 hover:-translate-y-1 transition-all duration-300 text-left overflow-hidden relative school-card"
                >
                    <!-- Gradient Overlay on Hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-teal-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                    
                    <div class="relative">
                        <!-- Icon -->
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-blue-500/30">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>

                        <!-- School Name -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                            {{ $school->name }}
                        </h3>

                        <!-- School Info -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="font-medium">NIT:</span>
                                <span>{{ $school->nit }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="font-medium">Municipio:</span>
                                <span>{{ $school->municipality }}</span>
                            </div>
                        </div>

                        <!-- Arrow Icon -->
                        <div class="mt-4 flex items-center justify-end">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-500 rounded-full flex items-center justify-center group-hover:translate-x-1 transition-transform duration-300">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        <!-- Footer -->
        <p class="text-center text-sm text-white/80 mt-12 drop-shadow">
            © 2025 Sistema de Presupuesto Escolar
        </p>
    </div>

    <!-- Add entrance animation to cards -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.school-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</div>
