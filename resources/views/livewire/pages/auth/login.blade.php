<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>
<div class="relative" x-data="{ isLoading: false }">
    <!-- Login Form Container -->
    <div class="w-full sm:max-w-md px-6 py-8">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl shadow-2xl shadow-blue-500/30 flex items-center justify-center transform hover:scale-105 transition-transform duration-300">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 object-contain">
            </div>
        </div>

        <!-- Title -->
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-2">Bienvenido</h2>
        <p class="text-center text-gray-600 mb-8">Ingresa a tu cuenta</p>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login" @submit="isLoading = true" class="space-y-6">
            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">
                    Correo Electrónico
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <input
                        wire:model="form.email"
                        id="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="tu@email.com"
                    >
                </div>
                <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-900 mb-2">
                    Contraseña
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input
                        wire:model="form.password"
                        id="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="••••••••"
                    >
                </div>
                <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input
                    wire:model="form.remember"
                    id="remember"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                >
                <label for="remember" class="ml-2 text-sm text-gray-700">
                    Recordarme
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-300 transform hover:-translate-y-0.5 disabled:transform-none"
            >
                <span wire:loading.remove>Iniciar Sesión</span>
                <span wire:loading class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Iniciando Sesión...
                </span>
            </button>

            <!-- Forgot Password Link -->
            @if (Route::has('password.request'))
                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            @endif
        </form>

        <!-- Demo Credentials -->
        <div class="mt-8 p-4 bg-blue-50 rounded-xl border border-blue-100">
            <p class="text-xs text-blue-800 font-semibold mb-2">Credenciales de Prueba:</p>
            <p class="text-xs text-blue-700"><span class="font-semibold">Admin:</span> admin@admin.com / password</p>
            <p class="text-xs text-blue-700"><span class="font-semibold">Usuario:</span> edgar@sanjuan.edu.co / password</p>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div 
        x-show="isLoading"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="flex flex-col items-center">
                <div class="relative w-16 h-16 mb-4">
                    <div class="absolute inset-0 border-4 border-blue-200 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Iniciando Sesión...</h3>
                <p class="text-sm text-gray-500 mt-2">Por favor espera...</p>
            </div>
        </div>
    </div>
</div>
