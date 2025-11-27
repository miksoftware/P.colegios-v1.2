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
- Alpine.js (via Livewire)

## Key Packages
- `spatie/laravel-permission` - Role and permission management
- `usernotnull/tall-toasts` - Toast notifications
- SQLite database (development)

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
# or
php artisan test
```

### Code Quality
```bash
vendor/bin/pint
# Laravel Pint for code formatting (PSR-12)
```

### Database
```bash
php artisan migrate
php artisan db:seed
```

### Asset Building
```bash
npm run dev    # Development with HMR
npm run build  # Production build
```

## Windows Environment
Project runs on Windows with cmd shell. Use appropriate path separators and commands.
