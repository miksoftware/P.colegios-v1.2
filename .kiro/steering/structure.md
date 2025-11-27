---
inclusion: always
---

# Project Structure

## Application Code (`app/`)

### Models (`app/Models/`)
- Eloquent models with relationships
- Use `HasFactory`, `Notifiable`, `HasRoles` traits
- Define `$fillable` arrays for mass assignment
- Relationships: `belongsToMany` for User-School pivot

### Livewire Components (`app/Livewire/`)
- Full-page components with `->layout('layouts.app')`
- Public properties for form fields
- Validation in update methods
- Use `dispatch('notify')` for toast messages
- Forms in `app/Livewire/Forms/` subdirectory
- Actions in `app/Livewire/Actions/` subdirectory

### HTTP Layer (`app/Http/`)
- Controllers in `app/Http/Controllers/`
- Middleware in `app/Http/Middleware/`
- Custom middleware: `EnsureSchoolSelected` for school context

### View Components (`app/View/Components/`)
- Blade components: `AppLayout`, `GuestLayout`

## Views (`resources/views/`)

### Blade Templates
- `layouts/` - Layout components
- `livewire/` - Livewire component views
- `components/` - Reusable UI components
- Use `.blade.php` extension

### Livewire Views
- Match component namespace structure
- Located in `resources/views/livewire/`
- Example: `SchoolManagement.php` → `livewire/school-management.blade.php`

## Database (`database/`)

### Migrations (`database/migrations/`)
- Timestamped migration files
- Use descriptive names: `create_[table]_table`, `add_[fields]_to_[table]_table`

### Seeders (`database/seeders/`)
- `DatabaseSeeder` calls other seeders
- Separate seeders per model: `SchoolSeeder`, `UserSeeder`, `RoleSeeder`

## Frontend Assets

### Styles (`resources/css/`)
- `app.css` - Main stylesheet with Tailwind directives

### JavaScript (`resources/js/`)
- `app.js` - Application entry point
- `bootstrap.js` - Axios and Echo setup

## Configuration (`config/`)
- Laravel config files
- `permission.php` - Spatie Permission config
- `tall-toasts.php` - Toast notification config

## Routes (`routes/`)
- Web routes with Livewire component routing
- Auth routes from Breeze

## Testing (`tests/`)
- `Feature/` - Feature tests (HTTP, auth flows)
- `Unit/` - Unit tests
- Use PHPUnit

## Conventions

- PSR-4 autoloading
- PSR-12 code style (enforced by Pint)
- Eloquent relationships defined in models
- Session-based school context (`selected_school_id`)
- Spanish language for user-facing messages
- English for code and comments
