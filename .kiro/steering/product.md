---
inclusion: always
---

# Product Overview

Sistema de gestión presupuestal para instituciones educativas colombianas (Fondos de Servicios Educativos - FSE). Maneja administración multi-colegio con control de acceso basado en roles, gestión presupuestal integral, y cumplimiento normativo colombiano.

## Propósito del Sistema

El sistema está diseñado para gestionar los Fondos de Servicios Educativos de colegios públicos colombianos, permitiendo:
- Administración presupuestal por vigencia fiscal
- Control de ingresos y gastos por rubros presupuestales
- Trazabilidad completa de operaciones (auditoría)
- Gestión de proveedores con información tributaria
- Cumplimiento de normativas DIAN y MEN

---

## Flujo Presupuestal del Sistema

### Estructura de Datos Presupuestales

```
Colegio (School)
  └── Rubro Presupuestal (BudgetItem) - vinculado a cuenta contable auxiliar
       └── Fuente de Financiación (FundingSource) - origen de recursos
            └── Presupuesto (Budget) - monto por vigencia fiscal
                 ├── Modificaciones (BudgetModification) - adiciones/reducciones
                 ├── Ingresos (Income) - recaudo real
                 └── Traslados (BudgetTransfer) - créditos/contracréditos
```

### Flujo de Ingresos (Fase 1 y 2 - Implementado)

```
1. PRESUPUESTO INICIAL
   └── Crear Rubro → Crear Fuente → Crear Presupuesto tipo "income"
   
2. RECAUDO DE INGRESOS
   └── Ver estado de recaudo → Registrar ingreso → Ajustes automáticos
   
3. AJUSTES AUTOMÁTICOS
   ├── Ingreso > Presupuestado → Adición automática
   └── Cerrar recaudo parcial → Reducción automática

4. TRASLADOS
   └── Mover recursos entre fuentes (crédito/contracrédito)
```

### Flujo de Gastos (Fase 3 - En progreso)

```
1. PRESUPUESTO DE GASTOS
   └── Crear Rubro → Crear Fuente → Crear Presupuesto tipo "expense"
   
2. DISTRIBUCIÓN DE GASTOS (Implementado)
   └── Distribuir presupuesto en códigos de gasto (ExpenseDistribution)

3. ETAPA PRECONTRACTUAL (Data layer implementado, UI pendiente)
   ├── Convocatoria (nace desde la distribución de gastos)
   │   ├── Número consecutivo por colegio/año
   │   ├── Objeto, justificación, presupuesto asignado
   │   └── Fechas de inicio y fin
   ├── CDP (Certificado de Disponibilidad Presupuestal)
   │   ├── Reserva dinero del saldo disponible de la fuente de financiación
   │   ├── Vinculado a rubro presupuestal + fuentes de financiación
   │   └── Si hay ingresos reales, reserva de ahí; si no, del presupuestado
   └── Evaluación de Propuestas
       ├── Proveedores presentan propuestas con subtotal, IVA, total
       ├── Puntuación y selección de propuesta ganadora
       └── Adjudicación de la convocatoria

4. ETAPA CONTRACTUAL (Pendiente)
   └── RP (Registro Presupuestal) → Contrato → Orden de Pago

5. ETAPA POSTCONTRACTUAL (Pendiente)
   └── Ejecución del gasto → Egreso
```

---

## Módulos Funcionales Detallados

### 1. Dashboard (`/dashboard`)
**Propósito**: Vista general del colegio seleccionado y acceso rápido a funcionalidades.

**Funcionalidades**:
- Muestra información del colegio activo (NIT, municipio, rector, vigencia)
- Información de contacto del colegio
- Información presupuestal básica
- Botón para cambiar colegio (solo Admin)

**Reglas de Negocio**:
- Requiere colegio seleccionado para usuarios no-Admin
- Admin puede cambiar entre colegios desde el dashboard

---

### 2. Gestión de Usuarios (`/users`)
**Propósito**: CRUD de usuarios del sistema con asignación de roles y colegios.

**Funcionalidades**:
- Listado de usuarios (filtrado por colegio si está seleccionado)
- Crear, editar, eliminar usuarios
- Asignar roles (Admin, Rector, Pagador, etc.)
- Vincular usuarios a colegios (relación many-to-many)

**Campos del Usuario**:
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| name | string | Sí | Nombre |
| surname | string | Sí | Apellido |
| identification_type | enum | Sí | CC, CE, TI, PA, etc. |
| identification_number | string | Sí | Número de documento |
| email | string | Sí | Correo (único) |
| password | string | Sí | Contraseña (min 8 caracteres) |

**Reglas de Negocio**:
- Usuario no puede eliminar su propia cuenta
- Al crear usuario en contexto de colegio, se vincula automáticamente al colegio
- Rol se sincroniza tanto en Spatie como en pivot `school_user`

**Permisos**: `users.view`, `users.create`, `users.edit`, `users.delete`

---

### 3. Roles y Permisos (`/roles`)
**Propósito**: Gestión de roles con asignación de permisos organizados por módulos.

**Funcionalidades**:
- Listado de roles existentes con sus permisos
- Crear, editar, eliminar roles
- Selección de permisos por checkbox agrupados por módulo
- Vista de módulos con todos sus permisos disponibles

**Estructura de Permisos**:
```
Módulo: nombre_modulo
  - nombre_modulo.view (Ver)
  - nombre_modulo.create (Crear)
  - nombre_modulo.edit (Editar)
  - nombre_modulo.delete (Eliminar)
```

**Reglas de Negocio**:
- Rol "Admin" no puede ser eliminado
- No se puede eliminar un rol con usuarios asignados
- Permisos usan guard `web`

**Permisos**: `roles.view`, `roles.create`, `roles.edit`, `roles.delete`

---

### 4. Gestión de Colegios (Admin Modal) (`SchoolSelect`)
**Propósito**: Selección y administración de colegios (solo Admin).

**Funcionalidades**:
- Modal con listado paginado de colegios
- Búsqueda por nombre
- Seleccionar colegio activo (guarda en sesión)
- Crear, editar colegios

**Información del Colegio**:
| Sección | Campos |
|---------|--------|
| **Básicos** | name, nit, dane_code, municipality |
| **Personal** | rector_name, rector_document, pagador_name |
| **Contacto** | address, email, phone, website |
| **Presupuesto** | budget_agreement_number, budget_approval_date, current_validity |
| **Contratación** | contracting_manual_approval_number, contracting_manual_approval_date |
| **DIAN** | dian_resolution_1/2, dian_range_1/2, dian_expiration_1/2 |

**Reglas de Negocio**:
- Solo rol Admin puede acceder
- Al seleccionar colegio, se guarda `selected_school_id` en sesión
- NIT debe ser único entre colegios

**Permisos**: `schools.view`, `schools.create`, `schools.edit`, `schools.delete`

---

### 5. Información del Colegio (`/school/info`)
**Propósito**: Ver/editar información del colegio asignado (usuarios no-Admin).

**Funcionalidades**:
- Vista de información completa del colegio
- Modo edición con validación de campos
- Auto-selección del primer colegio asignado al usuario

**Reglas de Negocio**:
- Admin es redirigido al dashboard (usa modal de colegios)
- Usuario sin colegio asignado es redirigido con error
- Campos opcionales se convierten a null al guardar vacíos

**Permisos**: `school_info.view`, `school_info.edit`

---

### 6. Cuentas Contables (`/accounting-accounts`)
**Propósito**: Gestión del Plan Único de Cuentas (PUC) con estructura jerárquica de 5 niveles.

**Estructura Jerárquica**:
```
Nivel 1: Clase (código 1 dígito) - Ej: "1 - ACTIVO"
  └─ Nivel 2: Grupo (código 2 dígitos) - Ej: "11 - DISPONIBLE"
      └─ Nivel 3: Cuenta (código 4 dígitos) - Ej: "1105 - CAJA"
          └─ Nivel 4: Subcuenta (código 6 dígitos) - Ej: "110505 - CAJA GENERAL"
              └─ Nivel 5: Auxiliar (código 8 dígitos) - Ej: "11050501 - CAJA GENERAL MONEDA NAL"
```

**Funcionalidades**:
- Vista de árbol expandible/colapsable
- Crear cuenta desde nivel padre (código sugerido automático)
- Editar propiedades de cuenta
- Eliminar con confirmación doble (si tiene hijos, requiere confirmación adicional)
- Filtros por nivel, naturaleza, estado

**Campos de Cuenta**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| code | string | Código único |
| name | string | Nombre (se guarda en mayúsculas) |
| description | text | Descripción opcional |
| level | int(1-5) | Nivel jerárquico |
| parent_id | FK | Cuenta padre (null para clases) |
| nature | enum(D,C) | Naturaleza: Débito o Crédito |
| allows_movement | boolean | Solo auxiliares pueden permitir movimiento |
| is_active | boolean | Estado activo/inactivo |

**Reglas de Negocio**:
- Cuentas contables son **globales** (no por colegio)
- Solo cuentas auxiliares (nivel 5) pueden vincular rubros presupuestales
- Código hijo se calcula automáticamente basado en el padre
- Código de clase nueva = último código + 1

**Permisos**: `accounting_accounts.view`, `accounting_accounts.create`, `accounting_accounts.edit`, `accounting_accounts.delete`

---

### 7. Proveedores (`/suppliers`)
**Propósito**: Gestión de proveedores/terceros con información tributaria colombiana.

**Funcionalidades**:
- CRUD completo de proveedores
- Cálculo automático de dígito de verificación (DV) para NIT
- Filtros por estado, tipo de persona, régimen tributario
- Búsqueda por documento, nombre, email

**Campos del Proveedor**:
| Sección | Campos |
|---------|--------|
| **Identificación** | document_type, document_number, dv |
| **Nombre** | first_name, second_name, first_surname, second_surname |
| **Tributario** | person_type, tax_regime |
| **Ubicación** | address, department_id, municipality_id |
| **Contacto** | phone, mobile, email |
| **Bancario** | bank_name, account_type, account_number |
| **Otros** | is_active, notes |

**Tipos de Documento**: CC, CE, NIT, TI, PA, RC, NUIP

**Tipos de Persona**:
- `natural`: Persona Natural
- `juridica`: Persona Jurídica (auto-selecciona NIT)

**Regímenes Tributarios**:
- `simplificado`: Régimen Simplificado
- `comun`: Régimen Común
- `gran_contribuyente`: Gran Contribuyente
- `no_responsable`: No Responsable de IVA

**Reglas de Negocio**:
- Proveedor pertenece a un colegio (`school_id`)
- Documento único por tipo dentro del colegio
- Si document_type = NIT, person_type = juridica automáticamente
- DV se calcula con algoritmo estándar DIAN

**Cálculo del DV**:
```php
// Primos usados: [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71]
// Se multiplica cada dígito (NIT con padding a 15 dígitos) por el primo correspondiente
// Se suma todo y se calcula módulo 11
// Si residuo > 1: DV = 11 - residuo, sino DV = residuo
```

**Permisos**: `suppliers.view`, `suppliers.create`, `suppliers.edit`, `suppliers.delete`

---

### 8. Rubros Presupuestales (`/budget-items`)
**Propósito**: Definir los rubros (líneas) presupuestales vinculados a cuentas contables auxiliares. Son **globales** (compartidos entre todos los colegios).

**Funcionalidades**:
- CRUD de rubros presupuestales globales
- Selección de cuenta contable auxiliar (nivel 5)
- Filtros por estado y cuenta contable
- Búsqueda por código y nombre

**Campos del Rubro**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| accounting_account_id | FK | Cuenta auxiliar vinculada |
| code | string | Código único global |
| name | string | Nombre del rubro |
| description | text | Descripción opcional |
| is_active | boolean | Estado |

**Reglas de Negocio**:
- Rubros son globales, compartidos por todos los colegios
- Código único globalmente
- Solo puede vincularse a cuentas auxiliares (nivel 5) con `allows_movement = true`
- Código se guarda en mayúsculas

**Permisos**: `budget_items.view`, `budget_items.create`, `budget_items.edit`, `budget_items.delete`

---

### 9. Presupuestos (`/budgets`)
**Propósito**: Gestión del presupuesto inicial y modificaciones por rubro, fuente y vigencia fiscal.

**Funcionalidades**:
- Crear presupuesto con flujo: Rubro → Fuente → Monto
- Ver historial de modificaciones (adiciones/reducciones)
- Registrar modificaciones presupuestales manuales
- Filtros por tipo, año, estado
- Toggle de activación/desactivación
- Resumen de totales por tipo (ingresos vs gastos)

**Campos del Presupuesto**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| budget_item_id | FK | Rubro presupuestal |
| funding_source_id | FK | Fuente de financiación |
| type | enum | `income` (Ingreso) o `expense` (Gasto) |
| initial_amount | decimal(15,2) | Monto inicial |
| current_amount | decimal(15,2) | Monto actual (calculado) |
| fiscal_year | year | Vigencia fiscal |
| description | text | Descripción opcional |
| is_active | boolean | Estado |

**Flujo de Creación de Presupuesto**:
1. Seleccionar Tipo (Ingreso/Gasto)
2. Seleccionar Rubro presupuestal
3. Seleccionar Fuente de Financiación (filtrada por rubro seleccionado)
4. Ingresar monto inicial y descripción
5. Sistema valida unicidad por rubro+fuente+año+tipo

**Modificaciones Presupuestales**:
| Campo | Descripción |
|-------|-------------|
| modification_number | Número secuencial por presupuesto |
| type | `addition` (Adición) o `reduction` (Reducción) |
| amount | Monto de la modificación |
| previous_amount | Monto antes de modificar |
| new_amount | Monto después de modificar |
| reason | Justificación (min 10 caracteres) |
| document_number | Número de documento soporte |
| created_by | Usuario que registró |

**Fórmula de Monto Actual**:
```
current_amount = initial_amount 
               + SUM(additions) 
               - SUM(reductions)
               + SUM(créditos_entrantes)
               - SUM(contracréditos_salientes)
```

**Reglas de Negocio**:
- Un rubro+fuente solo puede tener UN presupuesto por tipo y año fiscal por colegio
- Reducción no puede ser mayor al monto actual
- Modificaciones son inmutables (no se pueden editar/eliminar)
- Al editar monto inicial, se recalcula monto actual automáticamente
- Modificaciones automáticas se crean desde módulo de Ingresos (ajustes por recaudo)

**Permisos**: `budgets.view`, `budgets.create`, `budgets.edit`, `budgets.delete`, `budgets.modify`

---

### 10. Fuentes de Financiación (`/funding-sources`)
**Propósito**: Definir fuentes de donde provienen los recursos del presupuesto. Son **globales** (compartidas entre todos los colegios).

**Funcionalidades**:
- CRUD de fuentes de financiación
- Vinculación a rubros presupuestales
- Ver saldo disponible (ingresos - transferencias)
- Filtros por tipo y estado

**Campos**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| budget_item_id | FK | Rubro presupuestal |
| code | string | Código único por rubro |
| name | string | Nombre de la fuente |
| type | enum | `internal` (Interna) o `external` (Externa) |
| description | text | Descripción |
| is_active | boolean | Estado |

**Cálculo de Saldo Disponible**:
```
available_balance = SUM(incomes) 
                  - SUM(transferencias_salientes)
                  + SUM(transferencias_entrantes)
```

**Reglas de Negocio**:
- Fuentes son globales, compartidas por todos los colegios
- Fuente pertenece a un rubro presupuestal
- Código único por rubro (budget_item_id + code)
- Saldo puede calcularse por año fiscal específico

**Permisos**: `funding_sources.view`, `funding_sources.create`, `funding_sources.edit`, `funding_sources.delete`

---

### 11. Ingresos Reales (`/incomes`)
**Propósito**: Registrar los ingresos efectivamente recaudados contra fuentes de financiación con seguimiento de estado de recaudo y ajustes automáticos al presupuesto.

**Funcionalidades**:
- CRUD de ingresos con flujo Rubro → Fuente
- **Estado de Recaudo por Fuente**: Tabla que muestra el estado de recaudo de cada presupuesto
- **Tarjetas de resumen**: Contadores de pendientes, parciales y completados
- **Filtros por estado**: Tabs para filtrar (Todos, Pendientes, Parciales, Completados)
- **Registro desde presupuesto**: Botón "Ingreso" pre-llena el modal con datos del presupuesto
- **Cerrar Recaudo**: Botón para marcar como completo un recaudo parcial con reducción automática
- **Ajustes automáticos**: Adiciones/reducciones según diferencia entre ingreso y presupuesto
- Métodos de pago y referencia de transacción

**Campos del Ingreso**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| budget_id | FK | Presupuesto de ingreso |
| funding_source_id | FK | Fuente de financiación |
| name | string | Concepto del ingreso |
| description | text | Descripción |
| amount | decimal(15,2) | Monto recaudado |
| date | date | Fecha del ingreso |
| payment_method | enum | transferencia, efectivo, cheque, consignacion, otro |
| transaction_reference | string | Referencia de transacción |
| created_by | FK | Usuario que registró |

**Estados de Recaudo**:
| Estado | Condición | Color | Acciones |
|--------|-----------|-------|----------|
| Pendiente | recaudado = 0 | Amarillo | Registrar Ingreso |
| Parcial | 0 < recaudado < presupuestado | Naranja | Registrar Ingreso, Cerrar Recaudo |
| Completado | recaudado = presupuestado | Verde | Solo visualización |
| Excedido | recaudado > presupuestado | Azul | Solo visualización |

**Flujo de Registro de Ingreso**:
1. Usuario ve tabla "Estado de Recaudo por Fuente" con todos los presupuestos de ingreso
2. Hace clic en "Ingreso" de un presupuesto específico
3. Modal se abre pre-llenado con:
   - Rubro y fuente del presupuesto
   - Información: Presupuestado, Recaudado, Pendiente
4. Usuario ingresa monto, concepto, fecha, método de pago
5. Sistema detecta si hay diferencia:
   - **Ingreso > Presupuestado**: Alerta de ADICIÓN automática
   - **Ingreso < Presupuestado**: Alerta informativa (queda parcial)
6. Al guardar, si hay excedente, se crea BudgetModification tipo "addition"

**Flujo de Cerrar Recaudo**:
1. Para presupuestos con estado "Parcial", aparece botón "Cerrar"
2. Usuario confirma en modal que muestra:
   - Presupuestado vs Recaudado
   - Monto de reducción que se aplicará
3. Al confirmar, sistema crea BudgetModification tipo "reduction" por la diferencia
4. Estado cambia automáticamente a "Completado"

**Ajustes Automáticos al Presupuesto**:
```
// Al registrar ingreso que excede el presupuesto:
IF (recaudado_total + nuevo_ingreso) > presupuestado THEN
    excedente = (recaudado_total + nuevo_ingreso) - presupuestado
    CREATE BudgetModification(
        type: 'addition',
        amount: excedente,
        reason: 'Ajuste automático por ingreso superior al presupuestado'
    )
    
// Al cerrar recaudo parcial:
IF recaudado < presupuestado THEN
    faltante = presupuestado - recaudado
    CREATE BudgetModification(
        type: 'reduction',
        amount: faltante,
        reason: 'Ajuste por cierre de recaudo - Ingreso inferior al presupuestado'
    )
```

**Resumen de Ejecución** (Tarjetas):
```
pendientes = COUNT(budgets WHERE recaudado = 0)
parciales = COUNT(budgets WHERE 0 < recaudado < current_amount)
completados = COUNT(budgets WHERE recaudado >= current_amount)
```

**Reglas de Negocio**:
- Monto debe ser mayor a 0
- Fecha es requerida
- Se registra automáticamente el usuario creador
- Al seleccionar rubro, se filtran solo fuentes activas de ese rubro
- Solo se pueden registrar ingresos en presupuestos tipo "income" de la vigencia actual
- Modificaciones presupuestales son inmutables y quedan registradas para auditoría
- El botón "Cerrar" solo aparece para presupuestos con estado "Parcial"

**Permisos**: `incomes.view`, `incomes.create`, `incomes.edit`, `incomes.delete`

---

### 12. Traslados Presupuestales (`/budget-transfers`)
**Propósito**: Registrar movimientos de recursos entre fuentes de financiación (Créditos y Contracréditos).

**Conceptos**:
- **Crédito**: Dinero que ENTRA a una fuente de financiación
- **Contracrédito**: Dinero que SALE de una fuente de financiación

**Funcionalidades**:
- Crear traslados entre fuentes de financiación
- Ver historial de traslados con detalle
- Filtros por año fiscal
- Numeración automática por año y colegio

**Campos del Traslado**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| transfer_number | int | Número secuencial por año/colegio |
| source_budget_id | FK | Presupuesto origen (rubro) |
| source_funding_source_id | FK | Fuente origen (contracrédito) |
| destination_budget_id | FK | Presupuesto destino (rubro) |
| destination_funding_source_id | FK | Fuente destino (crédito) |
| amount | decimal | Monto del traslado |
| source_previous_amount | decimal | Saldo anterior origen |
| source_new_amount | decimal | Saldo nuevo origen |
| destination_previous_amount | decimal | Saldo anterior destino |
| destination_new_amount | decimal | Saldo nuevo destino |
| reason | text | Justificación (min 10 caracteres) |
| document_number | string | Número de documento soporte |
| document_date | date | Fecha del documento |
| fiscal_year | int | Vigencia fiscal |
| created_by | FK | Usuario que registró |

**Flujo de Creación**:
1. Seleccionar rubro origen → cargar sus fuentes con saldo > 0
2. Seleccionar fuente origen → mostrar saldo disponible
3. Seleccionar rubro destino → cargar sus fuentes activas
4. Seleccionar fuente destino
5. Ingresar monto (no puede exceder saldo disponible origen)
6. Justificar el traslado

**Reglas de Negocio**:
- Fuente origen y destino deben ser diferentes
- Monto no puede exceder saldo disponible de la fuente origen
- Número de traslado es único por colegio y año fiscal
- Traslados son inmutables (no se editan ni eliminan)
- Al crear, se registran los saldos anterior/nuevo para auditoría

**Permisos**: `budget_transfers.view`, `budget_transfers.create`

---

### 13. Gastos (`/expenses`)
**Propósito**: Distribución de presupuesto de gastos en códigos de gasto (categorías específicas de gasto).

**Funcionalidades**:
- Listado de presupuestos tipo "expense" con distribuciones inline
- Distribuir presupuesto en códigos de gasto (muchos-a-uno)
- Vista de detalle con resumen de distribuciones
- Filtros por año, rubro presupuestal, búsqueda
- Resumen: Presupuestado, Distribuido, Sin Distribuir

**Distribución de Gastos**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| budget_id | FK | Presupuesto de gasto |
| expense_code_id | FK | Código de gasto |
| amount | decimal(15,2) | Monto distribuido |
| description | text | Descripción opcional |
| created_by | FK | Usuario creador |

**Reglas de Negocio**:
- Un código de gasto solo puede aparecer una vez por presupuesto
- La suma de distribuciones no puede exceder el monto del presupuesto
- No se puede eliminar una distribución que tenga convocatorias asociadas
- La ejecución directa fue removida (ahora pasa por etapa precontractual)

**Permisos**: `expenses.view`, `expenses.distribute`, `expenses.delete`

---

### 14. Etapa Precontractual (`/precontractual` - UI pendiente)
**Propósito**: Gestión de convocatorias, CDPs y evaluación de propuestas antes de la contratación.

**Subentidades**:

#### Convocatoria
```
Nace desde una distribución de gastos.
Define el objeto a contratar, justificación, presupuesto y plazos.
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| expense_distribution_id | FK (nullable) | Distribución origen |
| convocatoria_number | int | Consecutivo por colegio/año |
| fiscal_year | int | Vigencia fiscal |
| start_date | date | Fecha de apertura |
| end_date | date | Fecha de cierre |
| object | text | Objeto a contratar |
| justification | text | Necesidad a satisfacer |
| assigned_budget | decimal(15,2) | Presupuesto asignado |
| requires_multiple_cdps | boolean | Si requiere múltiples CDPs |
| status | enum | draft, open, evaluation, awarded, cancelled |
| evaluation_date | date | Fecha de evaluación |
| proposals_count | int | Cantidad de propuestas recibidas |
| created_by | FK | Usuario creador |

**Estados de Convocatoria**:
| Estado | Descripción | Color |
|--------|-------------|-------|
| draft | Borrador | Gris |
| open | Abierta | Azul |
| evaluation | En evaluación | Amarillo |
| awarded | Adjudicada | Verde |
| cancelled | Cancelada | Rojo |

#### CDP (Certificado de Disponibilidad Presupuestal)
```
Reserva dinero de la fuente de financiación para garantizar disponibilidad.
Un CDP se vincula a UN rubro presupuestal pero puede usar MÚLTIPLES fuentes.
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| school_id | FK | Colegio |
| convocatoria_id | FK | Convocatoria asociada |
| cdp_number | int | Consecutivo por colegio/año |
| fiscal_year | int | Vigencia fiscal |
| budget_item_id | FK | Rubro presupuestal |
| total_amount | decimal(15,2) | Monto total del CDP |
| status | enum | active, used, cancelled |
| created_by | FK | Usuario creador |

**Detalle por Fuente de Financiación (CdpFundingSource)**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| cdp_id | FK | CDP padre |
| funding_source_id | FK | Fuente de financiación |
| budget_id | FK | Budget record (item+fuente+tipo) |
| amount | decimal(15,2) | Monto a reservar de esta fuente |
| available_balance_at_creation | decimal(15,2) | Snapshot del saldo disponible al crear |

**Lógica de Reserva del CDP**:
```
Disponible en fuente = Ingresos Reales - CDPs activos existentes
Si no hay ingresos reales → Reserva del monto presupuestado
CDP reserva el monto, reduciendo el saldo disponible de la fuente
```

#### Propuesta
```
Proveedores presentan propuestas para una convocatoria.
Se evalúan con puntuación y se selecciona la ganadora.
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| convocatoria_id | FK | Convocatoria |
| supplier_id | FK | Proveedor |
| proposal_number | int | Número de propuesta |
| subtotal | decimal(15,2) | Subtotal |
| iva | decimal(15,2) | IVA |
| total | decimal(15,2) | Total |
| score | decimal(5,2) | Puntuación de evaluación |
| is_selected | boolean | Si es la propuesta ganadora |

**Reglas de Negocio Precontractual**:
- Convocatoria_number es único por colegio + año fiscal
- CDP_number es único por colegio + año fiscal
- Un proveedor solo puede presentar UNA propuesta por convocatoria
- Solo una propuesta puede tener is_selected = true por convocatoria
- CDP no puede reservar más de lo disponible en la fuente

**Permisos**: `precontractual.view`, `precontractual.create`, `precontractual.edit`, `precontractual.delete`, `precontractual.evaluate`

---

### 15. Registro de Actividad (`/activity-logs`)
**Propósito**: Auditoría completa de todas las operaciones del sistema.

**Funcionalidades**:
- Listado paginado de actividades
- Filtros por colegio, usuario, módulo, acción, fechas
- Ver detalle con valores anteriores y nuevos
- Búsqueda por descripción

**Campos del Log**:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| user_id | FK | Usuario que realizó la acción |
| school_id | FK | Colegio donde se realizó |
| action | string | created, updated, deleted |
| model_type | string | Clase del modelo afectado |
| model_id | int | ID del registro afectado |
| module | string | Módulo del sistema |
| description | string | Descripción legible |
| old_values | json | Valores anteriores |
| new_values | json | Valores nuevos |
| ip_address | string | IP del usuario |
| user_agent | string | Navegador/dispositivo |

**Trait LogsActivity**:
Cualquier modelo que use el trait `LogsActivity` registra automáticamente:
- Creación: guarda todos los atributos nuevos
- Actualización: guarda valores cambiados (anterior y nuevo)
- Eliminación: guarda todos los atributos eliminados

**Campos Ignorados**: `created_at`, `updated_at`, `remember_token`, `password`

**Permisos**: `activity_logs.view`

---

## Flujo Multi-Colegio

### Selección de Colegio
1. **Admin**: Modal de selección desde sidebar o dashboard
2. **Usuario normal**: Auto-selección del primer colegio asignado

### Middleware `EnsureSchoolSelected`
- Admin puede acceder sin colegio seleccionado
- Usuarios normales requieren colegio en sesión
- Si no hay colegio en sesión, se auto-selecciona el primero
- Si usuario no tiene colegios asignados, se redirige a login con error

### Scope `forSchool($schoolId)`
Todos los modelos que pertenecen a un colegio tienen este scope:
```php
public function scopeForSchool($query, int $schoolId)
{
    return $query->where('school_id', $schoolId);
}
```

---

## Contexto Normativo Colombiano

### Identificadores Fiscales
- **NIT**: Número de Identificación Tributaria con dígito de verificación
- **Código DANE**: Identificador del Ministerio de Educación Nacional
- **Resoluciones DIAN**: Autorización de facturación electrónica

### Información del Colegio
- **Rector**: Representante legal de la institución
- **Pagador**: Funcionario responsable de pagos (puede ser diferente al rector)
- **Vigencia**: Año fiscal del presupuesto
- **Acuerdo Presupuestal**: Documento de aprobación del presupuesto
- **Manual de Contratación**: Normativa interna de contratación

### Geografía
- 33 Departamentos colombianos con códigos DIAN
- Municipios asociados a cada departamento con códigos DIAN

---

## Seguridad y Permisos

### Sistema de Permisos (Spatie)
- Permisos organizados por módulos
- Formato: `modulo.accion` (ej: `budgets.create`)
- Guard único: `web`

### Módulos y Permisos Disponibles
| Módulo | Permisos |
|--------|----------|
| dashboard | view |
| schools | view, create, edit, delete |
| school_info | view, edit |
| users | view, create, edit, delete |
| roles | view, create, edit, delete |
| accounting_accounts | view, create, edit, delete |
| activity_logs | view |
| suppliers | view, create, edit, delete |
| budget_items | view, create, edit, delete |
| budgets | view, create, edit, delete, modify |
| funding_sources | view, create, edit, delete |
| incomes | view, create, edit, delete |
| budget_transfers | view, create |
| expenses | view, distribute, delete |
| precontractual | view, create, edit, delete, evaluate |

### Roles Predefinidos
- **Admin**: Todos los permisos + gestión multi-colegio
- **Rector**: Permisos de visualización y edición de su colegio
- **Pagador**: Permisos operativos (proveedores, ingresos, etc.)
