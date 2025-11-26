<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('school/select', App\Livewire\SchoolSelection::class)
    ->middleware(['auth'])
    ->name('school.select');

Route::get('school/manage', App\Livewire\SchoolManagement::class)
    ->middleware(['auth', 'verified'])
    ->name('school.manage');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
