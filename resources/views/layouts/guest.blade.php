<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sistemas y Proyectos Contables S.A.S</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
            @keyframes blob {
                0%   { transform: translate(0px, 0px) scale(1); }
                33%  { transform: translate(30px, -50px) scale(1.1); }
                66%  { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob { animation: blob 7s infinite; }
            .animation-delay-2000 { animation-delay: 2s; }
            .animation-delay-4000 { animation-delay: 4s; }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex">

            {{-- ===== PANEL IZQUIERDO: Branding ===== --}}
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-700 to-blue-500 flex-col items-center justify-center p-12 relative overflow-hidden">

                {{-- Blobs decorativos --}}
                <div class="absolute top-0 left-0 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                <div class="absolute bottom-0 right-0 w-80 h-80 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
                <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-teal-400 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000"></div>

                <div class="relative z-10 text-center max-w-sm">

                    {{-- Logo --}}
                    <div class="flex justify-center mb-7">
                        <div class="w-40 h-40 rounded-full bg-white/15 backdrop-blur-sm p-3 shadow-2xl border-2 border-white/30 ring-4 ring-white/10">
                            <img src="{{ asset('images/spc-logo.jpeg') }}" alt="Sistemas y Proyectos Contables S.A.S" class="w-full h-full object-contain drop-shadow-lg">
                        </div>
                    </div>

                    {{-- Nombre de la empresa --}}
                    <h1 class="text-4xl font-extrabold text-white leading-tight tracking-tight">
                        Sistemas y Proyectos<br>Contables S.A.S
                    </h1>
                    <p class="text-blue-200 mt-2 text-base font-medium tracking-widest uppercase">Bucaramanga</p>

                    {{-- Divisor --}}
                    <div class="flex items-center gap-4 my-8">
                        <div class="flex-1 h-px bg-white/20"></div>
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Contacto</span>
                        <div class="flex-1 h-px bg-white/20"></div>
                    </div>

                    {{-- Contactos --}}
                    <div class="space-y-4">

                        {{-- Teléfonos --}}
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/20">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="text-xs text-blue-200/70 uppercase tracking-widest font-semibold mb-0.5">Teléfonos</p>
                                <p class="text-white font-semibold text-sm">317 441 8493 &nbsp;&bull;&nbsp; 316 279 6262</p>
                            </div>
                        </div>

                        {{-- Correo --}}
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/20">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="text-xs text-blue-200/70 uppercase tracking-widest font-semibold mb-0.5">Correo</p>
                                <p class="text-white font-semibold text-sm">hildacontadora@sypsas.com</p>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <p class="text-white/30 text-xs mt-10">
                        &copy; {{ date('Y') }} Sistema de Presupuesto Escolar
                    </p>

                </div>
            </div>

            {{-- ===== PANEL DERECHO: Formulario ===== --}}
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center min-h-screen bg-gray-50 p-6">

                {{-- Branding móvil (solo en pantallas pequeñas) --}}
                <div class="lg:hidden flex flex-col items-center mb-8">
                    <div class="w-20 h-20 rounded-full bg-blue-600/10 border-2 border-blue-100 p-2 mb-3 shadow-md">
                        <img src="{{ asset('images/spc-logo.jpeg') }}" alt="SPC Logo" class="w-full h-full object-contain">
                    </div>
                    <h2 class="text-lg font-bold text-gray-800 text-center leading-snug">Sistemas y Proyectos Contables S.A.S</h2>
                    <p class="text-xs text-gray-500 mt-1 text-center">317 441 8493 &bull; hildacontadora@sypsas.com</p>
                </div>

                {{-- Tarjeta del formulario --}}
                <div class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-gray-200/80 border border-gray-100 p-8">
                    {{ $slot }}
                </div>

                <p class="lg:hidden text-center text-xs text-gray-400 mt-6">
                    &copy; {{ date('Y') }} Sistema de Presupuesto Escolar
                </p>
            </div>

        </div>
    </body>
</html>
