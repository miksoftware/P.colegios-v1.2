<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sistema de Presupuesto Escolar</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .glass-morphism {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
        </style>
    </head>
    <body class="antialiased">
        <!-- Elegant Blue to White Gradient Background -->
        <div class="min-h-screen bg-gradient-to-br from-blue-600 via-blue-400 to-white flex items-center justify-center p-4 relative overflow-hidden">
            <!-- Decorative Circles -->
            <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
            
            <!-- Login Container -->
            <div class="w-full max-w-md relative z-10">
                <!-- Logo and Branding -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-2xl shadow-blue-500/30 mb-6 transform hover:scale-105 transition-transform duration-300">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 object-contain">
                    </div>
                    <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">
                        Sistema de Presupuesto Escolar
                    </h1>
                    <p class="text-blue-100 text-lg">Gestión contable para instituciones educativas</p>
                </div>

                <!-- Login Card with Glass Morphism -->
                <div class="glass-morphism rounded-3xl shadow-2xl p-8 border border-white/20">
                    {{ $slot }}
                </div>
                
                <p class="text-center text-sm text-white/80 mt-8 drop-shadow">
                    © 2025 Sistema de Presupuesto Escolar
                </p>
            </div>
        </div>
        
        <style>
            @keyframes blob {
                0% {
                    transform: translate(0px, 0px) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
                100% {
                    transform: translate(0px, 0px) scale(1);
                }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    </body>
</html>
