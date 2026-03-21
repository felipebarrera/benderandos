# BENDERAND ERP — PROJECT KNOWLEDGE BASE
## Estado Real del Codebase · Arquitectura · Rutas · Auth · Spider · Gaps
*Generado: Marzo 2026 · Fuente: application_report.md + analysis_report.md + plan_v2 + config_industrias*
*Para: antigraviity — usar como contexto antes de cualquier modificación*

---

## 1. UBICACIÓN DEL PROYECTO

```
/home/master/trabajo/proyectos/src/benderandos/
├── app/
│   ├── Http/Controllers/
│   │   ├── SpiderController.php              ← Spider QA central
│   │   ├── Central/                          ← Controllers del panel central
│   │   └── Tenant/ (o sin subdirectorio)     ← Controllers de tenant
│   ├── Services/
│   ├── Jobs/
│   └── Models/
├── routes/
│   ├── web.php       ← Panel central web (login, dashboard, spider, billing)
│   ├── api.php       ← API central (Sanctum, spider QA, billing/módulos)
│   ├── central.php   ← API SuperAdmin (dashboard, métricas, tenants CRUD)
│   ├── tenant.php    ← TODAS las rutas de tenant (425 líneas)
│   └── webhook.php   ← Webhooks externos (WhatsApp onboarding)
├── resources/views/
│   └── central/
│       └── spider.blade.php
├── tests/
│   ├── sync_spider_tests.sh
│   └── spider_tests.json   ← 181 tests configurados
└── database/
    ├── seeders/
    │   ├── DemoTenantsSeeder.php
    │   └── TenantDemoDataSeeder.php
    └── migrations/
        ├── (central — schema public)
        └── tenant/ (se ejecutan por schema)
```

---

## 2. STACK TECNOLÓGICO

| Capa | Tecnología |
|---|---|
| Backend ERP | PHP 8.2 + Laravel 11 |
| Multi-tenancy | stancl/tenancy v3 — **schema por tenant en PostgreSQL** |
| Base de datos | PostgreSQL 16 (schema `public` = central, schema `tenant_{id}` = cada empresa) |
| Cache / Colas | Redis 7 + Laravel Horizon |
| Auth central | Guard `super_admin` → modelo `SuperAdmin` (conexión `central`) |
| Auth tenant API | Guard `sanctum` → modelo `User` / `SuperAdmin` |
| Auth bot | Middleware `auth.bot` → `InternalBotAuth` |
| Auth ERP↔Bot | Middleware `jwt.bridge` → `JwtBridgeMiddleware` (JWT_SHARED_SECRET compartido) |
| WhatsApp Bot | Node.js + Express + BullMQ + Socket.io (sistema separado "Moteland") |
| SII | libredte-lib-core integrado en `SiiService` + `EmitirDteJob` |
| Total rutas | **238 rutas registradas** |

---

## 3. SISTEMA DE AUTENTICACIÓN — DETALLE CRÍTICO

### 3.1 Guards definidos

```
super_admin  → modelo SuperAdmin, conexión 'central' (schema public)
              Usado en: panel web central, Spider QA
              Rutas: /central/*, /api/spider/*

sanctum      → modelo User (tenant) o SuperAdmin
              Usado en: API de tenant, API superadmin
              Rutas: /api/superadmin/*, /api/* (tenant)

auth.bot     → InternalBotAuth middleware
              Usado en: /api/bot/*

jwt.bridge   → JwtBridgeMiddleware (JWT_SHARED_SECRET)
              Usado en: /api/internal/* (puente ERP↔Node.js)
```

### 3.2 Cómo funciona Sanctum en multi-tenant

**CRÍTICO para el spider:** Los tokens Sanctum se almacenan en la tabla `personal_access_tokens`. En stancl/tenancy v3 con schema por tenant, esta tabla existe **tanto en el schema public (central) como en cada schema de tenant**.

- Un token generado al logear en `localhost:8000/api/superadmin/login` → se guarda en schema `public`
- Un token generado al logear en `demo.localhost:8000/api/login` → se guarda en el schema `tenant_demo`
- **Los tokens NO son portables entre central y tenant** — esto es la causa del BUG-SP-083

### 3.3 Flujo de login por contexto

```
Central (SuperAdmin):
  POST /api/superadmin/login  → guard sanctum, modelo SuperAdmin, schema public
  POST /central/spider/token  → guard super_admin, genera token para el spider

Tenant (Usuario):
  POST /api/login             → guard sanctum, modelo User, schema del tenant
  → retorna Bearer token del schema del tenant
```

---

## 4. ARCHIVOS DE RUTAS — MAPA COMPLETO

### 4.1 `routes/web.php` — Panel Central Web (guard: `super_admin`)

```
GET  /central/login          → CentralAuthController@showLogin
POST /central/login          → CentralAuthController@login
POST /central/logout         → CentralAuthController@logout
GET  /central                → MetricsController@dashboard
GET  /central/tenants        → TenantManageController@index
GET  /central/billing        → BillingController@index
GET  /central/spider         → SpiderController@dashboard   ← UI del spider
POST /central/spider/token   → SpiderController@generateToken ← genera token SA
```

### 4.2 `routes/api.php` — API Central (Sanctum + Spider)

```
POST /api/superadmin/login   → CentralAuthController@apiLogin (sin auth)

# Grupo auth:sanctum (superadmin):
GET  /api/superadmin/dashboard
GET  /api/superadmin/tenants
GET  /api/superadmin/billing/*
GET  /api/central/plan/modulos    → PlanModulosController@index ✅ verificado spider
PATCH /api/central/plan/modulos/{id}
PATCH /api/central/plan/modulos/{id}/impacto

# Spider QA (guard: auth:super_admin — NO auth:sanctum):
GET  /api/spider/probe       → SpiderController@probe
GET  /api/spider/db-check    → SpiderController@dbCheck
POST /api/spider/sync        → SpiderController@sync
GET/POST /api/spider/tests   → SpiderController@tests
```

> **⚠️ INCONSISTENCIA DETECTADA:** El `application_report.md` dice las rutas spider usan `auth:super_admin`. El spider QA v3 las accede con un token Bearer (Sanctum). Esto explica el BUG-SP-048 a SP-054: si usan `auth:super_admin` (guard web, no API), los requests con Bearer token Sanctum van a retornar 200 sin validar el token porque el guard web usa sesión, no token. **Verificar en el código real qué guard está aplicado.**

### 4.3 `routes/central.php` — API SuperAdmin

```
# Grupo auth:sanctum (modelo SuperAdmin):
POST /api/superadmin/login
GET  /api/superadmin/dashboard
GET  /api/superadmin/tenants
GET  /api/superadmin/billing/suscripciones
GET  /api/superadmin/billing/pagos
POST /api/superadmin/tenants/{id}/suspender
POST /api/superadmin/tenants/{id}/reactivar
POST /api/superadmin/tenants/{id}/impersonar
```

### 4.4 `routes/tenant.php` — Todas las rutas de tenant (425 líneas)

Grupos por middleware:

```
# Grupo 1: tenancy.initialize + auth:sanctum + role
/api/ventas                  → VentaController (apiResource)
/api/productos               → ProductoController (apiResource)
/api/clientes                → ClienteController (apiResource)
/api/usuarios                → UsuarioController
/api/roles                   → RolesController
/api/dashboard               → DashboardController
/api/config/*                → ConfigController
/api/compras                 → CompraController
/api/compras/dashboard
/api/compras/alertas-stock
/api/ordenes-compra          → OrdenCompraController
/api/proveedores             → ProveedorController
/api/marketing/*             → MarketingController
/api/sii/*                   → SiiController
/api/saas/*                  → SaasController
/api/rrhh/*                  → RrhhController
/api/reclutamiento/*         → ReclutamientoController
/api/recetas                 → RecetaController
/api/recetas/*
/api/producciones            → ProduccionController
/api/rentas/*                → RentaController
/api/delivery/*              → DeliveryController
/api/empleo/*                → EmpleoController (ofertas públicas)

# Grupo 2: tenancy.initialize + auth.bot (bot WhatsApp)
/api/bot/config              → BotApiController@config
/api/bot/pedido              → BotApiController
/api/bot/precio/{sku}
/api/bot/stock/{sku}
/api/bot/cliente/{telefono}
/api/bot/agenda/disponibilidad

# Grupo 3: tenancy.initialize + jwt.bridge (puente ERP↔Node.js)
/api/internal/productos/stock    → ErpBridgeController
/api/internal/clientes/buscar
/api/internal/ventas/remota

# Grupo 4: tenancy.initialize + sin auth (público)
/api/v1/public/productos     → PublicApiController@productos   ← BUG-SP-084 (500)
/api/v1/public/ventas        → PublicApiController@ventas
/api/v1/public/clientes      → PublicApiController@clientes
/api/v1/public/stock/{sku}   → PublicApiController@stock

# Grupo 5: sin auth (portal cliente)
/portal/*                    → ClientePortalController
/api/empleo/ofertas/*        → EmpleoController (rutas públicas)

# Rutas web (Blade views — tenancy.initialize + auth web)
/auth/login/web              → AuthController@showLoginWeb   ← LOGIN REAL
/auth/login                  → AuthController@login (API redirect)
/auth/logout                 → AuthController@logout (POST)
/pos                         → PosController@index (Blade view)
/pos/historial               → PosController@historial
/pos/saas/pipeline           → SaasPosController@pipeline (M31)
/pos/saas/tenants            → SaasPosController@tenants (M31)
/rentas                      → RentaController@panel (M05/M06) — puede no existir
/operario                    → OperarioController@panel — puede no existir
/admin/*                     → WebPanelController (Blade views admin)
```

### 4.5 `routes/webhook.php` — Webhooks externos

```
POST /webhook/whatsapp/onboarding       → WhatsAppWebhookController
POST /webhook/whatsapp/pedido-remoto    → WhatsAppWebhookController
POST /webhook/whatsapp/check-slug       → WhatsAppWebhookController
POST /webhook/wa/config                 → WhatsAppWebhookController
POST /webhook/wa/saas-onboarding        → SaasOnboardingController
```

> **Nota:** Todos los webhooks son `POST`. El spider los testea como `GET` → siempre van a retornar 405 Method Not Allowed o error. Esto explica los BUG-SP-044 a SP-047.

---

## 5. MIDDLEWARE PERSONALIZADO

| Alias | Clase | Qué hace | Dónde se usa |
|---|---|---|---|
| `auth.bot` | `InternalBotAuth` | Valida token bot de WhatsApp | `/api/bot/*` |
| `jwt.bridge` | `JwtBridgeMiddleware` | Valida JWT_SHARED_SECRET compartido con Node.js | `/api/internal/*` |
| `module` | `CheckModuleAccess` | Verifica que el módulo esté activo en el plan del tenant | Rutas de módulos específicos |
| `role` | `CheckRole` | Verifica rol del usuario (`admin`, `cajero`, `operario`, `bodega`) | Mayoría de rutas de tenant |
| — | `LogUnauthorizedRequests` | Loguea errores 4xx/5xx para QA | Global |
| — | `CheckTenantStatus` | Verifica estado del tenant (activo/suspendido/trial) → 402 si falla | Rutas que devuelven 402 |

---

## 6. MODELOS — INVENTARIO COMPLETO

### 6.1 Modelos Central (schema `public`)

| Modelo | Tabla | Notas |
|---|---|---|
| `SuperAdmin` | `super_admins` | Modelo de auth central. Conexión: `central` |
| `Tenant` | `tenants` | stancl/tenancy. Campos: `id`, `estado`, `plan_id`, `trial_ends_at`, `rubro` |
| `Domain` | `domains` | Subdominios de cada tenant |
| `Plan` | `plans` | Planes Básico/Pro/Enterprise |
| `Subscription` | `subscriptions` | Suscripción activa de cada tenant |
| `PagoSubscription` | `pago_subscriptions` | Historial de pagos |
| `AuditLog` | `audit_logs` | Log de acciones de impersonación |

### 6.2 Modelos Tenant (schema `tenant_{id}`)

| Modelo | Tabla | Campos clave post-audit |
|---|---|---|
| `Producto` | `productos` | `valor_venta` (era precio), `cantidad` (era stock), `cantidad_minima` (era stock_minimo), `tipo_producto` (era tipo), `estado` (enum: activo/inactivo) |
| `Cliente` | `clientes` | `nombre`, `rut`, `email`, `telefono`, `giro` *(nuevo)*, `direccion` *(nuevo)* |
| `Usuario` | `usuarios` | `name`, `email`, `password` (era clave), `rol` |
| `Empleado` | `empleados` | `nombre`, `rut`, `cargo`, `sueldo_base`, `fecha_ingreso` |
| `Venta` | `ventas` | — |
| `ItemVenta` | `items_venta` | — |
| `Compra` | `compras` | — |
| `Proveedor` | `proveedores` | — |
| `OrdenCompra` | `ordenes_compra` | — |
| `Renta` | `rentas` | `tiempo_fin` (persiste en DB) |
| `Receta` | `recetas` | — |
| `Empleado` | `empleados` | — |
| `Asistencia` | `asistencias` | — |
| `Liquidacion` | `liquidaciones` | — |
| `Entrega` | `entregas` | — |
| `Repartidor` | `repartidores` | — |
| `ZonaEnvio` | `zonas_envio` | — |
| `Deuda` | `deudas` | — |
| `Encargo` | `encargos` | — |
| `ConfigModulo` | `config_modulos` | `modulo_id`, `activo` (boolean) |
| `ConfigSii` | `config_sii` | — |
| `DteEmitido` | `dte_emitidos` | — |

---

## 7. TENANTS DE PRUEBA

| # | Slug / ID | Dominio | Admin | Password | Módulos principales |
|---|---|---|---|---|---|
| T0 | `df21b4b0-*` | `demo.localhost` | — | — | Original genérico |
| T1 | `demo-legal` | `demo-legal.localhost` | admin@demo-legal.cl | demo1234 | M07 M08 M09 M10 M20 M21 M32 |
| T2 | `demo-padel` | `demo-padel.localhost` | admin@demo-padel.cl | demo1234 | M03 M06 M08 M14 M17 M24 M30 M32 |
| T3 | `demo-motel` | `demo-motel.localhost` | admin@demo-motel.cl | demo1234 | M03 M06 M14 |
| T4 | `demo-abarrotes` | `demo-abarrotes.localhost` | admin@demo-abarrotes.cl | demo1234 | M01 M02 M03 M04 M11 M12 M17 M18 M20 M24 M32 |
| T5 | `demo-ferreteria` | `demo-ferreteria.localhost` | admin@demo-ferreteria.cl | demo1234 | M01 M02 M03 M04 M11 M17 M18 M19 M20 M24 M26 M32 ← **más completo** |
| T6 | `demo-medico` | `demo-medico.localhost` | admin@demo-medico.cl | demo1234 | M07 M08 M09 M10 M20 M21 M32 |
| T7 | `demo-saas` | `demo-saas.localhost` | admin@demo-saas.cl | demo1234 | M20 M21 M22 M23 M24 M25 M27 M31 M32 |

> `demo-ferreteria` es el tenant recomendado para Spider QA completo — tiene la mayor cobertura de módulos.

---

## 8. SPIDER QA v3 — ARQUITECTURA REAL

### 8.1 Componentes

```
SpiderController.php         ← Controller Laravel (central)
  métodos: dashboard(), probe(), dbCheck(), sync(), tests(), generateToken()

spider.blade.php             ← UI del spider (Blade, corre en /central/spider)
  Ejecuta los tests en el navegador vía fetch JS
  Muestra resultados en tiempo real

spider_tests.json            ← 181 tests configurados (JSON)
  Estructura por test: { id, nombre, url, método, esperado, capa, prioridad }

sync_spider_tests.sh         ← Script bash que sincroniza tests desde php artisan route:list
  Genera/actualiza spider_tests.json automáticamente
```

### 8.2 Flujo del spider

```
1. Usuario abre /central/spider (autenticado como super_admin)
2. spider.blade.php carga spider_tests.json vía /api/spider/tests
3. Para cada test:
   a. Si es check de auth central → usa token SA (del guard super_admin)
   b. Si es check de tenant → usa token tenant (generado con POST /api/login al tenant)
4. Resultados se muestran en UI y se pueden exportar como MD
```

### 8.3 Guards del spider — estado real vs esperado

El `application_report.md` dice: *"API Spider QA: `auth:super_admin`"*

Pero el spider hace requests con Bearer token. El guard `super_admin` es un **guard de sesión web**, no de API token. Esto crea la ambigüedad:

- Si las rutas `/api/spider/*` tienen `auth:super_admin` → aceptan sesión web, no Bearer token
- Si el spider hace requests API con `Authorization: Bearer xxx` → el guard web ignora el header → retorna 200 sin validar (acceso libre) → eso es exactamente lo que muestra el reporte: **HTTP 200 sin token** en rutas del spider

**Conclusión:** Las rutas del spider están desprotegidas porque usan el guard equivocado (web en lugar de API).

---

## 9. CAMPOS CRÍTICOS — POST CRUD AUDIT (Marzo 2026)

### Cambios de nombres de campo aplicados

| Modelo | Campo anterior (JS/UI) | Campo actual (DB) | Archivos afectados |
|---|---|---|---|
| Producto | `precio` | `valor_venta` | `productos.blade.php`, `PublicApiController`, `VentaService`, POS JS |
| Producto | `stock` | `cantidad` | ídem |
| Producto | `stock_minimo` | `cantidad_minima` | ídem |
| Producto | `tipo` | `tipo_producto` | ídem |
| Producto | `activo` (bool) | `estado` (enum: 'activo'/'inactivo') | `PublicApiController`, queries |
| User | `clave` | `password` | `usuarios.blade.php` |
| Cliente | *(no existía)* | `giro` | migración `2026_03_18_014000` |
| Cliente | *(no existía)* | `direccion` | ídem |

### API response del POS — contrato

El endpoint `/api/productos/buscar?q=xxx` debe retornar **ambos alias** para compatibilidad:
```json
{
  "id": 1,
  "nombre": "Leche Colún 1L",
  "precio": 1190,        // alias para JS del POS que aún usa 'precio'
  "valor_venta": 1190,   // campo real en DB
  "stock": 48,           // alias para JS del POS
  "cantidad": 48,        // campo real en DB
  "tipo": "stock_fisico",
  "tipo_producto": "stock_fisico"
}
```

---

## 10. ESTADO DE HITOS — LO QUE ESTÁ IMPLEMENTADO

| Hito | Módulo | Estado | Notas |
|---|---|---|---|
| H0 | Infraestructura | ✅ | Docker, PG, Laravel, Sanctum, tenancy |
| H1 | POS venta minorista | ✅ | VentaController, ProductoController, pos_v3.html |
| H2 | Multi-operario + Roles | ✅ | CheckRole middleware, Gates |
| H3 | Renta + Servicios + Fraccionados | ✅ | RentaService, timer en DB |
| H4 | WhatsApp Onboarding | ✅ | WhatsAppService, Jobs, webhooks |
| H5 | Super Admin + Billing | ✅ | CentralAuthController, MetricsService, cobros |
| H6 | Portal Cliente Web | ✅ | ClientePortalController, portal_cliente.html |
| H7 | Config dinámica por industria | ✅ | rubros_config, presets en seeder |
| H8 | ERP ↔ WhatsApp Bot | ✅ | JwtBridgeService, BotApiController |
| H9 | SII / LibreDTE | ✅ | SiiService, EmitirDteJob, config_sii, dte_emitidos |
| H10 | Compras y Proveedores | ✅ | CompraController, OrdenCompra, ProveedorController |
| H11 | Delivery y Logística | ✅ | DeliveryController, DeliveryService |
| H12 | Recetas / Ingredientes | ✅ | RecetaService, RecetaController |
| H13 | RRHH Completo | ✅ | RrhhController, RrhhService, Empleado, Asistencia, Liquidacion |
| H14 | Reclutamiento | ✅ | ReclutamientoController |
| H15 | Marketing QR | ✅ | QrCampanaController, QrGenerator |
| H16 | SaaS M31 | ✅ | SaasController, SaasPipeline, SaasCobros |
| H17 | Dashboard + API pública | ✅ | DashboardController, PublicApiController |
| H19 | Spider QA | ✅ | SpiderController, spider.blade.php, spider_tests.json |
| H20 | Seeders demo | ✅ | DemoTenantsSeeder, TenantDemoDataSeeder |
| H21 | Reportes avanzados | ⬜ | Propuesto, no iniciado |
| H22 | (no definido) | — | — |
| H23 | Estrategia de errores QA | ✅ | tests/BUGS.md, helpers.sh |
| H24 | Master Bug Fixes | ✅ | diagnose_tenant.sh, spider.blade.php integrado |

---

## 11. BUGS CONOCIDOS — ESTADO ACTUAL

### Del Spider QA v3 (2026-03-18)

| Bug ID | Endpoint | HTTP recibido | HTTP esperado | Causa real |
|---|---|---|---|---|
| SP-001 a SP-047 | Todas las rutas de `demo.localhost` | 0 | 200/30x | DNS: `demo.localhost` no en `/etc/hosts` del entorno donde corre el spider |
| SP-048 a SP-054 | `/api/spider/*`, `/central/spider` | 200 sin token | 401 | Guard web `super_admin` no valida Bearer tokens — rutas desprotegidas para requests API |
| SP-052/053 | `/api/spider/token` | 404 | 200/401 | Ruta no existe — pero `/central/spider/token` SÍ existe (en web.php) |
| SP-055 a SP-058 | `/api/bot/config`, `/api/compras*` | 402 | 200 | Tenant `demo` sin plan activo o trial vencido → `CheckTenantStatus` middleware |
| SP-059 | `/api/config/mi-plan` | 404 | 200 | Ruta no existe en `tenant.php` |
| SP-060 a SP-082 | Delivery, RRHH, Recetas, Rentas, Reclutamiento | 403 | 200 | `CheckModuleAccess` bloqueando — módulos inactivos en tenant `demo`. **Comportamiento correcto** |
| SP-083 | `/api/user` | 401 | 200 | Token Sanctum del central usado en tenant — tokens no son cross-schema |
| SP-084 | `/api/v1/public/productos` | 500 | 200 | `PublicApiController@productos` crashea: `.with('categoria')` (relación no existe), `where('activo', true)` (campo es enum string), campos `precio`/`stock` renombrados |
| SP-085 | `/api/v1/public/ventas` | 422 | 200 | POST sin body — test del spider mal diseñado |
| SP-086 | `demo.localhost` | 0 | 200 | Same DNS C1 |

### Del CRUD Audit (2026-03-18)

| Bug | Archivo | Fix aplicado |
|---|---|---|
| Campo `precio` → `valor_venta` en Producto | `productos.blade.php` | ✅ |
| Campo `stock` → `cantidad` | ídem | ✅ |
| Campo `clave` → `password` en Usuario | `usuarios.blade.php` | ✅ |
| Campos `giro`, `direccion` faltaban en Cliente | migración nueva | ✅ |
| Template literals con backticks escapados | `config.blade.php`, `recetas.blade.php` | ✅ |
| RRHH sin modales de crear/editar | `rrhh.blade.php` | ✅ |

---

## 12. GAPS IDENTIFICADOS — LO QUE FALTA O ESTÁ PENDIENTE

### 12.1 Gaps de código

| Gap | Descripción | Prioridad |
|---|---|---|
| `PublicApiController@productos` crashea | 500 por campos renombrados + relación inexistente | 🔴 Crítico |
| Ruta `/api/config/mi-plan` no existe | Spider la necesita, tenant la necesita para saber su plan | 🟠 Alto |
| Rutas spider sin protección real | Guard web en rutas API → acceso libre con cualquier request | 🟠 Alto |
| Spider no genera token por tenant | Usa token del central en tenants → 401 en `/api/user` | 🟠 Alto |
| `/api/spider/token` no existe en API | Solo existe `/central/spider/token` en web.php | 🟡 Medio |
| Tenant `demo` sin plan activo | `CheckTenantStatus` devuelve 402 en varios endpoints | 🟡 Medio |

### 12.2 Gaps de cobertura del spider

El spider NO testea:
- Flujo completo de venta: `POST /api/ventas` → items → confirmar → ticket
- CRUDs con campos nuevos: `POST /api/clientes` con `giro`/`direccion`, `POST /api/productos` con `valor_venta`
- Vistas admin autenticadas: `/admin/ventas`, `/admin/fiados`, `/admin/agenda`, `/admin/sii`
- `/central/modulos` — la ruta más importante del central no está en los tests
- Módulos en el tenant correcto: RRHH en `demo-legal`, Rentas en `demo-padel`, Reclutamiento en `demo-saas`

### 12.3 Rutas del spider que ya no existen (rutas viejas)

| Ruta en spider_tests.json | Estado | Reemplazada por |
|---|---|---|
| `http://demo.localhost:8000/admin/login` | ❌ No existe | `/auth/login/web` |
| `http://localhost:8000/login` | ❌ No existe | `/central/login` |
| `http://localhost:8000/web/logout` | ❌ No existe | `POST /central/logout` |
| `http://demo.localhost:8000/auth/me` | ❓ No estándar | `/api/user` |
| `http://demo.localhost:8000/rentas` | ❓ Verificar | Puede ser `/pos` con M06, o `/admin/rentas` |
| `http://demo.localhost:8000/operario` | ❓ Verificar | Puede estar integrado en `/pos` |
| Webhooks como GET | ❌ Son POST | Cambiar método en spider_tests.json |

---

## 13. COMANDOS ÚTILES

```bash
# Ver TODAS las rutas registradas (238 total)
php artisan route:list

# Ver rutas de un path específico
php artisan route:list --path=api/spider
php artisan route:list --path=api/config
php artisan route:list --path=api/v1/public
php artisan route:list --path=central

# Correr migraciones en tenant específico
php artisan tenants:migrate --tenants=demo-ferreteria

# Correr migraciones en TODOS los tenants
php artisan tenants:migrate

# Regenerar spider_tests.json desde las rutas reales
cd /home/master/trabajo/proyectos/src/benderandos
bash tests/sync_spider_tests.sh

# Ver qué módulos tiene activos un tenant
php artisan tinker
>>> $t = App\Models\Tenant::find('demo-ferreteria');
>>> $t->run(fn() => App\Models\ConfigModulo::where('activo',true)->pluck('modulo_id'));

# Activar todos los módulos en tenant demo
>>> $t = App\Models\Tenant::find('demo'); // o el ID UUID real
>>> $t->run(fn() => App\Models\ConfigModulo::query()->update(['activo'=>true]));

# Ver schema del tenant en psql
psql -U postgres benderand_erp
\dn  -- lista schemas
SET search_path = tenant_demo_ferreteria;
\dt  -- lista tablas del tenant

# Verificar columnas de productos en un tenant
>>> $t->run(fn() => Schema::getColumnListing('productos'));

# Limpiar caches
php artisan optimize:clear
php artisan route:cache
php artisan view:clear
```

---

## 14. PRÓXIMA AUDITORÍA — CHECKLIST RECOMENDADO

Para que antigraviity pueda hacer una auditoría completa con contexto correcto:

### Paso 1 — Leer rutas reales
```bash
php artisan route:list --json > /tmp/routes_real.json
# Subir routes_real.json como contexto
```

### Paso 2 — Leer código de los controllers críticos
```bash
cat app/Http/Controllers/SpiderController.php
cat app/Http/Controllers/Tenant/PublicApiController.php  # o donde esté
cat app/Http/Controllers/Tenant/ConfigController.php
cat routes/api.php
cat routes/tenant.php
# Subir estos archivos como contexto
```

### Paso 3 — Leer spider_tests.json actual
```bash
cat tests/spider_tests.json
# Subir como contexto para cruzar con rutas reales
```

Con esos 5 archivos, antigraviity puede generar un audit 100% preciso sin suposiciones.

---

*BenderAnd ERP · Project Knowledge Base · Marzo 2026*
*238 rutas · 8 tenants demo · Spider QA v3 · H0–H17 + H19/H20/H23/H24 completados*
*Ruta del proyecto: /home/master/trabajo/proyectos/src/benderandos/*
