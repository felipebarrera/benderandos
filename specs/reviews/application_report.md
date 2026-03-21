# Reporte de Estructura de Aplicación — BenderAndos 🚀

Última actualización: **2026-03-17**

## 🏗️ Arquitectura General

BenderAndos es una aplicación **multi-tenant** basada en subdominios (Stancl/Tenancy):
- **Dominio Central**: Gestión de métricas, facturación, tenants y Spider QA (`localhost`, `benderand.cl`).
- **Tenants**: Cada empresa tiene su esquema de BD propio (`{slug}.localhost`).
- **238 rutas** registradas en total.

---

## 📂 Archivos de Rutas

| Archivo | Propósito |
|---|---|
| [web.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/web.php) | Panel Central web (login, dashboard, spider, tenants, billing) |
| [api.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/api.php) | API Central (Sanctum, billing/modulos, Spider QA endpoints) |
| [central.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/central.php) | API SuperAdmin (dashboard, metrics, tenants CRUD) |
| [tenant.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/tenant.php) | Todas las rutas de tenant (425 líneas: ventas, clientes, RRHH, bot, delivery, etc.) |
| [webhook.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/webhook.php) | Webhooks externos (WhatsApp onboarding) |

---

## 🌐 Endpoints Principales

### Panel Central (Web — `auth:super_admin`)
| Método | Endpoint | Propósito |
|---|---|---|
| `GET` | `/central/login` | Login SA |
| `GET` | `/central` | Dashboard de Métricas |
| `GET` | `/central/tenants` | Gestión de Tenants |
| `GET` | `/central/billing` | Facturación |
| `GET` | `/central/spider` | Spider QA v3 |
| `POST` | `/central/spider/token` | Genera token SA para Spider |

### API Spider QA (`auth:super_admin`)
| Método | Endpoint | Propósito |
|---|---|---|
| `GET` | `/api/spider/probe` | Test de conectividad HTTP |
| `GET` | `/api/spider/db-check` | Chequeo de salud de DB |
| `POST` | `/api/spider/sync` | Sincronización de rutas |
| `GET/POST` | `/api/spider/tests` | Leer/guardar `spider_tests.json` |

### API SuperAdmin (`auth:sanctum` — `routes/central.php`)
| Método | Endpoint | Propósito |
|---|---|---|
| `POST` | `/api/superadmin/login` | Login API |
| `GET` | `/api/superadmin/dashboard` | Dashboard JSON |
| `GET` | `/api/superadmin/tenants` | Lista de tenants |
| `GET` | `/api/superadmin/billing/*` | Suscripciones y pagos |

---

## 🏢 Tenants Demo (8 total)

| Industria | Slug | Dominio | Módulos clave |
|---|---|---|---|
| — | `df21b4b0-*` | `demo.localhost` | Original |
| Legal | `demo-legal` | `demo-legal.localhost` | Agenda, Honorarios, SII, RRHH |
| Pádel | `demo-padel` | `demo-padel.localhost` | Rentas, Agenda, Bot, Membresías |
| Motel | `demo-motel` | `demo-motel.localhost` | Rentas, Recursos |
| Abarrotes | `demo-abarrotes` | `demo-abarrotes.localhost` | Stock, Deudas, Encargos, Bot, Compras, SII |
| Ferretería | `demo-ferreteria` | `demo-ferreteria.localhost` | Stock, Compras, Inventario, Delivery, SII, Bot |
| Médico | `demo-medico` | `demo-medico.localhost` | Agenda, Honorarios, SII, RRHH |
| SaaS | `demo-saas` | `demo-saas.localhost` | SII, RRHH, Reclutamiento, QR, Portal |

> [!TIP]
> `demo-ferreteria` es el tenant más completo para pruebas con Spider QA.

---

## 🕷️ Spider QA v3

- **181 tests** configurados en `spider_tests.json`
- Fases: Auth → DB Check → HTTP Checks (SA + Tenant) → UI Checks
- Sincronización automática desde `php artisan route:list`
- Archivos clave:
  - [SpiderController.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Http/Controllers/SpiderController.php)
  - [spider.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/central/spider.blade.php)
  - [sync_spider_tests.sh](file:///home/master/trabajo/proyectos/src/benderandos/tests/sync_spider_tests.sh)

---

## 🔐 Autenticación

| Guard | Modelo | Uso |
|---|---|---|
| `super_admin` | `SuperAdmin` (conexión `central`) | Panel Central web + Spider |
| `sanctum` | `User` / `SuperAdmin` | API tokens (central + tenant) |
| `auth.bot` | `InternalBotAuth` middleware | Rutas de bot internas |

---

## 🗄️ Middleware Personalizado

| Alias | Clase | Propósito |
|---|---|---|
| `auth.bot` | `InternalBotAuth` | Auth para rutas de bot/internas |
| `jwt.bridge` | `JwtBridgeMiddleware` | Puente JWT para impersonación |
| `module` | `CheckModuleAccess` | Verifica módulo activo en plan del tenant |
| — | `CheckRole` | Control de acceso por rol (`admin`, `super_admin`) |
| — | `LogUnauthorizedRequests` | Log de errores 4xx/5xx para QA |

---

## 📊 Seeders

| Seeder | Propósito |
|---|---|
| [DemoTenantsSeeder](file:///home/master/trabajo/proyectos/src/benderandos/database/seeders/DemoTenantsSeeder.php) | Crea 7 tenants demo con dominios |
| [TenantDemoDataSeeder](file:///home/master/trabajo/proyectos/src/benderandos/database/seeders/TenantDemoDataSeeder.php) | Datos por industria: admin, clientes, productos, RRHH, SII, bot |
