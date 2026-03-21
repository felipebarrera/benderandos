# H19 + H20: Billing por Módulo + UI Completa

## Objetivo
Implementar el sistema de precios por módulo (H19) y conectar la UI del admin dashboard al backend real (H20). Sin H19 el producto no tiene modelo de negocio funcional.

## Estado Actual
- `plans` tabla central: 3 planes fijos (Trial/Básico/Pro) → se reemplaza con pricing por módulo
- `subscriptions` tabla central: simple (plan_id, estado, monto) → extender con `modulos_activos[]`
- `rubros_config` tabla tenant: ya tiene `modulos_activos` JSONB → sincronizar con subscription

## Proposed Changes

### Componente 1: Base de Datos Central

#### [NEW] Migration `create_plan_modulos_table`
- Tabla `plan_modulos`: `modulo_id` (M01-M32), nombre, descripción, `precio_mensual`, `es_base`, `requiere[]`, activo
- Seeder con 32 módulos y precios según spec H19

#### [NEW] Migration `add_modules_to_subscriptions_table`
- Añadir a `subscriptions`: `modulos_activos` (JSONB), `precio_calculado`, `trial_termina`, `dias_gracia`, `descuento_pct`
- Ampliar enum `estado` con `trial`, `gracia`, `suspendido`

---

### Componente 2: Middleware y Control de Acceso

#### [NEW] `CheckModuleAccess` middleware
- Mapa endpoint→módulo requerido (M05-M31)
- Si módulo no activo → 403 con `modulo_no_activo`
- Si suscripción vencida → 402 con `suscripcion_vencida` + link pago

#### [MODIFY] `Subscription.php`
- `puedeOperar()`: true si trial/activo/gracia
- `diasGraciaRestantes()`: cálculo desde proximo_cobro

---

### Componente 3: API Endpoints

#### [NEW] `ModuloPlanController` (Central/Super Admin)
- `GET /central/plan/modulos` — listar módulos con MRR
- `PUT /central/plan/modulos/{id}` — cambiar precio
- `GET /central/plan/modulos/{id}/impacto` — simular cambio

#### [NEW] `MiPlanController` (Tenant Admin)
- `GET /api/config/mi-plan` — módulos activos + precio total
- `GET /api/config/modulos-disponibles` — catálogo con precios
- `POST /api/config/modulos/{id}/activar` — activar módulo
- `POST /api/config/modulos/{id}/desactivar` — desactivar módulo

---

### Componente 4: UI Frontend

#### [MODIFY] `admin_dashboard_v2.html`
- Tab "Mi Plan" en Config: lista módulos activos/disponibles con precios
- Modal "Activar módulo" con preview de precio
- Banner global de suscripción vencida
- Sidebar dinámico construido desde `modulos_activos`

#### [MODIFY] `superadmin.html`
- Tab "Módulos & Precios": tabla editable de módulos con MRR
- Simulador de impacto de cambio de precio

---

## Verification Plan
- Verificar middleware: módulo no activo → 403
- Verificar middleware: suscripción vencida → 402
- Verificar activar módulo recalcula precio
- Verificar sidebar dinámico se adapta a módulos activos
