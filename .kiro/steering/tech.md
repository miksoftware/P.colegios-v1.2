---
inclusion: always
---

# Tech Stack

## Core Framework
- Laravel 12 (PHP 8.2+)
- Livewire 3.6 + Volt for reactive components
- Laravel Breeze for authentication scaffolding

## Frontend
- Tailwind CSS 3 with @tailwindcss/forms
- Vite for asset bundling
- Alpine.js (via Livewire) for interactive components

## Key Packages
- `spatie/laravel-permission` - Role and permission management
- `usernotnull/tall-toasts` - Toast notifications
- MySQL database

## UI Components

### Searchable Select
```blade
<x-searchable-select
    wire:model="field_name"
    :options="$arrayOfOptions"
    placeholder="Select..."
    searchPlaceholder="Search..."
/>
```
Options format: `[['id' => 1, 'name' => 'Display Text'], ...]`

## Common Commands

### Setup
```bash
composer run setup
```

### Development
```bash
composer run dev
# Runs: server, queue, logs (pail), and vite concurrently
```

### Testing
```bash
composer run test
```

### Code Quality
```bash
vendor/bin/pint
```

### Database
```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=SpecificSeeder
php artisan permission:cache-reset  # After permission changes
```

### Asset Building
```bash
npm run dev    # Development with HMR
npm run build  # Production build
```

## Creating New Modules

1. **Model**: Create in `app/Models/` with `school_id` FK if school-scoped
2. **Migration**: Create table with proper FKs and indexes
3. **Livewire Component**: In `app/Livewire/` with `forSchool()` queries
4. **View**: In `resources/views/livewire/`
5. **Permission Seeder**: Create module + permissions with `updateOrCreate`
6. **Route**: Add to `routes/web.php` with middleware
7. **Menu**: Add to `resources/views/layouts/app.blade.php`

### Permission Seeder Template
```php
$module = Module::firstOrCreate(
    ['name' => 'module_name'],
    ['display_name' => 'Display Name', 'icon' => 'icon-name', 'order' => 50]
);

$permissions = [
    'module_name.view' => 'Ver items',
    'module_name.create' => 'Crear items',
    'module_name.edit' => 'Editar items',
    'module_name.delete' => 'Eliminar items',
];

foreach ($permissions as $name => $displayName) {
    Permission::updateOrCreate(
        ['name' => $name, 'guard_name' => 'web'],
        ['display_name' => $displayName, 'module_id' => $module->id]
    );
}
```

## Windows Environment
Project runs on Windows with cmd shell. Use `;` for command separation in PowerShell.
