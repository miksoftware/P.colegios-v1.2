---
inclusion: always
---

# Project Structure

## Arquitectura General

El proyecto sigue la estructura estándar de Laravel 12 con componentes Livewire 3 para interfaces reactivas.

```
app/
├── Console/Commands/     # Comandos Artisan personalizados
├── Http/
│   ├── Controllers/      # Controladores HTTP (mínimo uso, Livewire maneja la lógica)
│   └── Middleware/       # Middleware personalizado
├── Livewire/             # Componentes Livewire (full-page y parciales)
├── Models/               # Modelos Eloquent
├── Providers/            # Service Providers
├── Traits/               # Traits reutilizables
└── View/Components/      # Componentes Blade

resources/views/
├── components/           # Componentes Blade reutilizables
├── layouts/              # Layouts de la aplicación
└── livewire/            # Vistas de componentes Livewire

database/
├── migrations/           # Migraciones de base de datos
├── seeders/              # Seeders para datos iniciales
└── factories/            # Factories para testing
```

---

## Modelos (`app/Models/`)

### User (Usuario)
```php
// Relaciones
- schools(): BelongsToMany (pivot: school_user)
- roles: HasRoles (Spatie)

// Métodos
- isAdmin(): bool

// Campos fillable
name, surname, identification_type, identification_number, email, password
```

### School (Colegio)
```php
// Relaciones
- users(): BelongsToMany

// Campos fillable (33 campos)
name, nit, dane_code, municipality, rector_name, rector_document, 
pagador_name, address, email, phone, website, budget_agreement_number,
budget_approval_date, current_validity, contracting_manual_approval_number,
contracting_manual_approval_date, dian_resolution_1/2, dian_range_1/2, 
dian_expiration_1/2
```

### AccountingAccount (Cuenta Contable)
```php
// Traits
- LogsActivity

// Relaciones
- parent(): BelongsTo (self-referencing)
- children(): HasMany (self-referencing, ordenado por code)
- childrenRecursive(): HasMany (recursivo para árbol)

// Constantes
LEVELS = [1 => 'Clase', 2 => 'Grupo', 3 => 'Cuenta', 4 => 'Subcuenta', 5 => 'Auxiliar']
LEVEL_COLORS = [1 => 'bg-purple-100...', ...]

// Accessors
- level_name: nombre del nivel
- level_color: clases Tailwind para el nivel
- nature_name: 'Débito' o 'Crédito'
- full_path: jerarquía completa "Padre → Hijo → ..."

// Métodos
- hasChildren(): bool
- getNextChildCode(): string (código sugerido para hijo)
- getNextClassCode(): static string (código para nueva clase)

// Scopes
- roots(): cuentas sin padre
- active(): is_active = true

// Campos fillable
code, name, description, level, parent_id, nature, allows_movement, is_active
```

### Supplier (Proveedor)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- department(): BelongsTo
- municipality(): BelongsTo

// Constantes
DOCUMENT_TYPES = ['CC' => 'Cédula...', 'NIT' => 'NIT', ...]
PERSON_TYPES = ['natural' => 'Persona Natural', 'juridica' => 'Persona Jurídica']
TAX_REGIMES = ['simplificado', 'comun', 'gran_contribuyente', 'no_responsable']
ACCOUNT_TYPES = ['ahorros', 'corriente']

// Accessors
- full_name: nombre completo o razón social
- full_document: documento con DV si aplica
- document_type_name, person_type_name, tax_regime_name
- city: nombre del municipio

// Métodos estáticos
- calculateDv(string $nit): string (calcula dígito verificación)

// Scopes
- forSchool($schoolId), active(), search($search)

// Campos fillable (21 campos)
school_id, document_type, document_number, dv, first_name, second_name,
first_surname, second_surname, person_type, tax_regime, address,
department_id, municipality_id, phone, mobile, email, bank_name,
account_type, account_number, is_active, notes
```

### BudgetItem (Rubro Presupuestal)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- accountingAccount(): BelongsTo
- fundingSources(): HasMany

// Accessors
- full_code: código de cuenta + código de rubro

// Scopes
- forSchool($schoolId), active(), search($search)

// Campos fillable
school_id, accounting_account_id, code, name, description, is_active
```

### Budget (Presupuesto)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- budgetItem(): BelongsTo
- modifications(): HasMany (ordenado por modification_number)
- outgoingTransfers(): HasMany (traslados donde es origen)
- incomingTransfers(): HasMany (traslados donde es destino)

// Constantes
TYPES = ['income' => 'Ingreso', 'expense' => 'Gasto']

// Accessors
- type_name, type_color
- total_additions: suma de adiciones
- total_reductions: suma de reducciones
- total_contracreditos: suma de traslados salientes
- total_creditos: suma de traslados entrantes

// Métodos
- getNextModificationNumber(): int
- recalculateCurrentAmount(): void (recalcula y guarda)

// Scopes
- forSchool($schoolId), forYear($year), active(), byType($type)

// Campos fillable
school_id, budget_item_id, type, initial_amount, current_amount,
fiscal_year, description, is_active
```

### BudgetModification (Modificación Presupuestal)
```php
// Traits
- LogsActivity

// Relaciones
- budget(): BelongsTo
- creator(): BelongsTo (User)

// Constantes
TYPES = ['addition' => 'Adición', 'reduction' => 'Reducción']

// Accessors
- type_name, type_color
- formatted_number: número con padding (001, 002...)

// Campos fillable
budget_id, modification_number, type, amount, previous_amount, new_amount,
reason, document_number, document_date, created_by
```

### FundingSource (Fuente de Financiación)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- budgetItem(): BelongsTo
- incomes(): HasMany

// Constantes
TYPES = ['internal' => 'Interna', 'external' => 'Externa']

// Accessors
- type_name, type_color
- total_executed: suma de ingresos
- available_balance: saldo disponible (ingresos - salientes + entrantes)

// Métodos
- getAvailableBalanceForYear(int $year): float

// Scopes
- forSchool($schoolId), active(), byType($type), search($search)

// Campos fillable
school_id, budget_item_id, name, type, description, is_active
```

### Income (Ingreso Real)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- fundingSource(): BelongsTo
- creator(): BelongsTo (User)

// Scopes
- forSchool($schoolId), forYear($year), search($search)

// Campos fillable
school_id, funding_source_id, name, description, amount, date,
payment_method, transaction_reference, created_by
```

### BudgetTransfer (Traslado Presupuestal)
```php
// Traits
- LogsActivity

// Relaciones
- school(): BelongsTo
- sourceBudget(): BelongsTo (Budget)
- destinationBudget(): BelongsTo (Budget)
- sourceFundingSource(): BelongsTo (FundingSource)
- destinationFundingSource(): BelongsTo (FundingSource)
- creator(): BelongsTo (User)

// Accessors
- formatted_number: número con padding (001, 002...)

// Métodos estáticos
- getNextTransferNumber(int $schoolId, int $fiscalYear): int

// Scopes
- forSchool($schoolId), forYear($year), search($search)

// Campos fillable (16 campos)
school_id, transfer_number, source_budget_id, source_funding_source_id,
destination_budget_id, destination_funding_source_id, amount,
source_previous_amount, source_new_amount, destination_previous_amount,
destination_new_amount, reason, document_number, document_date,
fiscal_year, created_by
```

### Department (Departamento)
```php
// Relaciones
- municipalities(): HasMany

// Campos fillable
name, dian_code
```

### Municipality (Municipio)
```php
// Relaciones
- department(): BelongsTo

// Campos fillable
name, dian_code, department_id
```

### Module (Módulo)
```php
// Relaciones
- permissions(): HasMany

// Campos fillable
name, display_name, icon, order
```

### Permission (Permiso)
```php
// Extiende Spatie\Permission\Models\Permission

// Relaciones
- module(): BelongsTo

// Campos fillable
name, display_name, guard_name, module_id
```

### ActivityLog (Registro de Actividad)
```php
// Relaciones
- user(): BelongsTo
- school(): BelongsTo
- subject(): MorphTo (modelo relacionado)

// Accessors
- module_display_name: nombre legible del módulo
- action_display_name: acción legible (Creó, Actualizó, Eliminó)

// Métodos estáticos
- log(action, model, module, description, oldValues, newValues): self

// Campos fillable
user_id, school_id, action, model_type, model_id, module,
description, old_values, new_values, ip_address, user_agent

// Casts
old_values => array, new_values => array
```

---

## Componentes Livewire (`app/Livewire/`)

### Convenciones
- Atributo `#[Layout('layouts.app')]` para páginas completas
- Propiedades públicas para campos de formulario
- Propiedades computadas con `get[Name]Property()` para consultas
- Notificaciones con `$this->dispatch('toast', message: '...', type: 'success|error')`
- Scope `forSchool($schoolId)` para filtrar por colegio en sesión

### Componentes Principales

#### AccountingAccountManagement
```php
// Funcionalidad: CRUD de cuentas contables con árbol jerárquico
// Propiedades: accountId, code, name, level, parentId, nature, allowsMovement, isActive
// Features: expandir/colapsar árbol, eliminación con doble confirmación
```

#### ActivityLogViewer
```php
// Funcionalidad: Visor de logs de actividad
// Filtros: school, user, module, action, dateFrom, dateTo
// Features: modal de detalle con valores old/new
```

#### BudgetItemManagement
```php
// Funcionalidad: CRUD de rubros presupuestales
// Propiedades: code, name, accounting_account_id, is_active
// Features: solo permite vincular cuentas auxiliares (nivel 5)
```

#### BudgetManagement
```php
// Funcionalidad: CRUD de presupuestos + modificaciones
// Propiedades: budget_item_id, type, initial_amount, fiscal_year
// Features: modal de modificación, historial de cambios, toggle estado
```

#### BudgetTransferManagement
```php
// Funcionalidad: Crear traslados entre fuentes de financiación
// Flujo: rubro origen → fuente origen → rubro destino → fuente destino
// Features: validación de saldo disponible, numeración automática
```

#### FundingSourceManagement
```php
// Funcionalidad: CRUD de fuentes de financiación
// Propiedades: budget_item_id, name, type, is_active
// Features: ver saldo disponible por fuente
```

#### IncomeManagement
```php
// Funcionalidad: CRUD de ingresos reales
// Propiedades: funding_source_id, name, amount, date, payment_method
// Features: resumen de ejecución presupuestal
```

#### RoleManagement
```php
// Funcionalidad: CRUD de roles con permisos
// Propiedades: name, selectedPermissions[]
// Features: permisos agrupados por módulo
```

#### SchoolInfo
```php
// Funcionalidad: Ver/editar información del colegio (usuarios normales)
// Features: auto-selección de colegio asignado
```

#### SchoolManagement
```php
// Funcionalidad: Ver/editar colegio seleccionado
// Features: todos los campos del colegio
```

#### SchoolSelect
```php
// Funcionalidad: Modal de selección de colegios (Admin)
// Features: CRUD de colegios, búsqueda, paginación
```

#### SupplierManagement
```php
// Funcionalidad: CRUD de proveedores
// Features: cálculo automático de DV, cascada departamento→municipio
```

#### UserManagement
```php
// Funcionalidad: CRUD de usuarios
// Features: asignación de roles, vinculación a colegios
```

---

## Middleware (`app/Http/Middleware/`)

### EnsureSchoolSelected
```php
// Propósito: Garantizar que hay colegio seleccionado
// Comportamiento:
// - Admin: pasa sin restricción
// - Usuario normal: auto-selecciona primer colegio si no hay
// - Sin colegios: redirige a login con error
```

---

## Traits (`app/Traits/`)

### LogsActivity
```php
// Propósito: Registrar automáticamente cambios en modelos
// Eventos capturados: created, updated, deleted
// Campos ignorados: created_at, updated_at, remember_token, password

// Métodos a implementar en el modelo:
- getActivityModule(): string  // nombre del módulo
- getLogDescription(): string  // descripción del registro
```

---

## Base de Datos

### Tablas Principales
| Tabla | Relaciones | Índices |
|-------|------------|---------|
| users | schools (pivot) | email |
| schools | users (pivot) | nit |
| school_user | - | school_id, user_id |
| accounting_accounts | parent_id (self) | code, parent_id |
| suppliers | school, department, municipality | school_id+document |
| budget_items | school, accounting_account | school_id+code |
| budgets | school, budget_item, modifications | school_id+item+year |
| budget_modifications | budget, creator | budget_id |
| funding_sources | school, budget_item, incomes | school_id+type |
| incomes | school, funding_source, creator | school_id+date |
| budget_transfers | school, budgets, funding_sources | school_id+year |
| departments | municipalities | - |
| municipalities | department | department_id |
| modules | permissions | name |
| permissions | module | name |
| roles | permissions (pivot) | name |
| model_has_permissions | - | - |
| model_has_roles | - | - |
| role_has_permissions | - | - |
| activity_logs | user, school | module, action, dates |

### Migraciones Clave
```
2025_11_25_033220_create_schools_table.php
2025_11_25_033224_create_school_user_table.php
2025_11_26_040518_create_permission_tables.php (Spatie)
2025_11_29_010000_create_modules_table.php
2025_11_29_120000_create_activity_logs_table.php
2025_11_29_120001_create_accounting_accounts_table.php
2025_12_01_100000_create_suppliers_table.php
2025_12_14_000001_create_departments_table.php
2025_12_14_000002_create_municipalities_table.php
2025_12_14_000004_create_budget_items_table.php
2025_12_16_000001_create_budgets_table.php
2025_12_16_000002_create_budget_modifications_table.php
2025_12_17_000001_create_funding_sources_table.php
2025_12_26_000001_create_budget_transfers_table.php
2025_12_27_000001_create_incomes_table.php
2026_01_03_000001_add_funding_source_to_budget_transfers.php
```

### Seeders
| Seeder | Propósito |
|--------|-----------|
| DatabaseSeeder | Orquestador principal |
| ModulePermissionSeeder | Módulos + permisos base |
| SchoolSeeder | Colegio de prueba |
| UserSeeder | Usuario admin de prueba |
| AccountingAccountSeeder | PUC completo (~600 cuentas) |
| DepartmentSeeder | 33 departamentos colombianos |
| MunicipalitySeeder | Municipios con códigos DIAN |
| BudgetItemSeeder | Rubros de ejemplo |
| BudgetSeeder | Presupuestos de ejemplo |
| BudgetItemPermissionSeeder | Permisos de rubros |
| BudgetPermissionSeeder | Permisos de presupuestos |
| FundingSourcePermissionSeeder | Permisos de fuentes |
| IncomePermissionSeeder | Permisos de ingresos |
| BudgetTransferPermissionSeeder | Permisos de traslados |

---

## Rutas (`routes/web.php`)

### Rutas Principales
```php
// Dashboard y Perfil
GET /dashboard          → view('dashboard')          [auth, verified, EnsureSchool]
GET /profile            → view('profile')            [auth]

// Gestión de Información
GET /school/info        → SchoolInfo                 [auth, can:school_info.view]
GET /school/manage      → SchoolManagement           [auth, verified]
GET /users              → UserManagement             [auth, can:users.view]
GET /roles              → RoleManagement             [auth, can:roles.view]
GET /activity-logs      → ActivityLogViewer          [auth, can:activity_logs.view]

// Contabilidad
GET /accounting-accounts → AccountingAccountManagement [auth, can:accounting_accounts.view]

// Gestión Escolar (requiere colegio seleccionado)
GET /suppliers          → SupplierManagement         [auth, can, EnsureSchool]
GET /budget-items       → BudgetItemManagement       [auth, can, EnsureSchool]
GET /budgets            → BudgetManagement           [auth, can, EnsureSchool]
GET /funding-sources    → FundingSourceManagement    [auth, can, EnsureSchool]
GET /incomes            → IncomeManagement           [auth, can, EnsureSchool]
GET /budget-transfers   → BudgetTransferManagement   [auth, can, EnsureSchool]
```

### Middleware Stack
- `auth`: Usuario autenticado
- `verified`: Email verificado
- `can:permission`: Verificación de permiso Spatie
- `EnsureSchoolSelected`: Colegio en sesión

---

## Vistas (`resources/views/`)

### Layout Principal (`layouts/app.blade.php`)
```
├── Sidebar (barra lateral)
│   ├── Logo
│   ├── Navegación principal
│   │   ├── Dashboard
│   │   └── Registro de Información (expandible)
│   │       ├── Usuarios
│   │       ├── Colegios (Admin) / Mi Colegio (otros)
│   │       ├── Roles y Permisos
│   │       ├── Cuentas Contables
│   │       ├── Registro de Actividad
│   │       └── Proveedores
│   ├── Presupuesto (expandible)
│   │   ├── Rubros
│   │   ├── Presupuestos
│   │   ├── Fuentes de Financiación
│   │   ├── Ingresos
│   │   └── Traslados
│   └── Perfil de usuario
└── Contenido principal (slot)
```

### Componentes Blade (`components/`)
- `searchable-select.blade.php`: Select con búsqueda (Alpine.js)
- `modal.blade.php`: Modal reutilizable
- `toast-notification.blade.php`: Notificaciones toast

### Convenciones de Vistas Livewire
- Nombre coincide con componente: `BudgetItemManagement` → `budget-item-management.blade.php`
- Uso de `wire:model` para binding bidireccional
- `wire:click` para acciones
- `x-data` de Alpine.js para interactividad local
- Clase Tailwind para estilos

---

## Convenciones del Proyecto

### Nomenclatura
- **Modelos**: Singular, PascalCase (`BudgetItem`)
- **Tablas**: Plural, snake_case (`budget_items`)
- **Componentes Livewire**: PascalCase (`BudgetItemManagement`)
- **Vistas Livewire**: kebab-case (`budget-item-management.blade.php`)
- **Permisos**: `modulo.accion` (`budget_items.view`)

### Multi-Tenancy (por colegio)
- FK `school_id` en tablas de datos
- Scope `forSchool($schoolId)` en modelos
- Sesión `selected_school_id` para colegio activo

### Soft-Disable Pattern
- Campo `is_active` (boolean) para desactivar sin eliminar
- Scope `active()` para filtrar activos

### Logging
- Trait `LogsActivity` en modelos auditables
- Método `getActivityModule()` retorna nombre del módulo
- Método `getLogDescription()` retorna descripción del registro

### Idioma
- **UI**: Español
- **Código**: Inglés (variables, métodos, clases)
