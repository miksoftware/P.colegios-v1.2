<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('school/info', App\Livewire\SchoolInfo::class)
    ->middleware(['auth', 'verified', 'can:school_info.view'])
    ->name('school.info');

Route::get('school/manage', App\Livewire\SchoolManagement::class)
    ->middleware(['auth', 'verified'])
    ->name('school.manage');

Route::get('users', App\Livewire\UserManagement::class)
    ->middleware(['auth', 'verified', 'can:users.view'])
    ->name('users.index');

Route::get('roles', App\Livewire\RoleManagement::class)
    ->middleware(['auth', 'verified', 'can:roles.view'])
    ->name('roles.index');

Route::get('accounting-accounts', App\Livewire\AccountingAccountManagement::class)
    ->middleware(['auth', 'verified', 'can:accounting_accounts.view'])
    ->name('accounting.accounts');

Route::get('activity-logs', App\Livewire\ActivityLogViewer::class)
    ->middleware(['auth', 'verified', 'can:activity_logs.view'])
    ->name('activity.logs');

Route::get('suppliers', App\Livewire\SupplierManagement::class)
    ->middleware(['auth', 'verified', 'can:suppliers.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('suppliers.index');

Route::get('budget-items', App\Livewire\BudgetItemManagement::class)
    ->middleware(['auth', 'verified', 'can:budget_items.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('budget.items');

Route::get('budgets', App\Livewire\BudgetManagement::class)
    ->middleware(['auth', 'verified', 'can:budgets.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('budgets.index');

Route::get('funding-sources', App\Livewire\FundingSourceManagement::class)
    ->middleware(['auth', 'verified', 'can:funding_sources.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('funding-sources.index');

Route::get('budget-transfers', App\Livewire\BudgetTransferManagement::class)
    ->middleware(['auth', 'verified', 'can:budget_transfers.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('budget-transfers.index');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
