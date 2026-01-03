---
inclusion: always
---

# Product Overview

School management system for Colombian educational institutions. Handles multi-school administration with role-based access control.

## Core Features

- Multi-school support with school selection workflow
- User management with roles and permissions (Spatie Permission)
- School profile management (NIT, DANE codes, DIAN resolutions, budget info)
- Supplier management (proveedores) with person types and tax regimes
- Budget items (rubros presupuestales) linked to auxiliary accounting accounts
- Accounting accounts hierarchy (5 levels: Clase → Grupo → Cuenta → Subcuenta → Auxiliar)
- Activity logging for audit trails
- Authentication with Laravel Breeze
- Real-time UI updates with Livewire

## Domain Context

Colombian school administration context with specific fields:
- NIT (tax identification) with DV calculation
- DANE codes (national education registry)
- DIAN resolutions (tax authority invoicing)
- Rector and pagador (financial officer) information
- Budget agreements and contracting manuals
- Departments and municipalities with DIAN codes

## Modules

- **Dashboard**: Main overview
- **Users**: User management with role assignment
- **Roles & Permissions**: Role-based access control organized by modules
- **Schools**: Multi-school management (Admin only)
- **School Info**: Individual school profile
- **Accounting Accounts**: Chart of accounts (PUC) with 5-level hierarchy
- **Suppliers**: Vendor management (natural/juridical persons)
- **Budget Items**: Budget line items (rubros) linked to auxiliary accounts
- **Budgets**: Budget management with initial amounts, additions and reductions per budget item
- **Funding Sources**: Funding sources management (internal/external) linked to budget items
- **Incomes (Ingresos Reales)**: Real income tracking against funding sources with payment methods
- **Budget Transfers (Créditos y Contracréditos)**: Budget transfers between funding sources with audit trail
- **Activity Logs**: System audit trail
