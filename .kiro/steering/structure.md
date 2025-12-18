---
inclusion: always
---

# Project Structure

## Application Code (`app/`)

### Models (`app/Models/`)
- `User` - Users with HasRoles trait
- `School` - Educational institutions
- `AccountingAccount` - Chart of accounts (5 levels)
- `Supplier` - Vendors/providers
- `BudgetItem` - Budget line items (rubros)
- `Budget` - Budget per item with initial/current amounts, type (income/expense)
- `BudgetModification` - Budget modifications (additions/reductions) with tracking
- `FundingSource` - Funding sources (internal/external) linked to budget items
- `Department` - Colombian departments
- `Municipality` - Colombian municipalities
- `Module` - Permission modules for organization
- `Permission` - Extended Spatie Permission with module_id
- `ActivityLog` - Audit trail

### Livewire Components (`app/Livewire/`)
- Full-page components with `#[Layout('layouts.app')]`
- Public properties for form fields
- Computed properties with `get[Name]Property()` for queries
- Use `dispatch('toast')` for notifications
- `forSchool($schoolId)` scope for multi-tenant queries

### HTTP Layer (`app/Http/`)
- Middleware `EnsureSchoolSelected` - Auto-selects school for session

## Views (`resources/views/`)

### Reusable Components (`components/`)
- `searchable-select.blade.php` - Select with search (Alpine.js)
- `modal.blade.php` - Modal dialogs
- `toast-notification.blade.php` - Toast messages

### Livewire Views (`livewire/`)
- Match component names: `BudgetItemManagement.php` → `budget-item-management.blade.php`

## Database

### Key Tables
- `users`, `schools`, `school_user` (pivot)
- `accounting_accounts` - Hierarchical with parent_id
- `suppliers` - With department_id, municipality_id FKs
- `budget_items` - With school_id, accounting_account_id FKs
- `budgets` - With school_id, budget_item_id, type (income/expense), fiscal_year
- `budget_modifications` - With budget_id, modification_number, type (addition/reduction)
- `funding_sources` - With school_id, budget_item_id, type (internal/external)
- `departments`, `municipalities` - Colombian geography with DIAN codes
- `modules`, `permissions` - Permission organization
- `activity_logs` - Audit trail

### Seeders
- `DepartmentSeeder` - 33 Colombian departments
- `MunicipalitySeeder` - All Colombian municipalities
- `AccountingAccountSeeder` - PUC chart of accounts
- `BudgetItemSeeder` - Sample budget items
- `BudgetItemPermissionSeeder` - Module + permissions setup
- `BudgetPermissionSeeder` - Budget module permissions
- `BudgetSeeder` - Sample budgets with modifications
- `FundingSourcePermissionSeeder` - Funding sources permissions

## Routes (`routes/web.php`)

Key routes with middleware:
- `/budget-items` - BudgetItemManagement (requires school selected)
- `/budgets` - BudgetManagement (requires school selected)
- `/funding-sources` - FundingSourceManagement (requires school selected)
- `/suppliers` - SupplierManagement (requires school selected)
- `/accounting-accounts` - AccountingAccountManagement
- `/roles` - RoleManagement
- `/users` - UserManagement

## Conventions

- School-scoped data uses `school_id` FK and `forSchool()` scope
- Permissions follow pattern: `module.action` (e.g., `budget_items.view`)
- Permission seeders create Module + Permissions with `updateOrCreate`
- Spanish for UI text, English for code
- `is_active` boolean for soft-disable pattern
