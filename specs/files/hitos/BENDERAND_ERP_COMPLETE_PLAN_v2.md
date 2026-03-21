# BENDERAND ERP — PLAN DE DESARROLLO COMPLETO v2.0
**Plataforma SaaS Multi-Tenant · Multi-Rubro · Integración ERP + WhatsApp + SII**
*Versión 2.0 · Marzo 2026 · Confidencial*

---

## DOCUMENTOS DE ESTE PLAN

| Archivo | Contenido |
|---|---|
| `BENDERAND_ERP_COMPLETE_PLAN_v2.md` | **Este documento** — Arquitectura, módulos, DB, API, hitos, Docker |
| `BENDERAND_CONFIG_INDUSTRIAS.md` | **Sistema de configuración** — Módulos atómicos, presets por industria, onboarding WhatsApp, UI dinámica |

---

## PRINCIPIOS DE DISEÑO DEL SISTEMA

1. **Configuración = comportamiento.** Nada está hardcodeado por industria. Todo el comportamiento del sistema (menús, pantallas, etiquetas, flujos, alertas) se deriva de `rubros_config`. Ver `BENDERAND_CONFIG_INDUSTRIAS.md`.

2. **Módulos atómicos.** Cada funcionalidad es un módulo independiente (`M01`–`M30`) que puede activarse o desactivarse por empresa. Las industrias son presets de módulos.

3. **Dos contextos separados.** Cada módulo tiene acciones de **admin** (gestión, reportes, configuración) y acciones de **POS** (operación en caja, agenda, recursos). Un admin NO usa el POS. Un cajero NO ve el panel admin.

4. **Configuración vía WhatsApp.** El onboarding y la reconfiguración se ejecutan íntegramente por el bot. Sin dashboards de setup. Sin intervención del equipo BenderAnd.

5. **Industrias híbridas.** Una empresa puede combinar módulos de dos rubros (ej: Clínica + Farmacia). El sistema soporta esto sin duplicar código.

---

## RESUMEN EJECUTIVO

BenderAnd ERP es una plataforma SaaS unificada que integra tres sistemas en uno solo:

1. **ERP Comercial (BenderAnd POS)**: Sistema de punto de venta multi-tenant para retail, servicios, salud, legal y más rubros. Stack: PHP 8.2 + Laravel 11 + PostgreSQL 11.4. Mobile-first, dark UI con IBM Plex.
2. **WhatsApp Bot Agentic (Moteland)**: Plataforma B2B de bots IA para WhatsApp con handover humano. Stack: Node.js + Express + PostgreSQL + Redis + BullMQ + Socket.io + React. MVP en producción.
3. **Módulo SII / LibreDTE**: Facturación electrónica chilena integrada directamente en el flujo de ventas.

**Nuevos módulos en v2.0:**
- Compras y gestión de proveedores (globales + por tenant)
- Delivery y logística con tracking
- Restaurante con recetas e ingredientes
- RRHH completo (asistencia, vacaciones, permisos, liquidaciones)
- Reclutamiento y ofertas de empleo
- Marketing con QR dinámico

**Estado actual:** Hito 1–8 completados (POS base, roles, super admin, UI separada por rol). Este documento cubre la hoja de ruta completa desde Hito 9 en adelante y la arquitectura unificada.

---

## PARTE 1: ARQUITECTURA UNIFICADA

### 1.1 Stack Tecnológico Integrado

```
┌───────────────────────────────────────────────────────────────────┐
│                      BENDERAND ERP PLATFORM                        │
├───────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌──────────────────────────┐    ┌────────────────────────────┐  │
│  │   ERP CORE (Laravel 11 / PHP)   │    │  WHATSAPP BOT (Node.js)    │  │
│  │   PostgreSQL (schema/tenant)│◄──►│  PostgreSQL + Redis        │  │
│  │   SSE tiempo real        │    │  BullMQ + Socket.io        │  │
│  └──────────────────────────┘    └────────────────────────────┘  │
│            │                               │                       │
│            ▼                               ▼                       │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │              MÓDULO SII / LIBREDTE (PHP/Laravel 11)              │    │
│  │   Boletas · Facturas · Honorarios · NC/ND · Libro IVA     │    │
│  └──────────────────────────────────────────────────────────┘    │
│            │                               │                       │
│            └───────────────┬───────────────┘                       │
│                            ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │              NGINX — API GATEWAY UNIFICADO                │    │
│  │   JWT compartido · Rate limiting · SSL/TLS                │    │
│  └──────────────────────────────────────────────────────────┘    │
│                            │                                       │
│            ┌───────────────┼───────────────┐                       │
│            ▼               ▼               ▼                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ Redis Caché │  │    Redis    │  │  BullMQ     │             │
│  │ Sesiones    │  │  Colas WA   │  │  Jobs       │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │         DASHBOARD UNIFICADO (Vanilla JS / React)          │    │
│  │   POS · Admin · WhatsApp · SII · RRHH · Delivery · QR    │    │
│  └──────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────────┘
```

### 1.2 Stack Tecnológico por Capa

| Componente | Tecnología | Justificación |
|---|---|---|
| Backend ERP | PHP 8.2 + **Laravel 11** | Eloquent ORM, Jobs, Sanctum, Gates/Policies |
| Multi-tenancy | **stancl/tenancy v3** | Schema por tenant, scoping Eloquent automático |
| Base ERP | **PostgreSQL 16** (schema por tenant) | Soporte nativo de schemas, mismo servidor que WA |
| Base WhatsApp | PostgreSQL 16 + Prisma ORM | Ya en producción con Moteland |
| Bot IA | GPT-4o-mini vía OpenAI | Bajo costo, alta velocidad |
| Colas ERP | **Laravel Horizon + Redis** | Jobs async: DTE, emails, notificaciones WA |
| Colas WA | Redis + BullMQ | Mensajes WA sin pérdida bajo carga |
| Tiempo real ERP | **Laravel Broadcasting + Reverb** | WebSocket nativo Laravel 11 |
| Tiempo real WA | Socket.io + JWT | Dashboard handover en vivo |
| Frontend | Vanilla JS + HTML/CSS | Mobile-first, sin bundle pesado |
| Dashboard WA | React (Vite) | SPA con eventos en tiempo real |
| Proxy | Nginx | Rutas unificadas, SSL, WebSocket |
| Auth ERP | **Laravel Sanctum** (API tokens) | Token compartido con Node.js vía JWT |
| Auth compartida | JWT secret común Laravel + Node | Token válido en ambos sistemas |
| Cache | **Redis 7** (Laravel Cache driver) | Sesiones, caché queries, rate limiting |
| SII | **LibreDTE PHP** en Laravel Service | Llamado desde Jobs y Controllers |
| ORM | **Eloquent** con tenant schema scoping | Modelos aislados por schema automático |

> **Nota DB unificada:** Se usa **PostgreSQL 16** para ERP y WhatsApp bot. stancl/tenancy v3 crea un schema separado (`tenant_{uuid}`) por empresa dentro del mismo servidor PostgreSQL. Esto elimina la dependencia de PostgreSQL, unifica infraestructura y simplifica backups.

### 1.3 Modelo Multi-Tenant (stancl/tenancy v3)

```
PostgreSQL 16 — servidor único
│
├── schema: public (base central Laravel)
│   ├── tenants               ← registro de empresas (modelo Tenant)
│   ├── planes                ← Básico / Pro / Enterprise
│   ├── billing               ← cobros, facturas a clientes SaaS
│   ├── proveedores_globales  ← Coca-Cola, Nestlé, CCU (compartidos)
│   ├── super_admin_users     ← admins de la plataforma
│   └── domains               ← subdominios por tenant (stancl/tenancy)
│
├── schema: tenant_{uuid_empresa_1}
│   ├── users                 ← admin, cajero, operario, cliente
│   ├── productos             ← stock, renta, servicio, fraccionado
│   ├── ventas / items_venta
│   ├── compras / items_compra
│   ├── clientes
│   ├── proveedores_tenant    ← referencia a globales + condiciones propias
│   ├── ordenes_compra / items_orden_compra
│   ├── recepciones_compra / items_recepcion
│   ├── empleados / asistencias / vacaciones / permisos / liquidaciones
│   ├── recetas / ingredientes_receta / producciones
│   ├── repartidores / entregas / tracking_entregas
│   ├── ofertas_empleo / postulaciones / entrevistas
│   └── qr_campanas / escaneos_qr
│
├── schema: tenant_{uuid_empresa_2}   ← completamente aislado
│   └── ... (mismo set de tablas)
│
└── schema: moteland (WhatsApp Bot — Prisma)
    ├── tenants (Bot)
    ├── conversations
    ├── messages
    └── agents
```

**Configuración stancl/tenancy v3 (Laravel):**
```php
// config/tenancy.php
'tenant_model' => App\Models\Central\Tenant::class,
'bootstrappers' => [
    Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    // Cambia el search_path de PostgreSQL al schema del tenant
],
'database' => [
    'template_tenant_database' => null, // usamos schema, no DB separada
    'suffix' => '',
    'managers' => [
        'pgsql' => Stancl\Tenancy\Database\Managers\PostgreSQLSchemaManager::class,
    ],
],
```

**Acceso automático por subdominio:**
```php
// routes/tenant.php — aplica middleware tenancy
Route::middleware(['tenancy.initialize', 'auth:sanctum', 'role'])->group(function () {
    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('ventas', VentaController::class);
    // ... todos los recursos del tenant
});
```

### 1.4 Comunicación entre Servicios

```
ERP (Laravel 11) ←──Sanctum Token / JWT──► Node.js (WhatsApp Bot)
     │                                           │
     │  POST /internal/erp/evento (webhook)       │
     │◄──────────────────────────────────────────┘
     │
     │  Evento: venta_confirmada → Laravel dispatches NotificarClienteWA Job
     │  Evento: pedido_whatsapp  → Laravel crea Venta estado=remota_pendiente
     │  Evento: cita_confirmada  → Bot envía recordatorio automático
     │
     └──► Redis Pub/Sub (canal compartido cross-system)
              └─► Laravel Horizon procesa Jobs async
              └─► BullMQ (Node.js) procesa mensajes WA
```

**Autenticación compartida Laravel ↔ Node.js:**
```php
// app/Services/JwtBridgeService.php (Laravel)
class JwtBridgeService
{
    public function generateSharedToken(Tenant $tenant): string
    {
        return JWT::encode([
            'tenant_id' => $tenant->id,
            'tenant_uuid' => $tenant->uuid,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(8)->timestamp,
        ], config('app.jwt_shared_secret'), 'HS256');
    }

    public function validateSharedToken(string $token): array
    {
        return (array) JWT::decode($token, new Key(
            config('app.jwt_shared_secret'), 'HS256'
        ));
    }
}
```

```javascript
// src/middleware/erp-auth.js (Node.js)
import jwt from 'jsonwebtoken';
export const erpAuth = (req, res, next) => {
    const token = req.headers.authorization?.split(' ')[1];
    const payload = jwt.verify(token, process.env.JWT_SHARED_SECRET);
    req.tenant = payload; // mismo secret que Laravel
    next();
};
```

---

## PARTE 2: MÓDULO DE COMPRAS Y PROVEEDORES

### 2.1 Arquitectura de Compras

```
┌──────────────────────────────────────────────────────────────┐
│             SISTEMA DE COMPRAS MULTI-TENANT                   │
├──────────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐    ┌──────────────────────────────┐ │
│  │ PROVEEDORES GLOBALES │    │  PROVEEDORES POR TENANT      │ │
│  │ (benderand_master)   │    │  (schema tenant)             │ │
│  ├─────────────────────┤    ├──────────────────────────────┤ │
│  │ • Coca-Cola          │    │ • Distribuidora local        │ │
│  │ • Nestlé / Soprole   │    │ • Mayorista regional         │ │
│  │ • CCU / Watts        │    │ • Productor artesanal        │ │
│  └─────────────────────┘    └──────────────────────────────┘ │
│               │                        │                      │
│               └────────────┬───────────┘                      │
│                            ▼                                  │
│        ┌──────────────────────────────────────┐              │
│        │   CONTRATOS Y CONDICIONES POR TENANT  │              │
│        │   Precios negociados · Descuentos vol.│              │
│        │   Plazos pago · Mínimos de compra     │              │
│        └──────────────────────────────────────┘              │
│                            │                                  │
│            ┌───────────────┼───────────────┐                  │
│            ▼               ▼               ▼                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ OC AUTOMÁTICA│  │  OC MANUAL   │  │ OC PROGRAMADA│       │
│  │ stock mínimo │  │  por admin   │  │  semanal     │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│                            │                                  │
│                            ▼                                  │
│        ┌──────────────────────────────────────┐              │
│        │   RECEPCIÓN Y CONTROL DE CALIDAD      │              │
│        │   Validación OC · Lotes · Vencimiento │              │
│        │   Ajuste inventario · Nota de crédito │              │
│        └──────────────────────────────────────┘              │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Modelo de Datos de Compras

#### Tabla: proveedores_globales (benderand_master)
```sql
CREATE TABLE proveedores_globales (
    id            BIGSERIAL PRIMARY KEY,
    rut           VARCHAR(20) UNIQUE NOT NULL,
    nombre        VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255),
    rubro         VARCHAR(100),          -- 'abarrotes','construccion','limpieza'
    direccion     TEXT,
    telefono      VARCHAR(20),
    email         VARCHAR(255),
    website       VARCHAR(255),
    contacto_nombre   VARCHAR(255),
    contacto_telefono VARCHAR(20),
    contacto_email    VARCHAR(255),
    condiciones_generales JSON,          -- {plazo_dias:30, forma_pago:'transferencia'}
    certificaciones   JSON,              -- ['ISO9001','HACCP']
    activo        BOOLEAN DEFAULT TRUE,
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    updated_at    TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: proveedores_tenant (schema tenant)
```sql
CREATE TABLE proveedores_tenant (
    id                   BIGSERIAL PRIMARY KEY,
    proveedor_global_id  BIGINT,         -- NULL = proveedor solo local
    codigo_interno       VARCHAR(50),
    nombre_referencia    VARCHAR(255) NOT NULL,
    condiciones_negociadas JSON,         -- {descuento_pct:5, precio_especial:{prod_id:1190}}
    plazo_pago_dias      INT DEFAULT 0,
    minimo_compra        BIGINT DEFAULT 0,  -- en centavos
    forma_pago_preferida VARCHAR(50),    -- 'credito','contado','cheque'
    compras_anuales      BIGINT DEFAULT 0,
    rating               SMALLINT CHECK (rating BETWEEN 1 AND 5),
    notas                TEXT,
    activo               BOOLEAN DEFAULT TRUE,
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: productos_proveedor (schema tenant)
```sql
CREATE TABLE productos_proveedor (
    id                    BIGSERIAL PRIMARY KEY,
    proveedor_tenant_id   BIGINT NOT NULL,
    producto_id           BIGINT NOT NULL,  -- FK productos
    codigo_proveedor      VARCHAR(100),
    nombre_proveedor      VARCHAR(500),
    precio_compra         BIGINT NOT NULL,  -- centavos CLP
    precio_compra_anterior BIGINT,
    fecha_ultimo_cambio   TIMESTAMPTZ,
    descuentos_volumen    JSON,             -- {"100":5,"500":10,"1000":15}
    tiempo_entrega_dias   INT DEFAULT 1,
    presentacion          VARCHAR(100),     -- 'caja x 12 unidades'
    unidades_por_presentacion INT DEFAULT 1,
    es_preferente         BOOLEAN DEFAULT FALSE,
    activo                BOOLEAN DEFAULT TRUE,
    created_at            TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: ordenes_compra (schema tenant)
```sql
CREATE TABLE ordenes_compra (
    id                  BIGSERIAL PRIMARY KEY,
    numero_oc           VARCHAR(50) UNIQUE NOT NULL,  -- OC-2026-0001
    proveedor_tenant_id BIGINT NOT NULL,
    fecha_emision       DATE NOT NULL,
    fecha_requerida     DATE,
    fecha_entrega       DATE,
    estado              ENUM('borrador','pendiente','enviada','confirmada',
                             'parcial','completa','anulada') DEFAULT 'borrador',
    tipo                ENUM('automatica','manual','programada') DEFAULT 'manual',
    subtotal            BIGINT DEFAULT 0,
    descuento           BIGINT DEFAULT 0,
    impuestos           BIGINT DEFAULT 0,
    total               BIGINT DEFAULT 0,
    condiciones_pago    TEXT,
    observaciones       TEXT,
    creado_por          BIGINT,           -- FK usuarios
    autorizado_por      BIGINT,
    fecha_autorizacion  TIMESTAMPTZ,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: items_orden_compra (schema tenant)
```sql
CREATE TABLE items_orden_compra (
    id               BIGSERIAL PRIMARY KEY,
    orden_compra_id  BIGINT NOT NULL,
    producto_id      BIGINT NOT NULL,
    cantidad         DECIMAL(12,3) NOT NULL,
    cantidad_recibida DECIMAL(12,3) DEFAULT 0,
    unidad_medida    VARCHAR(20) DEFAULT 'unidad',
    precio_unitario  BIGINT NOT NULL,
    descuento_item   BIGINT DEFAULT 0,
    total_item       BIGINT NOT NULL,
    estado           ENUM('pendiente','parcial','completo') DEFAULT 'pendiente',
    created_at       TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: recepciones_compra (schema tenant)
```sql
CREATE TABLE recepciones_compra (
    id               BIGSERIAL PRIMARY KEY,
    orden_compra_id  BIGINT NOT NULL,
    numero_recepcion VARCHAR(50) UNIQUE,
    fecha_recepcion  DATE NOT NULL,
    guia_despacho    VARCHAR(100),
    factura_proveedor VARCHAR(100),
    recibido_por     BIGINT NOT NULL,    -- FK usuarios
    observaciones    TEXT,
    created_at       TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: items_recepcion (schema tenant)
```sql
CREATE TABLE items_recepcion (
    id                  BIGSERIAL PRIMARY KEY,
    recepcion_id        BIGINT NOT NULL,
    item_oc_id          BIGINT NOT NULL,
    cantidad_recibida   DECIMAL(12,3) NOT NULL,
    cantidad_aceptada   DECIMAL(12,3) NOT NULL,
    cantidad_rechazada  DECIMAL(12,3) DEFAULT 0,
    motivo_rechazo      TEXT,
    lote                VARCHAR(100),
    fecha_vencimiento   DATE,
    ubicacion_destino   VARCHAR(50),
    created_at          TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.3 API Endpoints — Compras

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/proveedores | Lista proveedores del tenant | admin |
| GET | /api/proveedores/globales | Catálogo de proveedores globales | admin |
| POST | /api/proveedores | Crear proveedor local o vincular global | admin |
| PUT | /api/proveedores/{id} | Editar condiciones negociadas | admin |
| GET | /api/proveedores/{id}/productos | Catálogo de productos del proveedor | admin |
| POST | /api/ordenes-compra | Crear orden de compra | admin |
| GET | /api/ordenes-compra | Listar OC con filtros | admin,bodega |
| GET | /api/ordenes-compra/{id} | Detalle OC | admin,bodega |
| PUT | /api/ordenes-compra/{id}/estado | Cambiar estado (enviada/anulada) | admin |
| POST | /api/ordenes-compra/{id}/recepciones | Registrar recepción de mercancía | bodega |
| GET | /api/compras/sugerencias | OC sugeridas por stock mínimo | admin |
| GET | /api/compras/reportes/mensual | Resumen compras del mes | admin |

### 2.4 UI — Panel de Compras

#### Dashboard de Compras
```
┌──────────────────────────────────────────────────────────┐
│  📦 COMPRAS Y PROVEEDORES              [Marzo 2026 ▼]    │
├──────────────────────────────────────────────────────────┤
│  ┌─────────────┬─────────────┬─────────────┬───────────┐ │
│  │ Total mes   │ OC activas  │ Proveedores │ Ahorro    │ │
│  │ $12.345.678 │ 48          │ 24          │ $234.567  │ │
│  └─────────────┴─────────────┴─────────────┴───────────┘ │
│                                                          │
│  ⚠️ ALERTAS DE STOCK                                     │
│  🔴 12 productos bajo stock mínimo                       │
│  🟡 8 productos por vencer (próx. 30 días)               │
│  🟢 3 órdenes de compra por recibir hoy                  │
│                                                          │
│  📋 ÓRDENES RECIENTES                                    │
│  OC-1042 · Coca-Cola · $234.500 · 📦 Recibida · [Ver]   │
│  OC-1043 · CCU · $567.800 · 🚚 En tránsito · [Ver]      │
│  OC-1044 · Nestlé · $123.400 · ⏳ Pendiente · [Ver]     │
│                                                          │
│  [➕ NUEVA OC]  [📊 REPORTES]  [📦 RECEPCIONES HOY]      │
└──────────────────────────────────────────────────────────┘
```

#### Recepción de Mercancía
```
┌──────────────────────────────────────────────────────────┐
│  📦 RECEPCIÓN                              OC-2026-1042  │
├──────────────────────────────────────────────────────────┤
│  PROVEEDOR: Coca-Cola de Chile                           │
│  GUÍA: [123456]   FACTURA: [F-98765]                     │
│                                                          │
│  Producto         Solicitado  Recibido  Aceptado  Estado │
│  Coca-Cola 1.5L      24          24        24      ✅    │
│  Coca-Cola Zero      12          10        10      ⚠️    │
│    → Faltan 2 (pendiente de confirmación)                │
│  Fanta 1.5L          12          12        8       ❌    │
│    → 4 unidades dañadas — se rechazan                    │
│                                                          │
│  LOTE: L240315-01 · Vence: 15/12/2026 · ✅ OK            │
│  UBICACIÓN EN BODEGA: [A-12]                             │
│                                                          │
│  [✅ CONFIRMAR RECEPCIÓN]  [⚠️ GENERAR NOTA DE CRÉDITO]  │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 3: MÓDULO DE DELIVERY Y LOGÍSTICA

### 3.1 Arquitectura de Delivery

```
┌──────────────────────────────────────────────────────────┐
│                    SISTEMA DE DELIVERY                    │
├──────────────────────────────────────────────────────────┤
│  ┌───────────────────┐    ┌──────────────────────────┐  │
│  │  ÓRDENES ENTREGA  │    │     REPARTIDORES          │  │
│  │  Pendientes       │    │  Propios / Freelance      │  │
│  │  En ruta          │    │  Disponibilidad horaria   │  │
│  │  Entregadas       │    │  Zona de cobertura        │  │
│  └───────────────────┘    └──────────────────────────┘  │
│           │                           │                   │
│           └────────────┬──────────────┘                   │
│                        ▼                                   │
│  ┌──────────────────────────────────────────────────┐    │
│  │          ASIGNACIÓN Y OPTIMIZACIÓN DE RUTAS       │    │
│  │   Geolocalización · Ventanas horarias             │    │
│  │   Capacidad · Prioridades (VIP, urgente)          │    │
│  └──────────────────────────────────────────────────┘    │
│                        │                                   │
│                        ▼                                   │
│  ┌──────────────────────────────────────────────────┐    │
│  │          TRACKING EN TIEMPO REAL                  │    │
│  │   SSE/WebSocket · Notificación WA al cliente      │    │
│  │   ETA · Confirmación de entrega · Firma digital   │    │
│  └──────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

### 3.2 Modelo de Datos de Delivery

#### Tabla: repartidores (schema tenant)
```sql
CREATE TABLE repartidores (
    id                 BIGSERIAL PRIMARY KEY,
    usuario_id         BIGINT,           -- FK usuarios (si es interno)
    nombre             VARCHAR(255) NOT NULL,
    telefono           VARCHAR(20),
    tipo               ENUM('interno','freelance','plataforma') DEFAULT 'interno',
    vehiculo_tipo      VARCHAR(50),      -- 'bicicleta','moto','auto','camion'
    vehiculo_patente   VARCHAR(10),
    licencia_valida    BOOLEAN DEFAULT TRUE,
    disponible         BOOLEAN DEFAULT TRUE,
    horario_trabajo    JSON,             -- {"lunes":["09:00-18:00"]}
    zona_cobertura     JSON,             -- polígono GeoJSON o lista de comunas
    calificacion_promedio DECIMAL(3,2) DEFAULT 5.0,
    total_entregas     INT DEFAULT 0,
    activo             BOOLEAN DEFAULT TRUE,
    created_at         TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: entregas (schema tenant)
```sql
CREATE TABLE entregas (
    id                  BIGSERIAL PRIMARY KEY,
    uuid                VARCHAR(36) UNIQUE NOT NULL,
    venta_id            BIGINT NOT NULL,        -- FK ventas
    repartidor_id       BIGINT,                  -- FK repartidores (NULL = sin asignar)
    estado              ENUM('pendiente','asignada','en_ruta',
                             'entregada','fallida','cancelada') DEFAULT 'pendiente',
    tipo                ENUM('inmediata','programada') DEFAULT 'inmediata',
    fecha_programada    DATE,
    hora_inicio         TIME,
    hora_fin            TIME,
    direccion_entrega   TEXT NOT NULL,
    latitud             DECIMAL(10,8),
    longitud            DECIMAL(11,8),
    instrucciones       TEXT,
    contacto_nombre     VARCHAR(255),
    contacto_telefono   VARCHAR(20),
    distancia_km        DECIMAL(8,2),
    tiempo_estimado_min INT,
    costo_envio         BIGINT DEFAULT 0,
    confirmado_en       TIMESTAMPTZ,               -- timestamp de entrega
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: tracking_entregas (schema tenant)
```sql
CREATE TABLE tracking_entregas (
    id           BIGSERIAL PRIMARY KEY,
    entrega_id   BIGINT NOT NULL,
    estado       VARCHAR(50),
    latitud      DECIMAL(10,8),
    longitud     DECIMAL(11,8),
    timestamp    TIMESTAMPTZ DEFAULT NOW(),
    observacion  TEXT
);
```

### 3.3 API Endpoints — Delivery

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/entregas | Lista entregas con filtros de estado/fecha | admin |
| POST | /api/entregas | Crear entrega (al confirmar venta con delivery) | cajero |
| PUT | /api/entregas/{id}/asignar | Asignar repartidor | admin |
| PUT | /api/entregas/{id}/estado | Actualizar estado (en_ruta, entregada) | repartidor |
| POST | /api/entregas/{id}/tracking | Registrar posición GPS | repartidor |
| GET | /api/entregas/{uuid}/publico | Vista pública de seguimiento (cliente) | público |
| GET | /api/repartidores | Lista repartidores activos | admin |
| POST | /api/repartidores | Crear repartidor | admin |
| GET | /api/delivery/dashboard | Métricas en vivo (en ruta, pendientes) | admin |

### 3.4 UI — Panel de Delivery

#### Dashboard en Vivo
```
┌──────────────────────────────────────────────────────────┐
│  🚚 DELIVERY EN VIVO                    [Hoy 15:23]      │
├──────────────────────────────────────────────────────────┤
│  ┌───────────┬───────────┬───────────┬─────────────────┐ │
│  │ Pendientes│ En ruta   │Completadas│ Repartidores    │ │
│  │ 8         │ 5         │ 23        │ 4/6 activos     │ │
│  └───────────┴───────────┴───────────┴─────────────────┘ │
│                                                          │
│  📋 PRÓXIMAS ENTREGAS                                    │
│  15:30 · María González · Av. Siempre Viva 123 · ⏳ Asig │
│  15:45 · Juan Pérez · Calle 2 #456 · 🚚 En ruta          │
│  16:00 · Constructora · Los Industriales 789 · ⏳ Pend    │
│                                                          │
│  🏍️ JUAN (Moto) → 3 entregas · ETA: 23 min               │
│  🚲 MARÍA (Bici) → 2 entregas · ETA: 15 min              │
│  🚗 PEDRO (Auto) → 5 entregas · ETA: 45 min              │
│                                                          │
│  [➕ ASIGNAR MANUAL]  [📊 REPORTES]  [🚚 FLOTA]           │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 4: MÓDULO DE RESTAURANTE — RECETAS E INGREDIENTES

### 4.1 Modelo de Datos de Recetas

#### Tabla: recetas (schema tenant)
```sql
CREATE TABLE recetas (
    id                   BIGSERIAL PRIMARY KEY,
    codigo               VARCHAR(50) UNIQUE,
    nombre               VARCHAR(255) NOT NULL,
    descripcion          TEXT,
    categoria            VARCHAR(100),   -- 'entrada','fondo','postre','bebida'
    tiempo_preparacion_min INT DEFAULT 0,
    tiempo_coccion_min   INT DEFAULT 0,
    dificultad           ENUM('baja','media','alta') DEFAULT 'media',
    porciones            INT DEFAULT 1,
    instrucciones        TEXT,
    imagen_url           VARCHAR(500),
    costo_ingredientes   BIGINT DEFAULT 0,   -- calculado automático
    costo_mano_obra      BIGINT DEFAULT 0,
    costo_total          BIGINT DEFAULT 0,   -- calculado
    precio_venta         BIGINT NOT NULL,
    margen               DECIMAL(5,2),       -- calculado
    activo               BOOLEAN DEFAULT TRUE,
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: ingredientes_receta (schema tenant)
```sql
CREATE TABLE ingredientes_receta (
    id           BIGSERIAL PRIMARY KEY,
    receta_id    BIGINT NOT NULL,       -- FK recetas
    producto_id  BIGINT NOT NULL,       -- FK productos (el ingrediente)
    cantidad     DECIMAL(12,3) NOT NULL,
    unidad_medida VARCHAR(20) DEFAULT 'kg',
    merma_pct    DECIMAL(5,2) DEFAULT 0, -- % pérdida en preparación
    opcional     BOOLEAN DEFAULT FALSE,
    orden        INT DEFAULT 0,
    created_at   TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: producciones (schema tenant)
```sql
CREATE TABLE producciones (
    id                     BIGSERIAL PRIMARY KEY,
    receta_id              BIGINT NOT NULL,
    fecha_produccion       DATE NOT NULL,
    cantidad_porciones     INT NOT NULL,
    lote                   VARCHAR(100),
    costo_total_produccion BIGINT DEFAULT 0,  -- calculado al producir
    responsable_id         BIGINT NOT NULL,    -- FK usuarios
    observaciones          TEXT,
    estado                 ENUM('programada','en_proceso','completada') DEFAULT 'programada',
    created_at             TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: items_produccion (schema tenant)
```sql
CREATE TABLE items_produccion (
    id                BIGSERIAL PRIMARY KEY,
    produccion_id     BIGINT NOT NULL,
    producto_id       BIGINT NOT NULL,      -- ingrediente descontado
    cantidad_usada    DECIMAL(12,3) NOT NULL,
    costo_unitario    BIGINT NOT NULL,
    costo_total       BIGINT NOT NULL,
    created_at        TIMESTAMPTZ DEFAULT NOW()
);
```

### 4.2 API Endpoints — Recetas

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/recetas | Lista recetas activas | operario |
| POST | /api/recetas | Crear receta | admin |
| PUT | /api/recetas/{id} | Editar receta | admin |
| GET | /api/recetas/{id}/costo | Calcular costo actual según precios | admin |
| GET | /api/recetas/{id}/stock | Verificar stock de ingredientes | operario |
| POST | /api/producciones | Registrar producción (descuenta stock) | operario |
| GET | /api/producciones | Historial de producciones | admin |
| GET | /api/recetas/alertas | Recetas sin stock suficiente | admin |

### 4.3 UI — Editor de Recetas

```
┌──────────────────────────────────────────────────────────┐
│  📝 RECETA: LOMO A LO POBRE                              │
├──────────────────────────────────────────────────────────┤
│  Categoría: [Plato de fondo ▼]                           │
│  Prep: 20 min   Cocción: 25 min   Porciones: 4           │
│                                                          │
│  INGREDIENTES                                            │
│  Producto          Cantidad  Unidad  Merma %   Costo    │
│  Posta rosada      1.2       kg      5%        $8.400   │
│  Papas             1.5       kg      10%       $1.350   │
│  Cebollas          0.5       kg      5%        $450     │
│  Huevos            4         uni     0%        $800     │
│  Aceite            0.1       L       0%        $150     │
│  [➕ AGREGAR INGREDIENTE]                                │
│                                                          │
│  COSTOS Y MARGEN                                         │
│  Costo ingredientes:          $11.210                    │
│  Mano de obra estimada:        $2.000                    │
│  Otros costos:                  $300                     │
│  ─────────────────────────────────                       │
│  COSTO POR PORCIÓN:           $14.010                   │
│  PRECIO DE VENTA:             $19.800                   │
│  MARGEN:                       29.2%                     │
│                                                          │
│  [💾 GUARDAR]  [🍳 PRODUCIR AHORA]                       │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 5: MÓDULO DE RRHH COMPLETO

### 5.1 Modelo de Datos de RRHH

#### Tabla: empleados (schema tenant)
```sql
CREATE TABLE empleados (
    id                   BIGSERIAL PRIMARY KEY,
    usuario_id           BIGINT,             -- FK usuarios (si tiene acceso al sistema)
    rut                  VARCHAR(20) UNIQUE NOT NULL,
    nombres              VARCHAR(255) NOT NULL,
    apellidos            VARCHAR(255) NOT NULL,
    fecha_nacimiento     DATE,
    nacionalidad         VARCHAR(100) DEFAULT 'Chilena',
    direccion            TEXT,
    telefono             VARCHAR(20),
    email_personal       VARCHAR(255),
    email_corporativo    VARCHAR(255),
    cargo                VARCHAR(100),
    departamento         VARCHAR(100),
    fecha_contratacion   DATE NOT NULL,
    fecha_termino        DATE,
    tipo_contrato        ENUM('indefinido','plazo_fijo','honorarios','practica') DEFAULT 'indefinido',
    sueldo_base          BIGINT NOT NULL,     -- centavos CLP
    afp                  VARCHAR(50),         -- 'Cuprum','Habitat','PlanVital','ProVida','Modelo','Capital'
    salud                ENUM('fonasa','isapre') DEFAULT 'fonasa',
    isapre_nombre        VARCHAR(100),
    mutual               ENUM('mutual','achs','ist') DEFAULT 'mutual',
    banco_nombre         VARCHAR(100),
    banco_cuenta_tipo    ENUM('cuenta_rut','vista','corriente'),
    banco_cuenta_numero  VARCHAR(50),
    carga_familiar       BOOLEAN DEFAULT FALSE,
    num_cargas           INT DEFAULT 0,
    dias_vacaciones_acumulados INT DEFAULT 0,
    activo               BOOLEAN DEFAULT TRUE,
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: asistencias (schema tenant)
```sql
CREATE TABLE asistencias (
    id                    BIGSERIAL PRIMARY KEY,
    empleado_id           BIGINT NOT NULL,
    fecha                 DATE NOT NULL,
    hora_entrada          TIME,
    hora_salida           TIME,
    hora_colacion_inicio  TIME,
    hora_colacion_fin     TIME,
    tipo_jornada          ENUM('normal','extra','feriado') DEFAULT 'normal',
    horas_trabajadas      DECIMAL(5,2),        -- calculado
    horas_extra           DECIMAL(5,2) DEFAULT 0,
    atraso_minutos        INT DEFAULT 0,
    ausencia_justificada  BOOLEAN DEFAULT FALSE,
    observacion           TEXT,
    created_at            TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE KEY uk_empleado_fecha (empleado_id, fecha)
);
```

#### Tabla: vacaciones (schema tenant)
```sql
CREATE TABLE vacaciones (
    id                      BIGSERIAL PRIMARY KEY,
    empleado_id             BIGINT NOT NULL,
    fecha_inicio            DATE NOT NULL,
    fecha_fin               DATE NOT NULL,
    dias_solicitados        INT NOT NULL,
    dias_pendientes_antes   INT,
    dias_pendientes_despues INT,
    estado                  ENUM('pendiente','aprobada','rechazada','cancelada') DEFAULT 'pendiente',
    aprobado_por            BIGINT,             -- FK usuarios
    observaciones           TEXT,
    created_at              TIMESTAMPTZ DEFAULT NOW(),
    updated_at              TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: permisos (schema tenant)
```sql
CREATE TABLE permisos (
    id              BIGSERIAL PRIMARY KEY,
    empleado_id     BIGINT NOT NULL,
    tipo            ENUM('administrativo','medico','personal','estudio',
                         'maternidad','paternidad','duelo','otros') DEFAULT 'personal',
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL,
    horas_solicitadas INT,
    con_goce_sueldo BOOLEAN DEFAULT TRUE,
    documento_url   VARCHAR(500),
    estado          ENUM('pendiente','aprobada','rechazada','cancelada') DEFAULT 'pendiente',
    aprobado_por    BIGINT,
    observaciones   TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: liquidaciones (schema tenant)
```sql
CREATE TABLE liquidaciones (
    id                  BIGSERIAL PRIMARY KEY,
    empleado_id         BIGINT NOT NULL,
    periodo             DATE NOT NULL,          -- primer día del mes: 2026-03-01
    sueldo_base         BIGINT NOT NULL,
    horas_extra_valor   BIGINT DEFAULT 0,
    bonos               BIGINT DEFAULT 0,
    gratificacion       BIGINT DEFAULT 0,
    colacion            BIGINT DEFAULT 0,
    movilizacion        BIGINT DEFAULT 0,
    otros_haberes       JSON,                   -- [{nombre:'bono asistencia',monto:50000}]
    total_haberes       BIGINT NOT NULL,
    afp_descuento       BIGINT DEFAULT 0,
    salud_descuento     BIGINT DEFAULT 0,
    mutual_descuento    BIGINT DEFAULT 0,
    impuesto_unico      BIGINT DEFAULT 0,
    otros_descuentos    JSON,
    total_descuentos    BIGINT NOT NULL,
    liquido_pago        BIGINT NOT NULL,        -- total_haberes - total_descuentos
    documento_url       VARCHAR(500),
    pagada              BOOLEAN DEFAULT FALSE,
    fecha_pago          DATE,
    created_at          TIMESTAMPTZ DEFAULT NOW()
);
```

### 5.2 API Endpoints — RRHH

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/empleados | Lista empleados activos | admin |
| POST | /api/empleados | Crear empleado | admin |
| PUT | /api/empleados/{id} | Editar datos empleado | admin |
| POST | /api/asistencias/marcar | Marcar entrada/salida | cajero (propio) |
| GET | /api/asistencias?empleado_id&fecha_desde&fecha_hasta | Historial asistencia | admin |
| POST | /api/vacaciones | Solicitar vacaciones | empleado |
| PUT | /api/vacaciones/{id}/aprobar | Aprobar/rechazar solicitud | admin |
| POST | /api/permisos | Solicitar permiso | empleado |
| PUT | /api/permisos/{id}/aprobar | Aprobar/rechazar permiso | admin |
| POST | /api/liquidaciones | Generar liquidación mensual | admin |
| GET | /api/liquidaciones?empleado_id&periodo | Ver liquidaciones | admin |
| GET | /api/rrhh/dashboard | Resumen asistencia, solicitudes pendientes | admin |

### 5.3 UI — Panel de RRHH

#### Dashboard de RRHH
```
┌──────────────────────────────────────────────────────────┐
│  👥 RECURSOS HUMANOS                   [Marzo 2026 ▼]    │
├──────────────────────────────────────────────────────────┤
│  ┌───────────┬───────────┬───────────┬─────────────────┐ │
│  │ Total     │ Presentes │ Vacaciones│ Permisos        │ │
│  │ 24        │ 18 (75%)  │ 3 (12.5%) │ 3 (12.5%)       │ │
│  └───────────┴───────────┴───────────┴─────────────────┘ │
│                                                          │
│  ⏰ ASISTENCIA HOY                                       │
│  Juan Pérez      09:03  18:15  ✅ Presente              │
│  María González  08:55  17:30  ✅ Presente              │
│  Carlos López    09:30   --    ⚠️ Atraso 30 min         │
│  Ana Rodríguez    --     --    ❌ Ausente sin aviso      │
│  Pedro Soto       --     --    🌴 Vacaciones             │
│                                                          │
│  🌴 SOLICITUDES PENDIENTES                               │
│  Vacaciones · Ana Rodríguez · 20-25/03 · 5 días  [✅][❌]│
│  Permiso    · Carlos López  · 19/03   · 1 día    [✅][❌]│
│  Vacaciones · Pedro Soto   · 22-30/03 · 8 días   [✅][❌]│
│                                                          │
│  [➕ NUEVO EMPLEADO]  [📅 CALENDARIO]  [💰 LIQUIDACIONES] │
└──────────────────────────────────────────────────────────┘
```

#### Control de Asistencia — Marcar
```
┌──────────────────────────────────────────────────────────┐
│  ⏰ CONTROL DE ASISTENCIA              [18/03/2026]      │
├──────────────────────────────────────────────────────────┤
│  MARCAR ENTRADA / SALIDA                                 │
│  ┌────────────────────────────────────────────────────┐ │
│  │  👤 Juan Pérez                                     │ │
│  │  ⏱️ Hora actual: 15:23:08                          │ │
│  │  Última marcación: Entrada 09:03                   │ │
│  │                                                    │ │
│  │              [🔴 MARCAR SALIDA]                    │ │
│  └────────────────────────────────────────────────────┘ │
│                                                          │
│  REGISTRO DEL DÍA                                        │
│  09:03 · Entrada ✅   13:00 · Inicio colación ✅         │
│  14:00 · Fin colación ✅   15:23 · Salida ⏳ Pendiente   │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 6: MÓDULO DE RECLUTAMIENTO Y TALENTO

### 6.1 Modelo de Datos de Reclutamiento

#### Tabla: ofertas_empleo (schema tenant)
```sql
CREATE TABLE ofertas_empleo (
    id                   BIGSERIAL PRIMARY KEY,
    titulo               VARCHAR(255) NOT NULL,
    cargo                VARCHAR(100),
    departamento         VARCHAR(100),
    tipo_contrato        ENUM('indefinido','plazo_fijo','honorarios','practica','freelance'),
    modalidad            ENUM('presencial','hibrido','remoto') DEFAULT 'presencial',
    ubicacion            VARCHAR(255),
    sueldo_min           BIGINT,
    sueldo_max           BIGINT,
    sueldo_publico       BOOLEAN DEFAULT FALSE,
    descripcion          TEXT NOT NULL,
    requisitos           TEXT,
    beneficios           TEXT,
    horario              TEXT,
    experiencia_anios    INT DEFAULT 0,
    educacion_requerida  VARCHAR(255),
    habilidades          JSON,               -- ['ventas','excel','atencion_cliente']
    fecha_publicacion    DATE,
    fecha_cierre         DATE,
    estado               ENUM('borrador','publicada','pausada','cerrada','cancelada') DEFAULT 'borrador',
    vacantes             INT DEFAULT 1,
    postulantes_count    INT DEFAULT 0,      -- cache contador
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: postulaciones (schema tenant)
```sql
CREATE TABLE postulaciones (
    id                      BIGSERIAL PRIMARY KEY,
    oferta_id               BIGINT NOT NULL,
    nombres                 VARCHAR(255) NOT NULL,
    apellidos               VARCHAR(255) NOT NULL,
    rut                     VARCHAR(20),
    email                   VARCHAR(255) NOT NULL,
    telefono                VARCHAR(20),
    fecha_nacimiento        DATE,
    experiencia_resumen     TEXT,
    educacion               TEXT,
    habilidades             TEXT,
    disponibilidad_inmediata BOOLEAN DEFAULT FALSE,
    fecha_disponibilidad    DATE,
    pretension_renta        BIGINT,
    cv_url                  VARCHAR(500),
    carta_url               VARCHAR(500),
    estado                  ENUM('recibida','revisada','preseleccionada',
                                 'entrevista','rechazada','contratada') DEFAULT 'recibida',
    etapa_actual            INT DEFAULT 1,
    calificacion            SMALLINT CHECK (calificacion BETWEEN 1 AND 5),
    observaciones           TEXT,
    created_at              TIMESTAMPTZ DEFAULT NOW(),
    updated_at              TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: entrevistas (schema tenant)
```sql
CREATE TABLE entrevistas (
    id                BIGSERIAL PRIMARY KEY,
    postulacion_id    BIGINT NOT NULL,
    fecha             DATE NOT NULL,
    hora              TIME NOT NULL,
    modalidad         ENUM('presencial','videollamada','telefonica') DEFAULT 'presencial',
    link_reunion      VARCHAR(500),
    entrevistador_id  BIGINT NOT NULL,    -- FK usuarios
    duracion_min      INT DEFAULT 60,
    resultado         ENUM('pendiente','aprobado','rechazado','segunda_instancia') DEFAULT 'pendiente',
    feedback          TEXT,
    created_at        TIMESTAMPTZ DEFAULT NOW()
);
```

### 6.2 API Endpoints — Reclutamiento

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/ofertas-empleo | Lista ofertas activas | admin |
| POST | /api/ofertas-empleo | Crear oferta | admin |
| PUT | /api/ofertas-empleo/{id}/publicar | Publicar oferta | admin |
| GET | /api/postulaciones?oferta_id | Lista postulantes por oferta | admin |
| PUT | /api/postulaciones/{id}/estado | Avanzar/rechazar postulante | admin |
| POST | /api/entrevistas | Agendar entrevista | admin |
| PUT | /api/entrevistas/{id}/resultado | Registrar resultado | admin |
| POST | /api/postulaciones/{id}/contratar | Convertir en empleado | admin |
| GET | /api/reclutamiento/dashboard | Estadísticas globales | admin |
| POST | /publico/postular/{oferta_uuid} | Formulario público de postulación | público |

### 6.3 UI — Gestión de Talento

#### Panel de Ofertas
```
┌──────────────────────────────────────────────────────────┐
│  💼 GESTIÓN DE TALENTO                 [Marzo 2026 ▼]    │
├──────────────────────────────────────────────────────────┤
│  ┌───────────┬───────────┬───────────┬─────────────────┐ │
│  │ Ofertas   │Postulantes│Entrevistas│ Contratados     │ │
│  │ 8 activas │ 124 total │18 sem.    │ 3 este mes      │ │
│  └───────────┴───────────┴───────────┴─────────────────┘ │
│                                                          │
│  OFERTAS ACTIVAS                                         │
│  Vendedor tienda   · 32 postulantes · ⚡ 10 nuevas [Ver] │
│  Cajero            · 18 postulantes · 🟡 5 nuevas  [Ver] │
│  Repartidor        · 24 postulantes · ✅ Activa    [Ver] │
│  Jefe de bodega    · 8 postulantes  · ⚡ 2 nuevas  [Ver] │
│                                                          │
│  PRÓXIMAS ENTREVISTAS                                    │
│  19/03 10:00 · Juan Pérez — Vendedor                    │
│  19/03 12:00 · María López — Cajero                     │
│  20/03 09:30 · Carlos Soto — Jefe Bodega                │
│                                                          │
│  [➕ NUEVA OFERTA]  [📋 POSTULACIONES]  [📊 REPORTES]    │
└──────────────────────────────────────────────────────────┘
```

#### Gestión de Postulantes
```
┌──────────────────────────────────────────────────────────┐
│  📋 POSTULACIONES — VENDEDOR TIENDA             (32)     │
├──────────────────────────────────────────────────────────┤
│  FILTROS: [Todos ▼] [Preseleccionados] [Entrevista]      │
│                                                          │
│  ⭐⭐⭐⭐⭐ Juan Pérez  · 28 años · $700.000               │
│  Exp: 3 años Abarrote Don Juan · Disp: Inmediata        │
│  [📄 CV] [📞 Llamar] [✉️ Email] [✅ Preselect.] [❌ Rec.] │
│                                                          │
│  ⭐⭐⭐⭐ María López · 32 años · $650.000                │
│  Exp: 5 años Supermercado Central · Disp: 15 días       │
│  [📄 CV] [📞 Llamar] [✉️ Email] [✅ Preselect.] [❌ Rec.] │
│                                                          │
│  ⭐⭐⭐ Carlos Soto · 24 años · $600.000                  │
│  Exp: 1 año Tienda de barrio · Disp: Inmediata          │
│  [📄 CV] [📞 Llamar] [✉️ Email] [✅ Preselect.] [❌ Rec.] │
│                                                          │
│  [📧 EMAIL MASIVO A SELECCIONADOS] [📅 AGENDAR ENTREVISTAS]│
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 7: MÓDULO DE MARKETING CON QR

### 7.1 Modelo de Datos de Marketing

#### Tabla: campanas_marketing (schema tenant)
```sql
CREATE TABLE campanas_marketing (
    id             BIGSERIAL PRIMARY KEY,
    nombre         VARCHAR(255) NOT NULL,
    descripcion    TEXT,
    tipo           ENUM('qr','whatsapp','email','sms') DEFAULT 'qr',
    fecha_inicio   DATE,
    fecha_fin      DATE,
    presupuesto    BIGINT DEFAULT 0,
    objetivo       VARCHAR(255),
    estado         ENUM('borrador','activa','pausada','finalizada') DEFAULT 'borrador',
    created_at     TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: qr_campanas (schema tenant)
```sql
CREATE TABLE qr_campanas (
    id              BIGSERIAL PRIMARY KEY,
    campana_id      BIGINT,                 -- FK campanas_marketing (NULL = QR suelto)
    uuid            VARCHAR(36) UNIQUE NOT NULL,
    nombre          VARCHAR(255) NOT NULL,
    tipo_accion     ENUM('descuento_pct','descuento_monto','2x1','informacion',
                         'encuesta','fidelizacion','whatsapp') NOT NULL,
    producto_id     BIGINT,                  -- FK productos
    valor_descuento BIGINT DEFAULT 0,        -- % o monto según tipo
    url_destino     VARCHAR(500),
    fecha_expiracion DATE,
    usos_maximos    INT DEFAULT 0,           -- 0 = ilimitado
    usos_actuales   INT DEFAULT 0,
    activo          BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: escaneos_qr (schema tenant)
```sql
CREATE TABLE escaneos_qr (
    id          BIGSERIAL PRIMARY KEY,
    qr_id       BIGINT NOT NULL,
    cliente_id  BIGINT,                     -- FK clientes (si se identifica)
    timestamp   TIMESTAMPTZ DEFAULT NOW(),
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    conversion  BOOLEAN DEFAULT FALSE,       -- si realizó compra posterior
    venta_id    BIGINT                       -- FK ventas (si convirtió)
);
```

### 7.2 API Endpoints — Marketing QR

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| GET | /api/qr-campanas | Lista QRs activos | admin |
| POST | /api/qr-campanas | Crear nuevo QR | admin |
| PUT | /api/qr-campanas/{id} | Editar QR | admin |
| DELETE | /api/qr-campanas/{id} | Desactivar QR | admin |
| GET | /api/qr-campanas/{id}/estadisticas | Escaneos, conversiones, ROI | admin |
| GET | /publico/qr/{uuid} | Landing page pública del QR | público |
| POST | /publico/qr/{uuid}/escaneo | Registrar escaneo | público |
| GET | /api/marketing/dashboard | Métricas globales de QRs | admin |

### 7.3 UI — Campaña QR

#### Panel de QRs Activos
```
┌──────────────────────────────────────────────────────────┐
│  📱 MARKETING QR                        [Marzo 2026 ▼]   │
├──────────────────────────────────────────────────────────┤
│  ┌───────────┬───────────┬───────────┬─────────────────┐ │
│  │ Escaneos  │Conversiones│Tasa Conv.│ Descuentos otorg│ │
│  │ 1.234     │ 345        │ 28%      │ $234.567        │ │
│  └───────────┴───────────┴───────────┴─────────────────┘ │
│                                                          │
│  QR-001 — 20% descuento en Leche Colún                   │
│  Escaneos: 234 · Usos: 89 · Vence: 31/03                 │
│  [📊 VER] [📥 DESCARGAR] [✏️ EDITAR] [⏸️ PAUSAR]          │
│                                                          │
│  QR-002 — 2x1 en Coca-Cola 1.5L                          │
│  Escaneos: 567 · Usos: 234 · Vence: 15/04               │
│  [📊 VER] [📥 DESCARGAR] [✏️ EDITAR] [⏸️ PAUSAR]          │
│                                                          │
│  QR-003 — Encuesta de satisfacción                       │
│  Escaneos: 433 · Usos: 112 · Sin vencimiento             │
│  [📊 VER] [📥 DESCARGAR] [✏️ EDITAR] [⏸️ PAUSAR]          │
│                                                          │
│  [➕ NUEVO QR]  [📊 REPORTES]  [🎯 OBJETIVOS]             │
└──────────────────────────────────────────────────────────┘
```

#### Crear QR
```
┌──────────────────────────────────────────────────────────┐
│  ✨ NUEVO CÓDIGO QR                                       │
├──────────────────────────────────────────────────────────┤
│  NOMBRE: [Promoción Leche Marzo]                         │
│                                                          │
│  TIPO DE ACCIÓN:                                         │
│  ● Descuento %   ○ Descuento $  ○ 2x1  ○ Encuesta        │
│  ○ Información   ○ Fidelización ○ Abrir WhatsApp         │
│                                                          │
│  Producto: [Leche Colún 1L ▼]                            │
│  Descuento: [20] %                                       │
│  Válido hasta: [31/03/2026]                              │
│  Usos máximos: [500]                                     │
│                                                          │
│  DISEÑO DEL QR:                                          │
│  [QR PREVIEW]  Tamaño: ○ Chico ● Mediano ○ Grande        │
│  Con logo: ● Sí ○ No    Color: ● Negro ○ Azul ○ Custom   │
│                                                          │
│  AL ESCANEAR:                                            │
│  ● Mostrar descuento directamente en landing             │
│  ○ Redirigir a WhatsApp para activar                     │
│  ○ Redirigir a tienda online                             │
│                                                          │
│  [✅ GENERAR QR]  [📥 DESCARGAR PNG]  [📤 COMPARTIR]      │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 8: MÓDULO SII / LIBREDTE

### 8.1 Integración con LibreDTE

El módulo SII se construye como una librería PHP/Laravel 11 personalizada basada en el protocolo LibreDTE, integrada directamente en el flujo de ventas.

#### Tabla: config_sii (schema tenant)
```sql
CREATE TABLE config_sii (
    id                  BIGSERIAL PRIMARY KEY,
    rut_empresa         VARCHAR(20) NOT NULL,
    razon_social        VARCHAR(255) NOT NULL,
    certificado_pfx     BLOB,               -- certificado digital cifrado
    certificado_pass    VARCHAR(500),        -- clave AES-256 cifrada
    ambiente            ENUM('certificacion','produccion') DEFAULT 'certificacion',
    resolucion_numero   INT,
    resolucion_fecha    DATE,
    activo              BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMPTZ DEFAULT NOW()
);
```

#### Tabla: dte_emitidos (schema tenant)
```sql
CREATE TABLE dte_emitidos (
    id              BIGSERIAL PRIMARY KEY,
    venta_id        BIGINT,                 -- FK ventas (NULL si manual)
    tipo_dte        ENUM('boleta','factura','honorarios','nota_credito','nota_debito'),
    folio           INT NOT NULL,
    rut_receptor    VARCHAR(20),
    razon_social    VARCHAR(255),
    fecha_emision   DATE NOT NULL,
    neto            BIGINT DEFAULT 0,
    iva             BIGINT DEFAULT 0,
    total           BIGINT NOT NULL,
    estado_sii      ENUM('pendiente','enviada','aceptada','rechazada','anulada') DEFAULT 'pendiente',
    track_id        VARCHAR(100),            -- ID de seguimiento SII
    xml_url         VARCHAR(500),
    pdf_url         VARCHAR(500),
    email_enviado   BOOLEAN DEFAULT FALSE,
    wa_enviado      BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 8.2 API Endpoints — SII

| Método | Endpoint | Descripción | Rol mínimo |
|---|---|---|---|
| POST | /api/sii/emitir | Emitir DTE para venta | admin,cajero |
| GET | /api/sii/dte?tipo&fecha | Listado de DTEs emitidos | admin |
| GET | /api/sii/dte/{id}/pdf | Descargar PDF del DTE | admin |
| GET | /api/sii/dte/{id}/xml | Descargar XML del DTE | admin |
| POST | /api/sii/dte/{id}/reenviar | Reenviar al SII (si rechazado) | admin |
| GET | /api/sii/libro/ventas?mes&año | Libro de ventas mensual | admin |
| GET | /api/sii/libro/compras?mes&año | Libro de compras mensual | admin |
| GET | /api/sii/dashboard | Resumen del día (timbres, totales) | admin |
| POST | /api/sii/config | Guardar configuración del certificado | super_admin |

### 8.3 UI — Panel SII

#### Dashboard SII
```
┌──────────────────────────────────────────────────────────┐
│  📄 FACTURACIÓN ELECTRÓNICA              [Certificación] │
├──────────────────────────────────────────────────────────┤
│  RESUMEN HOY                                             │
│  Documentos emitidos: 24                                 │
│  • Boletas: 18   ($342.500)                              │
│  • Facturas: 4   ($1.234.000)                            │
│  • Honorarios: 2  ($120.000)                             │
│                                                          │
│  Timbres disponibles: 4.328 / 5.000                      │
│  Próx. vencimiento: 31/12/2026                           │
│                                                          │
│  ÚLTIMOS DOCUMENTOS                                      │
│  14:30 · BOL 1042 · María González · $4.320  · ✓ ACE    │
│  14:15 · FAC 101  · Constr. Andes  · $522K   · ✓ ACE    │
│  13:50 · BOL 1041 · Juan Pérez     · $2.890  · ✓ ACE    │
│  13:20 · HON 58   · Ana López      · $60K    · ⏳ ENV    │
│                                                          │
│  [➕ NUEVO DOCUMENTO] [📊 LIBRO IVA] [⚠️ RECHAZADOS: 0]  │
└──────────────────────────────────────────────────────────┘
```

---

## PARTE 9: WHATSAPP BOT — INTEGRACIÓN ERP

### 9.1 Flujos por Industria

#### 9.1.1 Abarrotes / Retail — Pedido Remoto
```
Cliente WA: "Hola, ¿tienen leche?"
Bot: "Sí, tenemos Leche Colún 1L a $1.190. ¿Cuántas necesitas?"
Cliente: "2 leches y 1 pan molde"
Bot: "[Carrito] Leche x2 $2.380 + Pan $1.490 = $3.870. ¿Confirmas?"
Cliente: "Sí"
Bot: "¡Pedido creado! #VTA-1042 listo en ~20 min. ¿Retiras o quieres delivery (+$2.000)?"
→ ERP: Crea venta remota_pendiente, notifica a cajero
→ ERP: Al preparar, bot envía "Tu pedido está listo para retiro"
```

#### 9.1.2 Motel — Consulta y Reserva
```
Cliente WA: "¿Tienen habitación disponible?"
Bot: "Sí, tenemos Suite disponible a $15.000/hora (mín. 2 horas). ¿Para cuándo?"
Cliente: "Ahora"
Bot: "Suite disponible. 2 horas = $30.000 o 3 horas = $42.000. ¿Cuál prefieres?"
→ ERP: Crea reserva, bloquea habitación, activa timer
```

#### 9.1.3 Profesionales — Agenda
```
Cliente WA: "Quiero hora con la Dra. Pérez"
Bot: "Claro. Las horas disponibles son: Lunes 10:00, Martes 15:00, Miércoles 09:00. ¿Cuál prefieres?"
Cliente: "Lunes 10:00"
Bot: "Agenda confirmada: Lunes 17/03 a las 10:00 con Dra. Pérez. Te recordaré mañana."
→ ERP: Crea cita en calendario, asigna a profesional
→ ERP: Día anterior, bot envía recordatorio automático
```

### 9.2 Acciones Transaccionales del Bot

| Intención | Ejemplo Cliente | Acción ERP | Respuesta Bot |
|---|---|---|---|
| Consultar stock | "¿tienen cemento?" | GET /api/productos/stock | "Sí, 48 bolsas a $6.300" |
| Consultar precio | "¿cuánto cuesta la leche?" | GET /api/productos/precio | "Leche Colún 1L: $1.190" |
| Crear pedido | "quiero 2 leches" | POST /api/ventas (remota) | Confirmación con total |
| Consultar deuda | "¿cuánto debo?" | GET /api/clientes/{rut}/deudas | "Debes $23.450 al 15/03" |
| Pagar deuda | "quiero pagar mi deuda" | POST /api/pagos/link | Envía link Webpay |
| Ver pedido | "¿dónde está mi pedido?" | GET /api/entregas/{uuid}/publico | Estado actual + ETA |
| Agendar hora | "quiero una cita" | POST /api/citas | Horarios disponibles |

---

## PARTE 10: UI/UX — PANTALLAS DINÁMICAS POR CONFIGURACIÓN

> **Principio:** Las pantallas NO están hardcodeadas por industria.
> El frontend lee `rubros_config` al cargar y renderiza solo los
> componentes que corresponden a los módulos activos del tenant.
> Ver documento `BENDERAND_CONFIG_INDUSTRIAS.md` para el sistema completo.

### 10.1 Pantalla Base POS (siempre presente — todos los rubros)

```
┌──────────────────────────────────────────────────────────┐
│  💳 VENTA                        [Empresa]  [Usuario]    │
├──────────────────────────────────────────────────────────┤
│  🔍 Buscar {label_producto}...                           │
│                                                          │
│  CARRITO                                                 │
│  ─────────────────────────────────────────────────────   │
│  [items aquí]                                            │
│                                                          │
│  SUBTOTAL:    $0                                         │
│  TOTAL:       $0                                         │
│                                                          │
│  [💵 EFECTIVO] [💳 TARJETA] [📱 TRANSF]                  │
│  {si M11} [📝 FIADO]                                     │
│  {si M09} [📃 HONORARIOS]                                │
│                    [✅ CONFIRMAR VENTA]                   │
└──────────────────────────────────────────────────────────┘
```

### 10.2 Componentes Condicionales POS (activados por módulo)

#### Componente: Peso/Fraccionado (M04)
```
  🔍 Buscar producto...   [Unidad] ● kg/L/m ○

  Al seleccionar fraccionado:
  Azúcar                  [  0.500  ] kg × $900/kg = $450
```

#### Componente: Selector DTE (M20 + mayorista M26)
```
  Tipo documento: ● Boleta  ○ Factura  ○ Guía despacho
  RUT receptor:  [______________]  [Buscar empresa]
```

#### Componente: Notas Cifradas (M10)
```
  📝 {label_nota} (solo visible para rol médico/profesional)
  ┌─────────────────────────────────────────────────────┐
  │ 🔒 Historia clínica — solo tú puedes leer esto      │
  │ [Escribir nota confidencial...]                     │
  └─────────────────────────────────────────────────────┘
```

#### Componente: Descuento por Volumen (M26)
```
  Cemento Bolsa  x[  50  ]  $6.300/u  ← $5.985/u (5% vol.)
                              ↑ descuento automático por 50+ unidades
```

### 10.3 Pantallas Especializadas (según módulos activos)

#### Vista Recursos/Mapa (M14 + M06) — Motel, Canchas, Hotel

```
┌──────────────────────────────────────────────────────────┐
│  {label_recurso}S                       [Hoy 14:23]      │
├──────────────────────────────────────────────────────────┤
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐         │
│  │   1    │  │   2    │  │   3    │  │   4    │         │
│  │ {est.} │  │ LIBRE  │  │ {est.} │  │ {est.} │         │
│  │ {timer}│  │        │  │ {timer}│  │ {info} │         │
│  └────────┘  └────────┘  └────────┘  └────────┘         │
│                                                          │
│  DETALLE {label_recurso} SELECCIONADO:                   │
│  Timer (si M06): [████████████████████░░] 85%           │
│  {items del carrito del recurso}                         │
│                                                          │
│  {si M06} [⏱️ EXTENDER] [✅ CHECKOUT]                    │
│  {si M05} [📦 DEVOLVER] [✅ CERRAR RENTA]                │
└──────────────────────────────────────────────────────────┘

Colores de estado (configurables en rubros_config.recurso_estados):
  LIBRE      → verde  (#22c55e)
  OCUPADO    → rojo   (#ef4444)
  RESERVADO  → naranja(#f59e0b)    ← solo M08
  LIMPIEZA   → azul   (#3b82f6)    ← estados extra del motel
  MANTENCIÓN → gris   (#6b7280)
```

#### Vista Agenda / Citas (M08) — Médico, Dentista, Abogado, Cancha, Spa

```
┌──────────────────────────────────────────────────────────┐
│  📅 AGENDA                              [18/03/2026]     │
├──────────────────────────────────────────────────────────┤
│  {si multi-profesional: selector de profesional}         │
│  [Dra. Pérez ▼]                                          │
│                                                          │
│  09:00  ──────────────  LIBRE                            │
│  10:00  ██████████████  Juan Pérez · Consulta  · ⏳      │
│  11:00  ██████████████  María López · Limpieza · ✅ llegó│
│  12:00  ──────────────  LIBRE                            │
│  13:00  COLACIÓN                                         │
│  14:00  ██████████████  Carlos Soto · Rx + consult · 📋  │
│                                                          │
│  CITA ACTIVA: María López — 11:00                        │
│  {si M07} [➕ Agregar {label_producto}]                  │
│  {si M10} [📝 {label_nota} 🔒]                          │
│  [💳 COBRAR] [✅ FINALIZAR]                               │
└──────────────────────────────────────────────────────────┘
```

#### Vista Comandas / Cocina (M15) — Restaurante, Café, Delivery

```
┌──────────────────────────────────────────────────────────┐
│  🍽️ COMANDAS EN CURSO                    [13:45]        │
├──────────────────────────────────────────────────────────┤
│  NUEVA (3)          EN COCINA (2)        LISTA (1)       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ Mesa 5       │  │ Mesa 3       │  │ Delivery D45 │   │
│  │ Lomo x2      │  │ Pasta x1     │  │ Combo x1     │   │
│  │ Cerveza x3   │  │ Ensalada x2  │  │ ✅ LISTA      │   │
│  │ [🍳 INICIAR] │  │ ⏱️ 8 min     │  │ [ENTREGAR]   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
└──────────────────────────────────────────────────────────┘
```

#### Vista Órdenes de Trabajo (M28) — Taller, Técnico, Gasfíter

```
┌──────────────────────────────────────────────────────────┐
│  🔧 ÓRDENES DE TRABAJO                                   │
├──────────────────────────────────────────────────────────┤
│  DIAGNÓSTICO    PRESUPUESTO    APROBADA    EJECUCIÓN    LISTA
│  ─────────────────────────────────────────────────────   │
│  OT-045         OT-043         OT-041      OT-039      OT-037
│  {patente/id}   {patente/id}   {pat/id}    {pat/id}    {pat/id}
│  {problema}     {total ppto}   {en proceso}{mechano}   {avisado}
│  [Ver] [→]      [Aprobar][→]  [Ver][→]    [Listo][→]  [Entregar]
│                                                          │
│  OT-045: Toyota Corolla ABCD12                          │
│  Descripción: No parte, ruidos al frenar                │
│  {si M29} Historial: 3 visitas anteriores · Ver →       │
│                                                          │
│  SERVICIOS (M07):                                        │
│  + Diagnóstico eléctrico (servicio)      $25.000         │
│  REPUESTOS (M03):                                        │
│  + Pastillas freno delanteras (stock)    $48.000         │
│  [➕ AGREGAR]   TOTAL:                   $73.000         │
│  [💳 COBRAR]   [📄 ENVIAR PRESUPUESTO WA]                │
└──────────────────────────────────────────────────────────┘
```

#### Vista Check-in / Membresías (M30) — Gym, Cancha, Spa

```
┌──────────────────────────────────────────────────────────┐
│  🎫 CHECK-IN SOCIO                                       │
├──────────────────────────────────────────────────────────┤
│  RUT / Código: [______________]  [BUSCAR]                │
│                                                          │
│  Juan Pérez · Membresía: MENSUAL PRO                     │
│  Vence: 30/04/2026 ✅  Accesos este mes: 12              │
│  {si cancha} Reserva hoy: 15:00 Cancha 2 ✅              │
│                                                          │
│  [✅ CONFIRMAR CHECK-IN]   [💳 RENOVAR MEMBRESÍA]        │
└──────────────────────────────────────────────────────────┘
```

### 10.4 Menús Admin Dinámicos por Módulo Activo

| Ítem de menú | Módulo requerido | Visible para |
|---|---|---|
| Dashboard | — siempre | admin |
| Ventas | — siempre | admin |
| {label_cliente}s | — siempre | admin |
| Compras | M18 | admin |
| Inventario | M03 | admin |
| Proveedores | M18 | admin |
| Agenda | M08 | admin |
| Ocupación (recursos) | M06 | admin |
| Recetas | M16 | admin |
| Delivery | M13 | admin |
| Órdenes de trabajo | M28 | admin |
| Membresías | M30 | admin |
| RRHH | M21 | admin |
| Liquidaciones | M22 | admin |
| Reclutamiento | M23 | admin |
| Marketing QR | M24 | admin |
| SII / DTE | M20 | admin |
| Configuración | — siempre | admin |

### 10.5 Menús POS Dinámicos por Módulo Activo

| Ítem de menú POS | Módulo requerido | Visible para |
|---|---|---|
| 💳 Venta | — siempre | cajero, operario |
| 📅 Agenda | M08 | recepcionista |
| 🗺️ {label_recurso}s | M14 | cajero |
| 🍽️ Comandas | M15 | operario-cocina |
| 🔧 OT | M28 | operario-técnico |
| 🚚 Delivery | M13 | cajero |
| 📋 Encargos | M12 | cajero |
| 🎫 Check-in | M30 | cajero |
| 💰 Caja / Turno | — siempre | cajero |



---

## PARTE 11: DASHBOARD UNIFICADO (ADMIN)

> El menú lateral se construye dinámicamente desde `rubros_config.modulos_activos`.
> Un abogado **nunca** verá "Canchas" ni "Recetas". Un motel **nunca** verá "RRHH" ni "Reclutamiento"
> a menos que los haya activado explícitamente.

```
┌──────────────────────────────────────────────────────────────┐
│  BENDERAND ERP          [{nombre_empresa}]  [{usuario}]      │
├──────────────────┬───────────────────────────────────────────┤
│  ← MENÚ          │                                           │
│  DINÁMICO        │  📊 RESUMEN DEL DÍA                       │
│  (solo módulos   │  Ventas: $234.500  (↑12% vs ayer)        │
│   activos)       │  {label_cliente}s: 34  · Prom: $6.897    │
│                  │  {si M17} Pedidos WA: 8 · Conv. 72%       │
│  █ Dashboard     │  {si M11} Deudas: $124.300                │
│  █ Ventas        │                                           │
│  █ {Clientes}    │  🟢 ACTIVIDAD EN VIVO                     │
│  ─ ─ ─ ─ ─ ─    │  {eventos en tiempo real por módulos}      │
│  {si M18}        │                                           │
│  █ Compras       │  📈 VENTAS ÚLTIMOS 7 DÍAS                 │
│  █ Proveedores   │  [gráfico]                                │
│  {si M03}        │                                           │
│  █ Inventario    │  ⚠️ ALERTAS (solo alertas de módulos       │
│  {si M08}        │     que el tenant tiene activos)          │
│  █ Agenda        │  {si M03} • 3 productos bajo stock        │
│  {si M14+M06}    │  {si M06} • 2 rentas por vencer           │
│  █ {Recursos}    │  {si M21} • 3 solicitudes RRHH pend.      │
│  {si M15+M16}    │  {si M18} • OC-1045 requiere autorización │
│  █ Recetas       │  {si M08} • 2 citas sin confirmar hoy     │
│  {si M13}        │  {si M28} • 1 OT esperando aprobación     │
│  █ Delivery      │                                           │
│  {si M28}        │                                           │
│  █ OT            │                                           │
│  {si M30}        │                                           │
│  █ Membresías    │                                           │
│  {si M21}        │                                           │
│  █ RRHH          │                                           │
│  {si M22}        │                                           │
│  █ Liquidaciones │                                           │
│  {si M23}        │                                           │
│  █ Reclutamiento │                                           │
│  {si M24}        │                                           │
│  █ Marketing QR  │                                           │
│  {si M20}        │                                           │
│  █ SII / DTE     │                                           │
│  ─ ─ ─ ─ ─ ─    │                                           │
│  █ Configuración │                                           │
└──────────────────┴───────────────────────────────────────────┘

Nota: {si MXX} = solo se renderiza si el módulo está en
      rubros_config.modulos_activos del tenant.
      {label_X} = etiqueta personalizada del rubro.
```

---

## PARTE 12: ESTRUCTURA DE ARCHIVOS DEL PROYECTO

```
benderand-erp/
├── backend-laravel/                          ← ERP Core (PHP 8.2 + Laravel 11)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   ├── Tenant/               ← rutas dentro del tenant (schema activo)
│   │   │   │   │   │   ├── AuthController.php
│   │   │   │   │   │   ├── VentaController.php
│   │   │   │   │   │   ├── ProductoController.php
│   │   │   │   │   │   ├── ClienteController.php
│   │   │   │   │   │   ├── CompraController.php
│   │   │   │   │   │   ├── OrdenCompraController.php     ← Hito 11
│   │   │   │   │   │   ├── ProveedorController.php       ← Hito 11
│   │   │   │   │   │   ├── RecepcionController.php       ← Hito 11
│   │   │   │   │   │   ├── EntregaController.php         ← Hito 12
│   │   │   │   │   │   ├── RepartidorController.php      ← Hito 12
│   │   │   │   │   │   ├── RecetaController.php          ← Hito 13
│   │   │   │   │   │   ├── ProduccionController.php      ← Hito 13
│   │   │   │   │   │   ├── EmpleadoController.php        ← Hito 14
│   │   │   │   │   │   ├── AsistenciaController.php      ← Hito 14
│   │   │   │   │   │   ├── VacacionController.php        ← Hito 14
│   │   │   │   │   │   ├── LiquidacionController.php     ← Hito 14
│   │   │   │   │   │   ├── OfertaEmpleoController.php    ← Hito 15
│   │   │   │   │   │   ├── PostulacionController.php     ← Hito 15
│   │   │   │   │   │   ├── QrCampanaController.php       ← Hito 16
│   │   │   │   │   │   ├── SiiController.php             ← Hito 10
│   │   │   │   │   │   ├── SaasClienteController.php     ← Hito 19 M31
│   │   │   │   │   │   ├── SaasPipelineController.php    ← Hito 19 M31
│   │   │   │   │   │   ├── SaasMetricasController.php    ← Hito 19 M31
│   │   │   │   │   │   ├── SaasCobrosController.php      ← Hito 19 M31
│   │   │   │   │   │   └── SaasOnboardingController.php  ← Hito 19 M31 (webhook WA)
│   │   │   │   │   └── Central/              ← rutas del schema public (super admin)
│   │   │   │   │       ├── TenantsController.php
│   │   │   │   │       ├── PlanesController.php
│   │   │   │   │       └── BillingController.php
│   │   │   │   └── Webhook/
│   │   │   │       ├── WhatsAppController.php
│   │   │   │       └── QrScanController.php
│   │   │   └── Middleware/
│   │   │       ├── AuthMiddleware.php
│   │   │       ├── RoleMiddleware.php        ← Gate::define por rol
│   │   │       └── TenantMiddleware.php      ← stancl/tenancy InitializeTenancy
│   │   ├── Models/
│   │   │   ├── Central/                      ← conectan al schema public
│   │   │   │   ├── Tenant.php                ← extends \Stancl\Tenancy\Database\Models\Tenant
│   │   │   │   ├── Plan.php
│   │   │   │   └── ProveedorGlobal.php
│   │   │   └── Tenant/                       ← conectan al schema activo del tenant
│   │   │       ├── Producto.php
│   │   │       ├── Venta.php
│   │   │       ├── Cliente.php
│   │   │       ├── OrdenCompra.php
│   │   │       ├── ProveedorTenant.php
│   │   │       ├── Entrega.php
│   │   │       ├── Receta.php
│   │   │       ├── Empleado.php
│   │   │       ├── Asistencia.php
│   │   │       ├── OfertaEmpleo.php
│   │   │       ├── QrCampana.php
│   │   │       ├── SaasCliente.php           ← Hito 19 M31
│   │   │       ├── SaasPlan.php              ← Hito 19 M31
│   │   │       ├── SaasPipeline.php          ← Hito 19 M31
│   │   │       └── SaasCobro.php             ← Hito 19 M31
│   │   ├── Services/
│   │   │   ├── JwtBridgeService.php          ← JWT compartido con Node.js
│   │   │   ├── WhatsAppNotifier.php          ← llama API del bot Node.js
│   │   │   ├── SiiService.php                ← LibreDTE integrado
│   │   │   ├── QrGenerator.php               ← genera QR con logo tenant
│   │   │   ├── LiquidacionCalculator.php     ← cálculo previsional chileno
│   │   │   ├── StockAlertService.php         ← detecta stock mínimo → OC auto
│   │   │   ├── DeliveryAssignService.php     ← asignación óptima repartidor
│   │   │   ├── RecetaCostService.php         ← calcula costo por porción
│   │   │   ├── SaasBillingService.php        ← genera cobros + emite facturas M31
│   │   │   └── SaasMetricasService.php       ← calcula MRR, ARR, churn, ARPU M31
│   │   ├── Jobs/                             ← Laravel Horizon / Queue
│   │   │   ├── EmitirDteJob.php              ← DTE async (SII puede tardar)
│   │   │   ├── NotificarClienteWaJob.php     ← notificación WA post-venta
│   │   │   ├── GenerarLiquidacionJob.php
│   │   │   ├── StockAlertJob.php             ← cron diario
│   │   │   ├── GenerarCobrosRecurrentes.php  ← 1° de mes, 08:00 M31
│   │   │   ├── AlertaTrialVencimiento.php    ← día 25 del trial M31
│   │   │   ├── AlertaMorosos.php             ← tenants +5 días sin pagar M31
│   │   │   ├── SuspenderMorosos.php          ← tenants +30 días sin pagar M31
│   │   │   ├── ActualizarMetricasMRR.php     ← diario 00:30 M31
│   │   │   ├── SeguimientoTrialDia7.php      ← WA engagement M31
│   │   │   ├── EmitirFacturasDelMes.php      ← DTE por cobros del ciclo M31
│   │   │   └── ReporteEjecutivos.php         ← resumen semanal equipo M31
│   │   ├── Events/
│   │   │   ├── VentaConfirmada.php
│   │   │   ├── EntregaActualizada.php
│   │   │   └── AsistenciaMarcada.php
│   │   ├── Listeners/
│   │   │   ├── NotificarBotWhatsApp.php
│   │   │   └── ActualizarStockDesdeVenta.php
│   │   └── Policies/
│   │       ├── VentaPolicy.php
│   │       ├── ProductoPolicy.php
│   │       └── EmpleadoPolicy.php
│   ├── database/
│   │   ├── migrations/                       ← migraciones schema public (central)
│   │   │   ├── 2026_01_01_create_tenants_table.php
│   │   │   ├── 2026_01_02_create_planes_table.php
│   │   │   └── 2026_01_03_create_proveedores_globales_table.php
│   │   ├── migrations/tenant/                ← migraciones por schema tenant
│   │   │   ├── 2026_01_10_create_productos_table.php
│   │   │   ├── 2026_01_11_create_ventas_table.php
│   │   │   ├── 2026_01_12_create_ordenes_compra_table.php
│   │   │   ├── 2026_01_13_create_empleados_table.php
│   │   │   └── ... (una por tabla del tenant)
│   │   └── seeders/
│   │       ├── ProveedoresGlobalesSeeder.php ← Coca-Cola, CCU, Nestlé, etc.
│   │       └── PlanesSeeder.php
│   ├── routes/
│   │   ├── api.php                           ← rutas públicas / auth
│   │   ├── tenant.php                        ← rutas con middleware tenancy.initialize
│   │   ├── central.php                       ← rutas super admin (schema public)
│   │   └── web.php                           ← landing QR pública, postulaciones
│   ├── config/
│   │   ├── tenancy.php                       ← stancl/tenancy configuración
│   │   └── sii.php                           ← LibreDTE config por ambiente
│   └── composer.json
│       ← dependencias clave:
│       "laravel/framework": "^11.0",
│       "stancl/tenancy": "^3.8",
│       "laravel/sanctum": "^4.0",
│       "laravel/horizon": "^5.0",
│       "firebase/php-jwt": "^6.0",
│       "sasco/libredte-lib-core": "^2.0",
│       "endroid/qr-code": "^5.0"
│
├── whatsapp-bot-node/                    ← Moteland (en producción, Node.js)
│   ├── src/
│   │   ├── controllers/
│   │   │   ├── webhook.controller.js
│   │   │   ├── message.controller.js
│   │   │   ├── handover.controller.js
│   │   │   └── erp.controller.js                 ← NUEVO: webhook desde Laravel
│   │   ├── services/
│   │   │   ├── gpt.service.js
│   │   │   ├── erp-integration.service.js        ← NUEVO: GET stock/precio/citas
│   │   │   ├── sii-link.service.js               ← NUEVO: links de pago
│   │   │   └── industry-intents.service.js       ← NUEVO: intenciones por rubro
│   │   └── middleware/
│   │       └── erp-auth.js                       ← valida JWT compartido con Laravel
│   └── prisma/schema.prisma
│
├── public/                               ← Frontend Vanilla JS + HTML/CSS
│   ├── pos/
│   │   ├── pos_v4.html                   ← POS cajero/operario (existente Hito 8)
│   │   ├── motel.html
│   │   ├── padel.html
│   │   ├── profesionales.html
│   │   ├── mayorista.html
│   │   └── restaurante.html
│   ├── admin/
│   │   ├── dashboard.html
│   │   ├── ventas.html
│   │   ├── compras.html
│   │   ├── proveedores.html
│   │   ├── inventario.html
│   │   ├── clientes.html
│   │   ├── delivery.html
│   │   ├── recetas.html
│   │   ├── rrhh/
│   │   │   ├── empleados.html
│   │   │   ├── asistencia.html
│   │   │   ├── vacaciones.html
│   │   │   └── liquidaciones.html
│   │   ├── reclutamiento/
│   │   │   ├── ofertas.html
│   │   │   └── postulantes.html
│   │   ├── marketing/
│   │   │   └── qr.html
│   │   ├── sii/
│   │   │   ├── dashboard.html
│   │   │   └── libro-iva.html
│   │   └── config.html
│   ├── superadmin/
│   │   ├── dashboard.html
│   │   └── empresas.html
│   └── publico/                          ← sin auth, acceso libre
│       ├── qr.html                       ← Landing page QR (param ?uuid=)
│       └── empleo.html                   ← Formulario postulación (param ?oferta=)
│
├── nginx/
│   └── conf.d/
│       ├── erp.benderand.cl.conf         ← Laravel app (FPM)
│       ├── wa.benderand.cl.conf          ← Node.js API + WebSocket
│       └── reverb.benderand.cl.conf      ← Laravel Reverb WebSocket
│
├── docker/
│   ├── docker-compose.yml
│   ├── docker-compose.prod.yml
│   └── .env.example
│
└── scripts/
    ├── deploy.sh
    ├── onboard-tenant.js
    ├── migrate-from-moteland.js
    └── seed-proveedores-globales.php     ← artisan db:seed --class=ProveedoresGlobalesSeeder
```

---

## PARTE 13: PLAN DE HITOS COMPLETO (v2.0)

### Estado Actual (Hitos 1–8 completados)

| Hito | Alcance | Estado |
|---|---|---|
| Hito 1 | Venta minorista multi-rubro, login, compra, inventario base | ✅ Completo |
| Hito 2 | Multi-operario, roles separados, portal cliente básico | ✅ Completo |
| Hito 3 | Renta y servicios, tipos de producto, honorarios | ✅ Completo |
| Hito 4 | Onboarding WhatsApp, notificaciones, pedido remoto | ✅ Completo |
| Hito 5 | Super admin, billing, planes, métricas MRR | ✅ Completo |
| Hito 6 | Portal cliente web, catálogo, seguimiento | ✅ Completo |
| Hito 7 | WhatsApp Bot (Moteland) MVP producción | ✅ Completo |
| Hito 8 | Separación de roles, super admin UI, config empresa | ✅ Completo |

### Hoja de Ruta (Hitos 9–16)

---

### HITO 9: Integración ERP ↔ WhatsApp Bot (3 semanas)

**Objetivo**: Comunicación bidireccional entre Laravel 11 y Node.js

**Tareas:**
- [ ] Definir JWT compartido entre Laravel 11 y Node.js (mismo secret, mismos claims)
- [ ] Crear servicio `ErpIntegrationService.js` en Node.js para llamar Laravel 11
- [ ] Endpoints Laravel 11 internos: stock, precios, clientes, ventas remotas
- [ ] Webhook Laravel 11 → Node.js: `venta_confirmada`, `pedido_listo`, `cita_confirmada`
- [ ] Webhook Node.js → Laravel 11: `pedido_whatsapp`, `cliente_identificado`
- [ ] Intenciones por rubro: configurar GPT con contexto de catálogo ERP
- [ ] Dashboard unificado: pestaña WhatsApp en panel admin
- [ ] Tests de integración end-to-end (pedido WA → venta ERP → notificación WA)

**Checklist de verificación:**
- [ ] Cliente WA puede consultar stock en tiempo real
- [ ] Pedido creado en WA aparece en ERP como `remota_pendiente`
- [ ] Venta confirmada en ERP dispara notificación WA al cliente
- [ ] JWT del ERP válido en endpoints Node.js

---

### HITO 10: Módulo SII / LibreDTE (4 semanas)

**Objetivo**: Facturación electrónica integrada en el flujo de venta

**Tareas:**
- [ ] Instalar y configurar librería LibreDTE PHP
- [ ] Crear `SiiService.php` con métodos: `emitirBoleta()`, `emitirFactura()`, `emitirHonorarios()`, `emitirNotaCredito()`
- [ ] Tabla `config_sii` con certificado cifrado AES-256
- [ ] Tabla `dte_emitidos` con estado de seguimiento
- [ ] Integración automática: al confirmar venta → emitir DTE según config rubro
- [ ] Envío automático por email del PDF
- [ ] Envío por WhatsApp del PDF (vía bot Node.js)
- [ ] Sistema de reenvío automático en caso de rechazo SII
- [ ] Dashboard SII: timbres, totales del día, últimos DTEs
- [ ] Libro de ventas y compras mensual
- [ ] Ambiente certificación para pruebas

**Checklist de verificación:**
- [ ] Boleta se emite automáticamente al confirmar venta
- [ ] Factura requiere RUT empresa + razón social
- [ ] DTE aparece en dashboard con estado ACE/REC/REP
- [ ] PDF generado y enviable por email y WA
- [ ] Libro de ventas muestra resumen correcto del mes

---

### HITO 11: Módulo de Compras y Proveedores (4 semanas)

**Objetivo**: Gestión completa de proveedores, OC y recepción de mercancía

**Tareas:**
- [ ] Migración: tablas `proveedores_globales` (master) y `proveedores_tenant`
- [ ] Migración: tablas `ordenes_compra`, `items_orden_compra`
- [ ] Migración: tablas `recepciones_compra`, `items_recepcion`
- [ ] Seed de proveedores globales base (Coca-Cola, CCU, Nestlé, Soprole, etc.)
- [ ] CRUD proveedores con vinculación a globales
- [ ] Catálogo productos por proveedor con precios históricos
- [ ] Generación automática de OC por stock mínimo (cron job diario)
- [ ] Flujo de aprobación de OC (borrador → autorizada → enviada)
- [ ] Recepción de mercancía con control de calidad (aceptado/rechazado por lote)
- [ ] Integración con inventario: recepción ajusta stock automáticamente
- [ ] Descuentos por volumen configurables por proveedor
- [ ] Dashboard de compras: métricas, alertas, OC recientes
- [ ] Reportes: compras por proveedor, por categoría, comparativo mensual

**Checklist de verificación:**
- [ ] Crear OC manual seleccionando proveedor y productos
- [ ] Sistema sugiere OC automática cuando producto baja de stock mínimo
- [ ] Recepción parcial actualiza estado OC a `parcial`
- [ ] Items rechazados en recepción no incrementan stock
- [ ] Lotes y fechas de vencimiento se guardan por ítem recibido

---

### HITO 12: Módulo de Delivery (3 semanas)

**Objetivo**: Gestión de repartidores, asignación y tracking de entregas

**Tareas:**
- [ ] Migración: tablas `repartidores`, `entregas`, `tracking_entregas`
- [ ] CRUD repartidores con zonas de cobertura
- [ ] Integración con ventas: al confirmar venta con delivery → crear entrega
- [ ] Panel de asignación manual de repartidores
- [ ] Endpoint de actualización de estado por repartidor (móvil-friendly)
- [ ] SSE para tracking en tiempo real en dashboard admin
- [ ] Página pública de seguimiento para cliente (QR + link WA)
- [ ] Notificación WA automática: asignado, en camino, entregado
- [ ] Cálculo de costo de envío por zona/distancia
- [ ] Reportes: entregas por repartidor, tiempos promedio, incidencias

**Checklist de verificación:**
- [ ] Venta con delivery crea entrega automáticamente en estado `pendiente`
- [ ] Admin puede asignar repartidor y cambiar estado
- [ ] Cliente recibe notificación WA en cada cambio de estado
- [ ] Link público de seguimiento funciona sin login
- [ ] Repartidor puede actualizar estado desde móvil (URL simple)

---

### HITO 13: Módulo de Restaurante — Recetas (3 semanas)

**Objetivo**: Gestión de recetas con costeo y descuento automático de ingredientes

**Tareas:**
- [ ] Migración: tablas `recetas`, `ingredientes_receta`, `producciones`, `items_produccion`
- [ ] CRUD recetas con editor de ingredientes
- [ ] Cálculo automático de costo por porción (ingredientes + merma + mano de obra)
- [ ] Verificación de stock antes de producir (alerta de faltantes)
- [ ] Producción: descuento automático de ingredientes del inventario
- [ ] Integración con compras: producción sugiere OC para ingredientes faltantes
- [ ] Vista de comandas para cocina (pantalla simple, sin login extra)
- [ ] Carta digital con precios y disponibilidad en tiempo real
- [ ] Reporte de costo real vs precio de venta por plato

**Checklist de verificación:**
- [ ] Receta calcula costo automáticamente al cambiar precios de ingredientes
- [ ] Producción de 10 porciones descuenta ingredientes correctamente
- [ ] Alerta cuando ingrediente insuficiente para producir
- [ ] Comanda en cocina muestra pedidos en tiempo real

---

### HITO 14: Módulo de RRHH (4 semanas)

**Objetivo**: Gestión completa del personal

**Tareas:**
- [ ] Migración: tablas `empleados`, `asistencias`, `vacaciones`, `permisos`, `liquidaciones`
- [ ] CRUD empleados con datos previsionales chilenos (AFP, ISAPRE, Mutual)
- [ ] Control de asistencia: marcación desde POS o panel web
- [ ] Cálculo automático de horas trabajadas, atrasos, horas extra
- [ ] Flujo solicitud → aprobación de vacaciones y permisos
- [ ] Calendario laboral con feriados chilenos
- [ ] Generación de liquidación mensual con descuentos legales (AFP, salud, mutual, SIS, impuesto único)
- [ ] Dashboard RRHH: asistencia hoy, solicitudes pendientes, vencimientos contrato
- [ ] Reportes: ausentismo, horas extra, resumen mensual

**Checklist de verificación:**
- [ ] Empleado puede marcar entrada/salida desde pantalla POS
- [ ] Sistema calcula atrasos automáticamente vs horario configurado
- [ ] Solicitud de vacaciones notifica a admin y puede aprobarse en un clic
- [ ] Liquidación genera correctamente los descuentos previsionales

---

### HITO 15: Módulo de Reclutamiento (2 semanas)

**Objetivo**: Portal de ofertas y gestión de postulantes

**Tareas:**
- [ ] Migración: tablas `ofertas_empleo`, `postulaciones`, `entrevistas`
- [ ] CRUD ofertas con editor completo
- [ ] Página pública de postulación por oferta (URL amigable por tenant)
- [ ] Formulario de postulación con subida de CV
- [ ] Pipeline de candidatos (recibida → preseleccionada → entrevista → contratada)
- [ ] Agenda de entrevistas con recordatorios
- [ ] Acción "Contratar": convierte postulante en empleado (pre-llena datos)
- [ ] Notificaciones automáticas por email/WA a postulantes en cada etapa

**Checklist de verificación:**
- [ ] Postulante puede completar formulario público sin login
- [ ] Admin ve todos los postulantes por oferta con filtros
- [ ] Al contratar, datos del postulante pasan al módulo RRHH automáticamente

---

### HITO 16: Marketing QR (2 semanas)

**Objetivo**: Generación y tracking de códigos QR para campañas

**Tareas:**
- [ ] Migración: tablas `campanas_marketing`, `qr_campanas`, `escaneos_qr`
- [ ] Generador de QR dinámico (librería PHP + logo del tenant)
- [ ] Landing page pública personalizada por QR con diseño del tenant
- [ ] Tipos de acción: descuento %, descuento $, 2x1, abrir WhatsApp, encuesta
- [ ] Registro de escaneos con IP, device, timestamp
- [ ] Tracking de conversiones: escaneo → compra (venta con código QR aplicado)
- [ ] Dashboard de métricas: escaneos, conversiones, tasa, descuentos otorgados
- [ ] Descarga de QR en PNG/SVG en distintos tamaños
- [ ] Integración POS: cajero puede aplicar descuento QR al cobrar

**Checklist de verificación:**
- [ ] QR generado apunta a landing pública del tenant
- [ ] Escaneo registra datos sin login del cliente
- [ ] Descuento QR se aplica correctamente en POS al ingresar código
- [ ] Métricas muestran conversiones reales vinculadas a ventas

---

### HITO 17: Dashboard Ejecutivo Unificado + API Pública (3 semanas)

**Objetivo**: Vista integrada de todos los módulos y API para integraciones externas

**Tareas:**
- [ ] Dashboard ejecutivo con KPIs cruzados: ventas + WA + RRHH + delivery
- [ ] Widget de alertas unificado (stock, rentas, RRHH, SII)
- [ ] Centro de notificaciones en tiempo real (SSE)
- [ ] API REST documentada (OpenAPI / Swagger) para integraciones externas
- [ ] Webhooks salientes configurables (ej: notificar a otro sistema al crear venta)
- [ ] Reportes exportables: Excel, PDF, CSV para todos los módulos
- [ ] Optimización de performance: índices DB, cache de reportes

---

### HITO 19: Módulo M31 — Venta de Software SaaS (4 semanas)

**Objetivo**: BenderAnd opera desde sí mismo su propio negocio SaaS.
La empresa que vende BenderAnd usa BenderAnd para gestionar tenants,
pipeline comercial, billing recurrente y métricas de negocio.
**Este módulo es el primer tenant en producción del sistema.**

> **Prerrequisito**: Hitos 9 y 10 completos (integración WA + SII).
> El onboarding de prospectos ocurre 100% vía WhatsApp bot.
> La facturación mensual usa M20 (SII) para emitir facturas automáticas.

**Semana 1 — Base de datos y modelos**
- [ ] Migración: `saas_planes`, `saas_clientes`, `saas_pipeline`
- [ ] Migración: `saas_actividades`, `saas_cobros`, `saas_metricas`, `saas_demos`
- [ ] Modelos Eloquent: `SaasCliente`, `SaasPlan`, `SaasPipeline`, `SaasCobro`
- [ ] Seeder: planes Básico ($39k), Pro ($89k), Enterprise ($189k) con módulos por plan
- [ ] Seeder: tenant propio de BenderAnd como primer cliente del sistema
  ```bash
  php artisan make:migration create_saas_planes_table --path=database/migrations/tenant
  php artisan make:migration create_saas_clientes_table --path=database/migrations/tenant
  php artisan make:migration create_saas_pipeline_table --path=database/migrations/tenant
  php artisan make:migration create_saas_cobros_table --path=database/migrations/tenant
  php artisan make:migration create_saas_metricas_table --path=database/migrations/tenant
  php artisan make:migration create_saas_actividades_table --path=database/migrations/tenant
  php artisan make:migration create_saas_demos_table --path=database/migrations/tenant
  php artisan make:model Tenant/SaasCliente
  php artisan make:model Tenant/SaasPlan
  php artisan make:model Tenant/SaasPipeline
  php artisan make:model Tenant/SaasCobro
  ```

**Semana 2 — API y lógica de negocio**
- [ ] `SaasController` con CRUD completo de tenants/clientes
- [ ] `SaasPipelineController` con avance de etapas y registro de actividades
- [ ] `SaasMetricasController` con cálculo MRR, ARR, churn, ARPU, cohorts
- [ ] `SaasCobrosController` con registro manual y generación de ciclo mensual
- [ ] `SaasDemosController` con agenda y resultado de demos
- [ ] Service `SaasBillingService` — genera cobros, emite facturas vía SiiService
- [ ] Service `SaasMetricasService` — calcula y persiste métricas diarias
- [ ] Policy: ejecutivos solo ven sus propios prospectos; admin ve todo

**Semana 3 — Jobs automáticos y WhatsApp onboarding**
- [ ] Job `GenerarCobrosRecurrentes` — corre el 1° de cada mes a las 08:00
  ```bash
  php artisan make:job GenerarCobrosRecurrentes
  # bootstrap/app.php:
  # Schedule::job(new GenerarCobrosRecurrentes)->monthlyOn(1, '08:00');
  ```
- [ ] Job `AlertaTrialVencimiento` — WA al prospecto día 25 del trial
- [ ] Job `AlertaMorosos` — WA de cobro a tenants vencidos +5 días
- [ ] Job `SuspenderMorosos` — suspende tenants +30 días sin pago
- [ ] Job `ActualizarMetricasMRR` — diario a las 00:30
- [ ] Job `SeguimientoTrialDia7` — WA de engagement en día 7
- [ ] Job `EmitirFacturasDelMes` — emite DTE por cada cobro del ciclo
- [ ] Job `ReporteEjecutivos` — resumen semanal lunes 08:00 al equipo comercial
- [ ] Webhook WhatsApp `saas-onboarding`: recibe mensaje del bot → crea trial + schema tenant
  ```php
  // routes/web.php
  Route::post('/webhook/wa/saas-onboarding', [SaasOnboardingController::class, 'handle']);
  ```

**Semana 4 — UI POS y dashboards**
- [ ] POS view: `Panel de Tenants` (lista con alertas, búsqueda, filtros por estado/plan)
- [ ] POS view: `Pipeline Kanban` (arrastrar o avanzar etapa con botón)
- [ ] POS view: `Ficha CRM del tenant` (uso, billing, historial, acciones rápidas)
- [ ] Admin view: `Dashboard MRR` (gráfico 12 meses, distribución por plan/rubro)
- [ ] Admin view: `Gestión de Planes` (editar precios, módulos incluidos, addons)
- [ ] Admin view: `Cobros del mes` (pendientes, vencidos, emitir factura manual)
- [ ] Admin view: `Demos agendadas` (calendario del equipo comercial)
- [ ] Integrar con `rubros_config`: preset `saas_provider` aplica módulos M31 + M20 + M21 + M23

**Checklist de verificación:**
- [ ] Un prospecto escribe al bot → se crea en `saas_pipeline` automáticamente
- [ ] Bot crea cuenta trial → schema tenant creado, preset restaurante/dentista/etc. aplicado
- [ ] Trial de 30 días: alertas WA en día 7, 25 y vencimiento
- [ ] Cobro del mes 1 generado automáticamente el día 1
- [ ] Factura electrónica emitida vía SII para cada cobro pagado
- [ ] Tenant moroso +30 días queda suspendido automáticamente (UI y API devuelven 402)
- [ ] MRR calculado correctamente: suma de `saas_cobros` activos del mes
- [ ] Churn calculado: cancelados / activos inicio del mes × 100
- [ ] Ejecutivo ve solo sus propios prospectos en pipeline
- [ ] Admin ve métricas globales y puede reasignar ejecutivos
- [ ] Preset `saas_provider` no muestra: stock, delivery, comandas, timers, recetas

---

### HITO 18: Testing, Seguridad y Despliegue (2 semanas)

**Tareas:**
- [ ] Tests de integración end-to-end (flujo completo por rubro)
- [ ] Tests de carga: concurrencia WhatsApp (1000 mensajes/min)
- [ ] Auditoría de seguridad: SQL injection, XSS, CSRF, tenant isolation
- [ ] Certificación SII en ambiente producción para todos los tipos de DTE
- [ ] Runbook de despliegue actualizado
- [ ] Scripts de migración para tenants existentes
- [ ] Documentación de usuario por módulo
- [ ] Capacitación (video tutoriales por rubro)

---

## PARTE 14: CONFIGURACIÓN DOCKER — PRODUCCIÓN

### docker-compose.prod.yml

```yaml
version: '3.8'

services:
  # ─── ERP Core ────────────────────────────────────────────────
  benderand_app:
    build:
      context: ./backend-laravel
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    environment:
      APP_ENV: production
      APP_KEY: ${APP_KEY}
      DB_CONNECTION: pgsql
      DB_HOST: benderand_postgres
      DB_PORT: 5432
      DB_DATABASE: benderand_erp
      DB_USERNAME: ${DB_USER}
      DB_PASSWORD: ${DB_PASS}
      REDIS_HOST: benderand_redis
      QUEUE_CONNECTION: redis
      JWT_SHARED_SECRET: ${JWT_SHARED_SECRET}
      SII_AMBIENTE: produccion
    volumes:
      - ./backend-laravel:/var/www
      - storage_data:/var/www/storage/app/public
    networks:
      - benderand_net
    depends_on:
      - benderand_postgres
      - benderand_redis

  # ─── Laravel Horizon (Queue Worker) ─────────────────────────────
  benderand_horizon:
    build:
      context: ./backend-laravel
      dockerfile: docker/Dockerfile.prod
    restart: unless-stopped
    command: php artisan horizon
    volumes:
      - ./backend-laravel:/var/www
    networks:
      - benderand_net
    depends_on:
      - benderand_app

  # ─── WhatsApp Bot ────────────────────────────────────────────
  moteland_api:
    build:
      context: ./whatsapp-bot-node
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    environment:
      - NODE_ENV=production
      - DATABASE_URL=postgresql://${PG_USER}:${PG_PASS}@moteland_postgres:5432/moteland
      - REDIS_URL=redis://benderand_redis:6379
      - JWT_SHARED_SECRET=${JWT_SHARED_SECRET}  # mismo secret que Laravel
      - ERP_BASE_URL=http://benderand_app
      - OPENAI_API_KEY=${OPENAI_API_KEY}
    networks:
      - benderand_net
    depends_on:
      - moteland_postgres
      - benderand_redis

  # ─── Bases de Datos ──────────────────────────────────────────
  benderand_postgres:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: benderand_erp
      POSTGRES_USER: ${DB_USER}
      POSTGRES_PASSWORD: ${DB_PASS}
    volumes:
      - postgres_erp_data:/var/lib/postgresql/data
      - ./docker/postgres/init:/docker-entrypoint-initdb.d
    networks:
      - benderand_net

  # ─── Cache y Colas ───────────────────────────────────────────
  benderand_redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASS}
    volumes:
      - redis_data:/data
    networks:
      - benderand_net

  # ─── Dashboard WA (React) ────────────────────────────────────
  moteland_dashboard:
    build:
      context: ./whatsapp-bot-node/dashboard
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    networks:
      - benderand_net

  # ─── Proxy Unificado ─────────────────────────────────────────
  benderand_proxy:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
      - ./nginx/ssl:/etc/nginx/ssl
      - ./backend-laravel/public:/var/www/public:ro
      - storage_data:/var/www/storage/app/public:ro
    networks:
      - benderand_net
    depends_on:
      - benderand_app
      - moteland_api
      - moteland_dashboard

networks:
  benderand_net:
    driver: bridge

volumes:
  postgres_erp_data:
  postgres_erp_data:
  redis_data:
  uploads:
```

### nginx/conf.d/erp.benderand.cl.conf
```nginx
server {
    listen 443 ssl;
    server_name api.benderand.cl;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;

    # Laravel (PHP-FPM)
    root /var/www/public;

    location ~ \.php$ {
        fastcgi_pass benderand_app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /storage/ {
        alias /var/www/storage/app/public/;
        expires 7d;
    }
}
```

---

## PARTE 15: CHECKLISTS DE VERIFICACIÓN POR HITO

### Checklist Pre-Hito (antes de comenzar cualquier hito)
- [ ] Backup completo de base de datos (master + todos los schemas)
- [ ] Branch de desarrollo creado en git (`feature/hito-X`)
- [ ] Variables de entorno actualizadas en `.env.example`
- [ ] Revisión de migraciones pendientes aplicadas en staging

### Checklist Post-Hito (antes de merge a main)
- [ ] Todos los endpoints nuevos documentados
- [ ] Tests manuales completados por cada flujo principal
- [ ] Sin errores en logs de Laravel 11 y Node.js
- [ ] Performance: ninguna query > 500ms
- [ ] Tenant isolation: verificar que datos de un tenant no son accesibles desde otro
- [ ] Migración de base de datos probada en ambiente limpio

---

## PARTE 16: PRÓXIMOS PASOS INMEDIATOS

### Semana 1 — Setup base Laravel 11 + Integración WA

**Día 1–2: Instalar y configurar el proyecto Laravel 11**
```bash
# Crear proyecto Laravel 11
composer create-project laravel/laravel backend-laravel "^11.0"
cd backend-laravel

# Multi-tenancy
composer require stancl/tenancy

# Auth API
composer require laravel/sanctum

# Queue dashboard
composer require laravel/horizon

# WebSocket (Broadcasting)
composer require laravel/reverb

# JWT bridge con Node.js
composer require firebase/php-jwt

# SII / LibreDTE
composer require sasco/libredte-lib-core

# Generador de QR
composer require endroid/qr-code

# Publicar configs
php artisan tenancy:install
php artisan sanctum:install
php artisan horizon:install
php artisan reverb:install

# Configurar driver PostgreSQL en .env
DB_CONNECTION=pgsql
TENANCY_DATABASE_MANAGER=pgsql_schema
```

**Día 3–4: Migraciones centrales (schema public)**
```bash
# Crear migraciones para schema público (tenants, planes, proveedores_globales)
php artisan make:migration create_tenants_table
php artisan make:migration create_planes_table
php artisan make:migration create_proveedores_globales_table

# Ejecutar en schema public
php artisan migrate

# Seed proveedores globales (Coca-Cola, CCU, Nestlé, Soprole, etc.)
php artisan db:seed --class=ProveedoresGlobalesSeeder
php artisan db:seed --class=PlanesSeeder
```

**Día 5: Migraciones tenant y JWT Bridge**
```bash
# Migraciones tenant (se ejecutan por schema al crear empresa)
php artisan tenants:migrate --path=database/migrations/tenant

# Crear servicio JWT Bridge
php artisan make:service JwtBridgeService

# Crear middleware de rol (usando Gates de Laravel)
php artisan make:middleware RoleMiddleware

# Crear primer tenant de prueba
php artisan tinker
>>> $tenant = App\Models\Central\Tenant::create(['id' => 'empresa-demo', 'name' => 'Demo S.A.']);
>>> $tenant->domains()->create(['domain' => 'demo.benderand.cl']);
```

**Día 6–7: Endpoints internos para bot Node.js**
```bash
# Crear controlador con rutas internas (con middleware erpAuth que valida JWT_SHARED_SECRET)
php artisan make:controller Api/Internal/ErpBridgeController

# Rutas en routes/api.php (grupo 'internal', no usa tenancy middleware — usa JWT bridge)
Route::prefix('internal')->middleware('erp.bridge')->group(function () {
    Route::get('productos/stock', [ErpBridgeController::class, 'stock']);
    Route::get('clientes/buscar', [ErpBridgeController::class, 'buscarCliente']);
    Route::post('ventas/remota', [ErpBridgeController::class, 'crearVentaRemota']);
    Route::get('citas/disponibilidad', [ErpBridgeController::class, 'disponibilidad']);
});
```

---

### Semana 2 — Módulo Compras + SII básico

**Módulo Compras (Hito 11):**
```bash
# Migraciones tenant para compras
php artisan make:migration create_proveedores_tenant_table --path=database/migrations/tenant
php artisan make:migration create_ordenes_compra_table --path=database/migrations/tenant
php artisan make:migration create_items_orden_compra_table --path=database/migrations/tenant
php artisan make:migration create_recepciones_compra_table --path=database/migrations/tenant
php artisan make:migration create_items_recepcion_table --path=database/migrations/tenant

# Modelos Eloquent
php artisan make:model Tenant/OrdenCompra
php artisan make:model Tenant/ProveedorTenant
php artisan make:model Tenant/Recepcion

# Controladores API
php artisan make:controller Api/Tenant/OrdenCompraController --api
php artisan make:controller Api/Tenant/ProveedorController --api
php artisan make:controller Api/Tenant/RecepcionController --api

# Job para OC automática por stock mínimo
php artisan make:job StockAlertJob
# Registrar en app/Console/Kernel.php o Schedule (Laravel 11: bootstrap/app.php):
# Schedule::job(new StockAlertJob)->dailyAt('07:00');
```

**Módulo SII (Hito 10):**
```bash
# Migración DTE
php artisan make:migration create_dte_emitidos_table --path=database/migrations/tenant
php artisan make:migration create_config_sii_table --path=database/migrations/tenant

# Service y Job (DTE es async — SII puede tardar 5-30 seg)
php artisan make:service SiiService
php artisan make:job EmitirDteJob

# Primer test en ambiente certificación:
php artisan tinker
>>> app(App\Services\SiiService::class)->emitirBoletaPrueba();
```

---

### Semana 3 — RRHH básico + QR + Dashboard

**RRHH:**
```bash
php artisan make:migration create_empleados_table --path=database/migrations/tenant
php artisan make:migration create_asistencias_table --path=database/migrations/tenant
php artisan make:migration create_vacaciones_table --path=database/migrations/tenant
php artisan make:migration create_permisos_table --path=database/migrations/tenant
php artisan make:migration create_liquidaciones_table --path=database/migrations/tenant

php artisan make:model Tenant/Empleado
php artisan make:model Tenant/Asistencia
php artisan make:controller Api/Tenant/EmpleadoController --api
php artisan make:controller Api/Tenant/AsistenciaController --api
```

**Marketing QR:**
```bash
php artisan make:migration create_qr_campanas_table --path=database/migrations/tenant
php artisan make:migration create_escaneos_qr_table --path=database/migrations/tenant
php artisan make:service QrGenerator     # usa endroid/qr-code
php artisan make:controller Api/Tenant/QrCampanaController --api
# Ruta pública sin auth:
# Route::get('/qr/{uuid}', [QrPublicoController::class, 'landing'])->name('qr.landing');
```

**Dashboard ejecutivo con Broadcasting (Reverb):**
```bash
# Eventos en tiempo real
php artisan make:event VentaConfirmada
php artisan make:event EntregaActualizada
php artisan make:listener NotificarBotWhatsApp --event=VentaConfirmada

# Iniciar Reverb en desarrollo
php artisan reverb:start

# Iniciar Horizon en desarrollo
php artisan horizon
```

---

### Comandos útiles de referencia

```bash
# Correr migraciones solo en un tenant específico
php artisan tenants:migrate --tenants=uuid-del-tenant

# Correr migraciones en todos los tenants
php artisan tenants:migrate

# Ejecutar un Job manualmente (prueba)
php artisan tinker
>>> dispatch(new App\Jobs\EmitirDteJob($venta));

# Ver colas en tiempo real (Horizon dashboard en /horizon)
php artisan horizon:status

# Limpiar caches en producción post-deploy
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

---

*BenderAnd ERP — Plan de Desarrollo Completo v2.0 · Marzo 2026 · Confidencial*
*Stack: PHP 8.2 · Laravel 11 · stancl/tenancy v3 · PostgreSQL 16 · Redis 7 · Node.js · BullMQ · Socket.io · React*
*Multi-tenant: schema por empresa en PostgreSQL · Auth: Laravel Sanctum + JWT Bridge compartido*
