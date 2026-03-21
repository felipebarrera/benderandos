# BENDERAND ERP — KNOWLEDGE MASTER
## Documento completo de conocimiento del proyecto
*Generado: 2026-03-18 · Fuente: código real del container `benderandos_app`*
*Para dejar en la carpeta del proyecto en Claude Web como contexto permanente*

---

## 1. QUÉ ES ESTE PROYECTO

BenderAnd ERP es una plataforma **SaaS multi-tenant** para gestión comercial de múltiples rubros (abarrotes, ferretería, clínica, motel, pádel, legal, saas). Cada empresa es un **tenant** con su propio schema de PostgreSQL. El sistema se configura por industria activando módulos atómicos (M01–M32).

**Tres sistemas integrados:**
1. **ERP Core** — Laravel 11, POS, admin, facturación, RRHH, delivery, etc.
2. **WhatsApp Bot** — Node.js (Moteland), integrado vía JWT Bridge
3. **SII / LibreDTE** — Facturación electrónica chilena dentro del flujo de ventas

---

## 2. INFRAESTRUCTURA

```
Container : benderandos_app   (imagen: php:8.4-cli-alpine — usa sh, NO bash)
DB        : benderandos_pg    (postgres:16 · user:benderand · pass:benderand123 · db:benderand)
Redis     : benderandos_redis
Puerto    : host:8000 → container:8000
Código    : volumen .:/app (el código del host está montado en /app)
```

**Ejecutar comandos en el container:**
```sh
docker exec benderandos_app sh -c "cd /app && <comando>"
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"
```

**Conexiones de BD (`config/database.php`):**
- `central` → schema `public` (tablas globales: tenants, plans, super_admins, etc.)
- `pgsql` → schema `public` (alias del central)
- `tenant` → schema dinámico — stancl/tenancy cambia `search_path` en runtime al inicializar un tenant

**Multi-tenancy:** stancl/tenancy v3 con `PostgreSQLSchemaManager`. Cada tenant tiene un schema propio (`tenant{id}`) con sus propias tablas. La identificación es por subdominio (`{slug}.localhost`).

---

## 3. ESTRUCTURA DEL PROYECTO

```
/app
├── routes/
│   ├── web.php          → Panel central web (login, dashboard, spider, billing, tenants)
│   ├── api.php          → API central (Sanctum, modulos plan, Spider QA endpoints)
│   ├── central.php      → API superadmin (dashboard, metrics, tenants CRUD)
│   ├── tenant.php       → TODAS las rutas de tenant (425 líneas)
│   └── webhook.php      → Webhooks externos WhatsApp (con X-Bot-Token)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── SpiderController.php              ← Spider QA
│   │   │   ├── Central/
│   │   │   │   ├── BillingController.php
│   │   │   │   ├── CentralAuthController.php
│   │   │   │   ├── MetricsController.php
│   │   │   │   ├── ModuloPlanController.php      ← /api/central/plan/modulos
│   │   │   │   ├── TenantManageController.php
│   │   │   │   ├── WhatsAppPedidoController.php
│   │   │   │   └── WhatsAppWebhookController.php
│   │   │   └── Tenant/
│   │   │       ├── Api/
│   │   │       │   ├── MiPlanController.php      ← /api/config/mi-plan
│   │   │       │   ├── PublicApiController.php   ← /api/v1/public/*
│   │   │       │   └── TokenController.php       ← /api/user/tokens
│   │   │       ├── AuthController.php
│   │   │       ├── BotApiController.php
│   │   │       ├── ClienteController.php
│   │   │       ├── ClientePortalController.php
│   │   │       ├── CompraController.php
│   │   │       ├── ConfigBotController.php
│   │   │       ├── ConfigRubroController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── DeliveryController.php
│   │   │       ├── InternalBotController.php
│   │   │       ├── MarketingController.php
│   │   │       ├── OrdenCompraController.php
│   │   │       ├── PagoController.php
│   │   │       ├── ProductoController.php
│   │   │       ├── QrLandingController.php
│   │   │       ├── RecetaController.php
│   │   │       ├── ReclutamientoController.php
│   │   │       ├── RentaController.php
│   │   │       ├── RrhhController.php
│   │   │       ├── Saas{Dashboard,Pipeline,Cobro,Cliente}Controller.php
│   │   │       ├── SiiController.php
│   │   │       ├── UsuarioController.php
│   │   │       ├── VentaController.php
│   │   │       └── WebPanelController.php
│   │   └── Middleware/
│   │       ├── CheckModuleAccess.php  ← genera 403/402 por módulos
│   │       ├── CheckTenantStatus.php  ← genera 403 si tenant suspendido
│   │       ├── CheckRole.php          ← genera 403 si rol insuficiente
│   │       ├── InternalBotAuth.php    ← valida X-Bot-Token (JWT_SHARED_SECRET)
│   │       ├── JwtBridgeMiddleware.php ← puente ERP↔Node.js
│   │       └── LogUnauthorizedRequests.php
│   └── Models/
│       ├── SuperAdmin.php             ← conexión 'central', tiene HasApiTokens
│       ├── Central/
│       │   ├── Tenant.php             ← modelo de tenant (stancl)
│       │   ├── Plan.php
│       │   ├── PlanModulo.php
│       │   ├── Subscription.php       ← suscripción del tenant
│       │   ├── PagoSubscription.php
│       │   └── AuditLog.php
│       └── Tenant/
│           ├── RubroConfig.php        ← tabla rubros_config, campo modulos_activos
│           ├── Producto.php
│           ├── Cliente.php
│           ├── Usuario.php            ← Authenticatable + HasApiTokens
│           ├── Venta.php / ItemVenta.php
│           ├── Compra.php / ItemCompra.php
│           ├── OrdenCompra.php
│           ├── Proveedor.php
│           ├── Renta.php
│           ├── Empleado.php / Asistencia.php / Liquidacion.php
│           ├── Receta.php / IngredienteReceta.php
│           ├── Entrega.php / Repartidor.php / ZonaEnvio.php
│           ├── Saas{Cliente,Pipeline,Cobro,Plan,Metrica}.php
│           └── [otros 30+ modelos...]
└── tests/
    ├── spider_tests.json   ← 181 tests del Spider QA
    └── sync_spider_tests.sh
```

---

## 4. SISTEMA DE AUTENTICACIÓN

### 4.1 Guards (config/auth.php)

| Guard | Driver | Provider | Usado en |
|---|---|---|---|
| `web` | session | users (Usuario tenant) | Sesión web del tenant |
| `super_admin` | **session** | super_admins | Panel central web `/central/*` |
| `super_admin_api` | token (legacy) | super_admins | No usado activamente |
| `sanctum` | token Bearer | users o SuperAdmin | API del tenant y API central |

> **IMPORTANTE:** `auth:super_admin` es un guard de **sesión**, no de Bearer token. Las rutas del panel central (`/central/*`) requieren cookie de sesión, no Authorization header. Las rutas API del spider ahora usan `auth:sanctum`.

### 4.2 Tokens Sanctum — dónde se guardan

```
Schema public → personal_access_tokens del SuperAdmin
               Creados en: POST /central/spider/token
               Usados en: /api/spider/*, /api/central/plan/*

Schema tenant_{id} → personal_access_tokens del Usuario del tenant
                     Creados en: POST /api/login (en el tenant)
                     Usados en: todos los /api/* del tenant
```

**Los tokens NO son portables entre central y tenant.** Hay que autenticarse por separado en cada contexto.

### 4.3 Flujo login tenant → token

```
POST http://{tenant}.localhost:8000/api/login
Body: { "email": "admin@demo-{rubro}.cl", "password": "demo1234" }

Response 200:
{
  "token": "1|xxxxx...",
  "usuario": { "id": 1, "nombre": "Admin Demo", "email": "...", "rol": "admin" },
  "permisos": ["*"]
}

Usar en headers: Authorization: Bearer 1|xxxxx...
```

### 4.4 Abilities por rol (tokens del tenant)

| Rol | Abilities del token |
|---|---|
| `admin` / `super_admin` | `["*"]` (todo) |
| `cajero` | `ver:dashboard, ver:productos, crear:venta, ver:ventas, ver:clientes, crear:cliente` |
| `bodega` / `operario` | `ver:dashboard, ver:productos, editar:stock, ver:compras, crear:compra` |
| `cliente` | `ver:mis-pedidos` |

### 4.5 Auth modelo Usuario — campo de contraseña

```php
// Modelo Usuario usa 'clave_hash' como campo de contraseña
// (NO 'password' — getAuthPassword() retorna $this->clave_hash)
// El seeder crea con: 'clave_hash' => Hash::make('demo1234')
// La validación en AuthController valida campo 'password' del request
// y lo compara contra clave_hash con Hash::check()
```

---

## 5. SISTEMA DE MÓDULOS

### 5.1 Catálogo M01–M32

| ID | Nombre | Precio | Contexto |
|---|---|---|---|
| M01 | Venta simple | $0 BASE | pos |
| M02 | Venta multi-operario | $9.990 | pos |
| M03 | Stock físico | $0 BASE | pos+admin |
| M04 | Stock fraccionado | $4.990 | pos |
| M05 | Renta / Arriendo | $9.990 | pos |
| M06 | Renta por hora | $9.990 | pos |
| M07 | Servicios sin stock | $0 BASE | pos |
| M08 | Agenda / Citas | $9.990 | pos+admin |
| M09 | Honorarios | $4.990 | pos |
| M10 | Notas cifradas | $4.990 | pos |
| M11 | Fiado / Crédito | $4.990 | pos |
| M12 | Encargos / Reservas | $4.990 | pos+admin |
| M13 | Delivery / Envíos | $14.990 | pos+admin |
| M14 | Habitaciones / Recursos | $9.990 | pos |
| M15 | Comandas / Cocina | $9.990 | pos |
| M16 | Recetas / Ingredientes | $9.990 | admin |
| M17 | Pedido remoto WhatsApp | $14.990 | pos+admin |
| M18 | Compras / Proveedores | $9.990 | admin |
| M19 | Inventario avanzado | $9.990 | admin |
| M20 | SII / Facturación DTE | $14.990 | admin+pos |
| M21 | RRHH / Asistencia | $9.990 | admin |
| M22 | Liquidaciones | $9.990 | admin |
| M23 | Reclutamiento | $9.990 | admin |
| M24 | Marketing QR | $9.990 | admin |
| M25 | Portal cliente web | $9.990 | cliente |
| M26 | Descuento por volumen | $4.990 | pos |
| M27 | Multi-sucursal | $19.990 | admin |
| M28 | Órdenes de trabajo | $9.990 | pos+admin |
| M29 | Historial por recurso | $4.990 | admin |
| M30 | Membresías / Suscripciones | $9.990 | pos+admin |
| M31 | Venta Software SaaS | $24.990 | pos+admin |
| M32 | CRM Modular | $9.990 | pos+admin+bot |

### 5.2 Cómo se controla el acceso a módulos

**Modelo:** `App\Models\Tenant\RubroConfig` (tabla `rubros_config` en schema del tenant)
**Campo clave:** `modulos_activos` → JSON array, ej: `["M01","M03","M18","M20"]`

**Middleware `CheckModuleAccess`** — detecta el módulo por path:

```php
const MODULE_GATES = [
    'rentas'       => 'M05',  'compras'      => 'M18',
    'delivery'     => 'M13',  'recetas'      => 'M16',
    'rrhh'         => 'M21',  'reclutamiento'=> 'M23',
    'bot'          => 'M17',  'inventario'   => 'M19',
    'dte'          => 'M20',  'qr'           => 'M24',
    'portal'       => 'M25',  'membresias'   => 'M30',
    // etc...
];
```

**Flujo de decisión:**
1. Detecta módulo requerido por el path del request
2. Lee `RubroConfig::first()->modulos_activos`
3. Si módulo no está → **HTTP 403** `{error: "modulo_no_activo"}`
4. Si está → busca `Subscription` del tenant en schema `public`
5. Sin suscripción → **HTTP 402** `{error: "no_subscription"}`
6. Suscripción vencida → **HTTP 402** `{error: "suscripcion_vencida"}`

### 5.3 Módulos activos por tenant demo

| Tenant | Módulos activos |
|---|---|
| `demo-legal` | M01 M07 M08 M09 M10 M20 M21 M32 |
| `demo-padel` | M01 M03 M05 M06 M08 M17 M30 M32 |
| `demo-motel` | M01 M03 M05 M06 M14 |
| `demo-abarrotes` | M01 M02 M03 M04 M11 M12 M17 M18 M20 M24 M25 M32 |
| `demo-ferreteria` | M01 M02 M03 M04 M07 M11 M17 M18 M19 M20 M24 M26 M32 |
| `demo-medico` | M01 M07 M08 M09 M10 M20 M21 M32 |
| `demo-saas` | M01 M07 M20 M21 M22 M23 M24 M25 M27 M31 M32 |

**Sin ningún tenant activo:** M13 (Delivery), M15 (Comandas), M16 (Recetas), M28 (OT), M29 (Historial recurso)

---

## 6. ENDPOINTS — REFERENCIA COMPLETA

### 6.1 Central — Panel Web (`localhost:8000`)

```
GET  /central/login           → Formulario login super_admin
POST /central/login           → Autenticar super_admin (sesión)
POST /central/logout          → Cerrar sesión
GET  /central                 → Dashboard métricas [auth:super_admin]
GET  /central/tenants         → Lista tenants [auth:super_admin]
GET  /central/billing         → Facturación [auth:super_admin]
GET  /central/planes          → Gestión planes [auth:super_admin]
GET  /central/modulos         → Gestión módulos M01-M32 [auth:super_admin]
GET  /central/spider          → Spider QA UI [auth:super_admin]
POST /central/spider/token    → Genera Bearer token para Spider [auth:super_admin]
```

### 6.2 Central — API (`localhost:8000/api/*`)

```
# Módulos del plan (auth:sanctum — token de SuperAdmin)
GET  /api/central/plan/modulos          → Lista M01-M32 con precios
PUT  /api/central/plan/modulos/{id}     → Actualizar precio/estado módulo
GET  /api/central/plan/modulos/{id}/impacto → Tenants afectados si se cambia

# Spider QA (auth:sanctum — token de SuperAdmin, cambiado de super_admin)
GET  /api/spider/probe                  → Test de conectividad HTTP proxy
GET  /api/spider/db-check               → Health check de DB central
POST /api/spider/sync                   → Sincronizar tests desde route:list
GET  /api/spider/tests                  → Leer spider_tests.json
POST /api/spider/tests                  → Guardar spider_tests.json

# Usuario del token
GET  /api/user                          → Usuario autenticado [auth:sanctum]
```

### 6.3 Tenant — Autenticación

```
# Login (públicas)
POST /auth/login                        → Login API (retorna Bearer token)
POST /api/login                         → Alias para el spider

# Sesión web (públicas)
GET  /auth/login/web                    → Formulario login Blade
POST /auth/login/web                    → Login web (crea sesión + cookie)
POST /web/logout                        → Cerrar sesión web

# Autenticadas
POST /auth/logout                       → Revocar token Bearer [sanctum]
GET  /auth/me                           → Usuario + permisos actuales [sanctum]
```

### 6.4 Tenant — Vistas Admin Web (requieren sesión web / `auth`)

```
GET /admin/dashboard          → Panel principal
GET /admin/productos          → Gestión productos
GET /admin/clientes           → Gestión clientes
GET /admin/compras            → Compras básicas
GET /admin/compras-avanzadas  → Compras avanzadas + OC
GET /admin/usuarios           → Gestión usuarios y roles
GET /admin/reportes           → Reportes
GET /admin/rentas             → Panel de rentas
GET /admin/config             → Configuración de la empresa
GET /admin/whatsapp           → Config WhatsApp bot
GET /admin/sii                → Panel SII / facturación
GET /admin/delivery           → Panel delivery
GET /admin/recetas            → Recetas / ingredientes
GET /admin/rrhh               → RRHH / asistencia
GET /admin/reclutamiento      → Reclutamiento
GET /admin/marketing          → Marketing QR
GET /admin/saas/dashboard     → Dashboard SaaS (M31)
GET /admin/api-docs           → Documentación API [solo admin]
```

### 6.5 Tenant — POS y Operario

```
GET /pos                      → POS principal [auth]
GET /pos/historial            → Historial de ventas del POS [auth]
GET /rentas                   → Panel rentas (alias) [auth]
GET /operario                 → Vista operario [auth]
GET /pos/saas/tenants         → Lista tenants (M31) [auth]
GET /pos/saas/pipeline        → Pipeline ventas (M31) [auth]
```

### 6.6 Tenant — API Productos

```
# Todos requieren Bearer token (auth:sanctum)
GET  /api/productos                     → Lista paginada (50/página)
GET  /api/productos/buscar?q={texto}    → Búsqueda rápida POS (máx 20 results)
GET  /api/productos/{id}                → Detalle producto
POST /api/productos                     → Crear [admin, bodega]
PUT  /api/productos/{id}                → Editar [admin, bodega]
POST /api/productos/{id}/ajuste-stock   → Ajuste manual de cantidad [admin, bodega]
```

**Response de búsqueda (`/api/productos/buscar`):**
```json
[
  {
    "id": 1,
    "codigo": "DEMO-001",
    "nombre": "Producto Demo 1",
    "valor_venta": 10000,
    "cantidad": 100.000,
    "unidad_medida": null,
    "fraccionable": false
  }
]
```

**Body para crear/editar producto:**
```json
{
  "codigo": "ABC-123",
  "nombre": "Leche Colún 1L",
  "tipo_producto": "stock_fisico",
  "valor_venta": 1190,
  "cantidad": 48,
  "cantidad_minima": 10,
  "unidad_medida": "unidad",
  "fraccionable": false,
  "estado": "activo"
}
```

**Tipos de producto válidos:** `stock_fisico`, `servicio`, `renta`, `fraccionado`, `honorarios`
**Estados válidos:** `activo`, `inactivo`, `agotado`

### 6.7 Tenant — API Clientes

```
GET  /api/clientes                      → Lista clientes
GET  /api/clientes/{id}                 → Detalle cliente
POST /api/clientes                      → Crear [admin, cajero]
PUT  /api/clientes/{id}                 → Editar [admin, cajero]
```

**Body:**
```json
{
  "nombre": "Juan Pérez",
  "rut": "12345678-9",
  "email": "juan@ejemplo.cl",
  "telefono": "+56912345678",
  "giro": "Comercio al por menor",
  "direccion": "Av. Providencia 1234"
}
```

### 6.8 Tenant — API Ventas

```
GET  /api/ventas                        → Lista ventas
GET  /api/ventas/por-cliente            → Ventas filtradas por cliente
GET  /api/ventas/{id}                   → Detalle venta
POST /api/ventas                        → Crear nueva venta
POST /api/ventas/{id}/items             → Agregar ítem a venta
DELETE /api/ventas/{ventaId}/items/{itemId} → Quitar ítem
PUT  /api/ventas/{id}/estado            → Tomar venta (en_caja) [admin, cajero]
POST /api/ventas/{id}/confirmar         → Confirmar y cobrar [admin, cajero]
POST /api/ventas/{id}/anular            → Anular venta [permiso: anular-ventas]
```

### 6.9 Tenant — API Compras y Proveedores (M18 requerido)

```
GET  /api/compras                       → Lista compras
GET  /api/compras/dashboard             → Métricas de compras
GET  /api/compras/alertas-stock         → Productos bajo stock mínimo
GET  /api/proveedores                   → Lista proveedores
GET  /api/proveedores/{id}              → Detalle proveedor
POST /api/proveedores                   → Crear proveedor
PUT  /api/proveedores/{id}              → Editar proveedor
GET  /api/ordenes-compra                → Lista OC
POST /api/ordenes-compra                → Crear OC
POST /api/ordenes-compra/{id}/autorizar → Autorizar OC
POST /api/ordenes-compra/{id}/enviar    → Enviar al proveedor
POST /api/ordenes-compra/{id}/recepcion → Registrar recepción
POST /api/ordenes-compra/{id}/anular    → Anular OC
```

### 6.10 Tenant — API Delivery (M13 requerido — sin tenant activo)

```
GET  /api/delivery/dashboard            → Métricas delivery
GET  /api/delivery/entregas             → Lista entregas
GET  /api/delivery/entregas/{id}        → Detalle entrega
POST /api/delivery/entregas/{id}/asignar → Asignar repartidor
POST /api/delivery/entregas/{id}/estado  → Cambiar estado
GET  /api/delivery/repartidores         → Lista repartidores
POST /api/delivery/repartidores         → Crear repartidor
GET  /api/delivery/zonas                → Zonas de envío
POST /api/delivery/zonas                → Crear zona
GET  /tracking/{uuid}                   → Tracking público (sin auth)
```

### 6.11 Tenant — API RRHH (M21 requerido — admin únicamente)

```
GET  /api/rrhh/dashboard                → Resumen RRHH
GET  /api/rrhh/empleados                → Lista empleados
POST /api/rrhh/empleados                → Crear empleado
PUT  /api/rrhh/empleados/{id}           → Editar empleado
POST /api/rrhh/asistencia/entrada       → Marcar entrada
POST /api/rrhh/asistencia/salida        → Marcar salida
GET  /api/rrhh/asistencia/hoy           → Asistencia del día
GET  /api/rrhh/vacaciones               → Lista vacaciones
POST /api/rrhh/vacaciones               → Solicitar vacación
POST /api/rrhh/vacaciones/{id}/resolver → Aprobar/rechazar
POST /api/rrhh/permisos                 → Solicitar permiso
POST /api/rrhh/permisos/{id}/resolver   → Aprobar/rechazar permiso
GET  /api/rrhh/liquidaciones            → Lista liquidaciones
POST /api/rrhh/liquidaciones/generar    → Generar una liquidación
POST /api/rrhh/liquidaciones/masivo     → Generar liquidaciones masivo
```

### 6.12 Tenant — API Reclutamiento (M23 — solo demo-saas)

```
GET  /api/reclutamiento/dashboard
GET  /api/reclutamiento/ofertas
POST /api/reclutamiento/ofertas
GET  /api/reclutamiento/postulaciones
GET  /api/reclutamiento/postulaciones/{id}
POST /api/reclutamiento/postulaciones/{id}/mover
POST /api/reclutamiento/postulaciones/{id}/entrevista
# Públicas (sin auth):
GET  /api/empleo/ofertas
GET  /api/empleo/ofertas/{slug}
POST /api/empleo/ofertas/{slug}/postular
```

### 6.13 Tenant — API Marketing QR (M24)

```
GET  /api/marketing/dashboard
GET  /api/marketing/campanas
POST /api/marketing/campanas
GET  /api/marketing/campanas/{id}
POST /api/marketing/campanas/{id}/qrs   → Generar QR codes
GET  /api/marketing/escaneos            → Métricas de escaneos
GET  /qr/{uuid}                         → Landing de escaneo QR (público)
```

### 6.14 Tenant — API SII / Facturación (M20)

```
GET  /api/sii/dashboard                 → Resumen DTE emitidos
GET  /api/sii/dtes                      → Lista DTEs
GET  /api/sii/dtes/{id}                 → Detalle DTE
GET  /api/sii/libro-ventas              → Libro de ventas
GET  /api/sii/config                    → Config SII (rut, ambiente)
PUT  /api/sii/config                    → Actualizar config [admin]
POST /api/sii/emitir/{ventaId}          → Emitir DTE de una venta [admin]
POST /api/sii/nota-credito/{dteId}      → Emitir NC [admin]
POST /api/sii/consultar-estado/{dteId}  → Consultar estado en SII [admin]
```

### 6.15 Tenant — API SaaS (M31 — solo demo-saas)

```
GET  /api/saas/dashboard                → MRR, churn, tenants activos
POST /api/saas/generar-snapshot         → Snapshot de métricas
GET  /api/saas/clientes                 → CRM de clientes/tenants
GET  /api/saas/clientes/{id}
POST /api/saas/clientes
PUT  /api/saas/clientes/{id}
GET  /api/saas/cobros                   → Historial de cobros
POST /api/saas/cobros/generar-mes       → Generar facturación del mes
POST /api/saas/cobros/vencimientos      → Procesar vencimientos
POST /api/saas/cobros/{id}/pago         → Registrar pago manual
GET  /api/saas/pipeline                 → Pipeline de ventas
POST /api/saas/pipeline
PUT  /api/saas/pipeline/{id}/etapa
POST /api/saas/pipeline/{id}/demo
POST /api/saas/pipeline/{id}/actividad
```

### 6.16 Tenant — API Configuración

```
GET  /api/config/rubro                  → Config de la industria del tenant
PUT  /api/config/rubro                  → Actualizar config [admin]
GET  /api/config/mi-plan                → Plan activo + módulos habilitados
GET  /api/config/modulos-disponibles    → Módulos disponibles para activar
POST /api/config/modulos/{id}/activar   → Activar módulo [admin]
POST /api/config/modulos/{id}/desactivar → Desactivar módulo [admin]
GET  /api/config/modulos/{id}/preview   → Preview del módulo
POST /api/config/modulos-rubro/{id}/toggle → Toggle rápido [admin]
POST /api/config/aplicar-preset/{industria} → Aplicar preset industria [admin]
GET  /api/bot/config                    → Config del bot WA
PUT  /api/bot/config                    → Actualizar config bot [admin]
```

### 6.17 Tenant — API Recetas (M16 — sin tenant activo)

```
GET  /api/recetas/dashboard
GET  /api/recetas
GET  /api/recetas/{id}
POST /api/recetas
PUT  /api/recetas/{id}
POST /api/recetas/{id}/recalcular       → Recalcular costos
POST /api/recetas/{id}/verificar-stock  → Verificar insumos disponibles
POST /api/recetas/{id}/producir         → Producir y descontar ingredientes
GET  /api/producciones                  → Historial de producciones
GET  /api/recetas-reporte/costos        → Reporte de rentabilidad
```

### 6.18 Tenant — API Rentas (M05/M06)

```
GET  /api/rentas/panel                  → Estado actual de recursos (habitaciones/canchas)
POST /api/rentas/{id}/extender          → Extender tiempo de renta
POST /api/rentas/{id}/devolver          → Devolver/cerrar renta
```

### 6.19 Tenant — API Bot (M17 — header `X-Bot-Token`)

```
GET  /api/bot/stock/{sku}               → Stock de un producto por SKU
GET  /api/bot/precio/{sku}              → Precio de un producto
GET  /api/bot/cliente/{telefono}        → Datos del cliente
GET  /api/bot/agenda/disponibilidad     → Horarios disponibles
POST /api/bot/pedido                    → Crear pedido desde WA
GET  /api/bot/pedido/{id}/estado        → Estado de un pedido
```

### 6.20 Tenant — API Interna Bot ERP (header `X-Bot-Token`)

```
GET  /api/internal/productos/stock      → Stock detallado para el bot
GET  /api/internal/clientes/buscar      → Buscar cliente
POST /api/internal/ventas/remota        → Crear venta remota desde WA
```

### 6.21 Tenant — API Pública (auth:sanctum)

```
GET  /api/v1/public/productos           → Catálogo público
GET  /api/v1/public/clientes            → Lista clientes
GET  /api/v1/public/stock/{sku}         → Stock por código
POST /api/v1/public/ventas              → Crear venta (integración externa)
```

### 6.22 Tenant — Portal Cliente (M25)

```
# Públicas:
GET  /portal/login
POST /portal/login
POST /portal/logout

# Auth Sanctum + abilities:
GET  /portal/catalogo                   → Catálogo de productos
GET  /portal/historial                  → Historial de compras
POST /portal/pedido                     → Crear pedido remoto
GET  /portal/deudas                     → Deudas pendientes
POST /portal/pedido/{venta}/pagar       → Iniciar pago Transbank
```

### 6.23 Tenant — Usuarios y Roles

```
GET  /api/usuarios                      → Lista usuarios [admin]
GET  /api/usuarios/{id}                 → Detalle
POST /api/usuarios                      → Crear usuario [admin]
PUT  /api/usuarios/{id}                 → Editar usuario [admin]
GET  /api/roles                         → Lista de roles disponibles
GET  /api/user/tokens                   → Tokens activos del usuario
POST /api/user/tokens                   → Crear nuevo token
DELETE /api/user/tokens/{id}            → Revocar token
```

### 6.24 Webhooks (header `X-Bot-Token = JWT_SHARED_SECRET`)

```
GET  /webhook/whatsapp/check-slug       → Verificar disponibilidad de slug
POST /webhook/whatsapp/onboarding       → Crear nuevo tenant desde WA
POST /webhook/whatsapp/pedido-remoto    → Crear pedido desde WA
POST /webhook/wa/config                 → Configurar bot desde WA
```

---

## 7. MODELOS — CAMPOS CLAVE

### Producto (`App\Models\Tenant\Producto`)

```php
// Tabla: productos
// CAMPOS REALES (post CRUD audit):
'codigo'          // SKU/código de barras — NO 'sku'
'nombre'
'tipo_producto'   // NO 'tipo' — enum: stock_fisico|servicio|renta|fraccionado|honorarios
'valor_venta'     // NO 'precio' — integer CLP
'costo'
'cantidad'        // NO 'stock' — decimal(10,3)
'cantidad_minima' // NO 'stock_minimo'
'estado'          // NO boolean 'activo' — enum: activo|inactivo|agotado
'fraccionable'    // boolean

// ACCESSORS DE COMPATIBILIDAD (se pueden usar como alias):
$producto->precio       → retorna valor_venta
$producto->stock        → retorna cantidad
$producto->stock_minimo → retorna cantidad_minima
$producto->tipo         → retorna tipo_producto
$producto->activo       → retorna estado === 'activo'

// SCOPE correcto:
Producto::where('estado', 'activo')   // NO where('activo', true)
Producto::activos()                    // scope equivalente

// BUSCAR:
Producto::buscar($termino)  // busca en nombre, codigo, codigo_referencia, marca

// NO existe: $producto->categoria, $producto->sku, with('categoria')
```

### Cliente (`App\Models\Tenant\Cliente`)

```php
// Tabla: clientes
'nombre', 'rut', 'email', 'telefono'
'giro'         // agregado migración 2026_03_18_014000
'direccion'    // agregado migración 2026_03_18_014000
'codigo_rapido' // entero auto-incremental por tenant (para búsqueda rápida)
'usuario_id'   // usuario que lo creó
```

### Usuario (`App\Models\Tenant\Usuario`)

```php
// Tabla: users (NO 'usuarios')
// Implementa Authenticatable + HasApiTokens
'nombre', 'email', 'whatsapp'
'clave_hash'   // campo de contraseña (getAuthPassword() retorna clave_hash)
'rol'          // string: admin|cajero|operario|bodega|super_admin|cliente|ejecutivo
'role_id'      // FK a tabla roles
'activo'       // boolean
```

### Tenant (`App\Models\Central\Tenant`)

```php
// Tabla: tenants (schema public, conexión 'central')
'id'            // string slug (ej: 'demo-legal')
'nombre'
'rut_empresa'
'estado'        // activo|suspendido|trial
'trial_hasta'   // datetime
'whatsapp_admin'
'plan_id'
'rubro_config'  // JSON (cast array)
'data'          // JSON (cast array) — datos extra stancl
```

### RubroConfig (`App\Models\Tenant\RubroConfig`)

```php
// Tabla: rubros_config (schema tenant)
// Este es el modelo que controla qué módulos están activos
'industria_preset'   // 'abarrotes', 'legal', etc.
'modulos_activos'    // JSON array: ['M01', 'M03', 'M18']
'tiene_stock_fisico' // boolean int
'tiene_renta'        // boolean int
'tiene_servicios'    // boolean int
```

---

## 8. SPIDER QA v3

### 8.1 Componentes

| Archivo | Ruta |
|---|---|
| Controller | `/app/app/Http/Controllers/SpiderController.php` |
| Vista UI | `/app/resources/views/central/spider.blade.php` |
| Tests JSON | `/app/tests/spider_tests.json` (181 tests) |
| Script sync | `/app/tests/sync_spider_tests.sh` |

### 8.2 Cómo funciona

```
1. Super admin loguea en /central/login → cookie de sesión
2. Abre /central/spider → UI del spider en blade
3. POST /central/spider/token → crea Bearer token del SuperAdmin
4. Spider usa ese token para:
   → /api/spider/* (ahora auth:sanctum ✅)
   → /api/central/plan/* (auth:sanctum)
5. Para cada tenant → POST /api/login del tenant → Bearer token del tenant
6. Usa token del tenant para todos los /api/* de ese tenant
```

### 8.3 Métodos del SpiderController

| Método | Ruta | Descripción |
|---|---|---|
| `index()` | `GET /central/spider` | Retorna view UI del spider |
| `probe()` | `GET /api/spider/probe?url=` | Proxy HTTP — testea accesibilidad de una URL |
| `dbCheck()` | `GET /api/spider/db-check` | Verifica super_admins, plan_modulos, tenants, bug_reports |
| `syncTests()` | `POST /api/spider/sync` | Ejecuta `sync_spider_tests.sh` → actualiza JSON |
| `getTests()` | `GET /api/spider/tests` | Lee `spider_tests.json` |
| `saveTests()` | `POST /api/spider/tests` | Guarda `spider_tests.json` |
| `generateToken()` | `POST /central/spider/token` | Crea Bearer token para SuperAdmin |

### 8.4 Estructura de spider_tests.json

```json
{
  "_meta": { "version": "3.0", "total_tests": 181 },
  "http_checks": [
    {
      "id": "H-1",
      "path": "/",
      "label": "Vista: /",
      "expected": "200|301|302",
      "url_key": "super"
    }
  ],
  "api_sa_checks": [...],
  "api_tenant_checks": [...],
  "auth_checks": [...],
  "db_checks": [...],
  "ui_checks": [...]
}
```

`url_key`: `"super"` = `localhost:8000`, `"tenant"` = `demo.localhost:8000`

---

## 9. TENANTS DEMO — DATOS DE PRUEBA

### 9.1 Credenciales de acceso

| Tenant | URL Login | Admin | Password |
|---|---|---|---|
| Legal | http://demo-legal.localhost:8000/auth/login/web | admin@demo-legal.cl | demo1234 |
| Pádel | http://demo-padel.localhost:8000/auth/login/web | admin@demo-padel.cl | demo1234 |
| Motel | http://demo-motel.localhost:8000/auth/login/web | admin@demo-motel.cl | demo1234 |
| Abarrotes | http://demo-abarrotes.localhost:8000/auth/login/web | admin@demo-abarrotes.cl | demo1234 |
| Ferretería ⭐ | http://demo-ferreteria.localhost:8000/auth/login/web | admin@demo-ferreteria.cl | demo1234 |
| Médico | http://demo-medico.localhost:8000/auth/login/web | admin@demo-medico.cl | demo1234 |
| SaaS | http://demo-saas.localhost:8000/auth/login/web | admin@demo-saas.cl | demo1234 |

⭐ `demo-ferreteria` es el más completo — tiene el mayor número de módulos activos.

**Central (superadmin):** http://localhost:8000/central

### 9.2 Datos demo sembrados por tenant

El `TenantDemoDataSeeder` crea en cada tenant:
- **1 usuario admin** con email `admin@demo-{rubro}.cl` y password `demo1234`
- **5 clientes demo** (cliente1@demo.cl ... cliente5@demo.cl)
- **3 productos demo** (DEMO-001, DEMO-002, DEMO-003) — solo en tenants con stock
- **1 proveedor demo** (rut: 76543210-K)
- **1 empleado demo** (rut: 19123456-7) — si existe tabla empleados
- **Config SII** mínima (ambiente: certificacion)
- **Config bot** mínima (inactiva)
- **RubroConfig** con módulos según la industria

### 9.3 /etc/hosts requerido para desarrollo local

```sh
sudo bash -c 'cat >> /etc/hosts << EOF
127.0.0.1 demo.localhost
127.0.0.1 demo-legal.localhost
127.0.0.1 demo-padel.localhost
127.0.0.1 demo-motel.localhost
127.0.0.1 demo-abarrotes.localhost
127.0.0.1 demo-ferreteria.localhost
127.0.0.1 demo-medico.localhost
127.0.0.1 demo-saas.localhost
EOF'
```

---

## 10. MIDDLEWARE — COMPORTAMIENTO EXACTO

| Middleware | Cuándo aplica | Retorna |
|---|---|---|
| `CheckTenantStatus` | Todas las rutas tenant | 403 si `tenant.estado === 'suspendido'` |
| `CheckModuleAccess` | Rutas dentro de `auth:sanctum + module` | 403 si módulo inactivo en RubroConfig; 402 si sin Subscription |
| `CheckRole` | Rutas con `:admin,cajero` etc. | 403 si rol insuficiente |
| `InternalBotAuth` (alias: `auth.bot`) | `/api/internal/*` | 401 si `X-Bot-Token` ≠ `JWT_SHARED_SECRET` |
| `JwtBridgeMiddleware` (alias: `jwt.bridge`) | `/api/bot/*` | 401 si JWT inválido |
| `LogUnauthorizedRequests` | Global (append) | Log de todos los 4xx/5xx |

---

## 11. BUGS CONOCIDOS Y ESTADO ACTUAL

### Resueltos (2026-03-18)

| Bug | Fix |
|---|---|
| `GET /api/v1/public/productos` → 500 | Removido `.with('categoria')`, `where('estado','activo')`, campos `codigo`/`cantidad`/`valor_venta` en PublicApiController |
| Rutas spider sin auth (`/api/spider/*` → 200 sin token) | Guard cambiado `auth:super_admin` → `auth:sanctum` en routes/api.php |
| Seeder ferretería con módulo `'SmallBusiness'` inválido | Cambiado a `'M26'` |
| 8 tests con rutas/expected incorrectos en spider_tests.json | H-1, H-21, H-23, H-24, H-44→H-47 corregidos |
| Campos `giro`/`direccion` faltaban en clientes | Migración `2026_03_18_014000` ejecutada en todos los tenants |

### Pendientes

| Bug | Descripción | Prioridad |
|---|---|---|
| SP-083 | Spider usa token de SuperAdmin en requests de tenant → 401 en `/api/user` | Alto — fix en la lógica del spider |
| Sin cobertura M13/M16 | Delivery y Recetas no tienen tenant activo | Medio — agregar módulo a algún tenant |
| Flujo de venta sin testear | `POST /api/ventas` → items → confirmar no está en spider_tests.json | Medio |
| Vistas admin testeadas sin sesión | El spider prueba `/admin/*` sin cookie → siempre 302 (pasa), sin verificar contenido | Bajo |

---

## 12. COMANDOS ÚTILES

```sh
# Acceder al container
docker exec benderandos_app sh -c "cd /app && <comando>"
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"

# Ver logs del servidor
docker exec benderandos_app sh -c "tail -100 /app/storage/logs/laravel.log"

# Rutas registradas
docker exec benderandos_app sh -c "cd /app && php artisan route:list"
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/spider"

# Migraciones
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate"
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate --tenants=demo-ferreteria"

# Seeders
docker exec benderandos_app sh -c "cd /app && php artisan db:seed --class=DemoTenantsSeeder"

# Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"
docker exec benderandos_app sh -c "cd /app && php artisan route:cache && php artisan view:clear"

# Ver módulos activos de un tenant
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"
# >>> App\Models\Tenant\RubroConfig::first()->modulos_activos

# Leer un controller específico
docker exec benderandos_app sh -c "cat /app/app/Http/Controllers/Tenant/Api/PublicApiController.php"

# Recolectar contexto para antigraviity
sh collect_context.sh   # desde el host, genera /tmp/benderand_context_*.md
```

---

## 13. RECOMENDACIONES PARA EL SPIDER — PRÓXIMO RUN

**Tenant correcto por módulo:**

| Módulo | Usar tenant | Endpoint a testear |
|---|---|---|
| M08 Agenda | demo-legal, demo-padel, demo-medico | `/api/agenda/*` |
| M09/M10 Honorarios/Notas | demo-legal, demo-medico | `/api/honorarios/*` |
| M11 Fiado | demo-abarrotes, demo-ferreteria | `/api/deudas/*` |
| M18 Compras | demo-abarrotes, demo-ferreteria | `/api/compras/*` |
| M21 RRHH | demo-legal, demo-medico, demo-saas | `/api/rrhh/*` |
| M23 Reclutamiento | demo-saas | `/api/reclutamiento/*` |
| M31 SaaS | demo-saas | `/api/saas/*` |
| General (todo) | **demo-ferreteria** ← recomendado | — |

**Expectativas correctas para el próximo run:**
- HTTP 0 → **0** (si `/etc/hosts` está configurado)
- Spider sin token → **0** (ahora `auth:sanctum`)
- Módulos inactivos (403) → **~23** (comportamiento correcto)
- Sin suscripción (402) → **4** (tenant `demo` genérico sin Subscription)
- Tests incorrectos → **0** (corregidos en JSON)
- Crashes 500 → **0** (PublicApiController corregido)
- **Total bugs reales esperados: < 5**

---

*BenderAnd ERP · Knowledge Master · 2026-03-18*
*Container: benderandos_app · php:8.4-cli-alpine · /app · postgres:16*
*238 rutas · 7 tenants demo · Spider QA v3 · M01–M32*
