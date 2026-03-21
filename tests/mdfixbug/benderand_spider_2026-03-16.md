# BenderAnd Spider QA v2 — Reporte
**Generado:** 2026-03-16 8:24:39 PM
**SuperAdmin:** http://localhost:8000 | **Tenant:** http://demo.localhost:8000
**Modo HTTP:** proxy NO disponible — instalar SpiderController

---

## Resumen

| | |
|---|---|
| Total checks | 23 |
| ✅ PASS | 13 |
| ❌ FAIL | 6 |
| ⚠️ WARN | 4 |
| 🐛 Bugs | 6 |
| Tasa éxito | 57% |

---

## Stack
- Laravel 11 + stancl/tenancy v3 · PostgreSQL 16 · Docker · Sanctum JWT
- App container: `benderandos_app` ruta: `/app`
- DB container: `benderandos_pg` user: `benderand` db: `benderand`

## Comandos Docker
```bash
docker exec benderandos_app php artisan [cmd]
docker exec benderandos_app php artisan tinker --execute="[código]"
docker exec benderandos_pg psql -U benderand -d benderand -c "[SQL]"
docker exec benderandos_app tail -200 /app/storage/logs/laravel.log
# Correr diagnóstico tenant (fix automático):
bash tests/diagnose_tenant.sh
# Regenerar credenciales:
bash tests/gen_credentials.sh
```

---

## Instrucciones para el Agente (Antigravity)
Para cada bug: 1) identifica archivo exacto 2) fix con código antes/después 3) verifica 4) cierra en DB:
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c "UPDATE bug_reports SET estado='resuelto',resuelto_en=NOW(),fix_commit='HASH' WHERE bug_id='BUG-ID';"
```

---

## Bugs Detectados (6)

### 🟠 BUG-SP-001 — Endpoint tenant falla: Dashboard

| Campo | Valor |
|---|---|
| **Tipo** | `E-HTTP` |
| **Capa** | `api` |
| **Prioridad** | alto |
| **URL** | `http://demo.localhost:8000/api/dashboard` |
| **Detectado** | 2026-03-16T23:24:35.348Z |

**Detalle:** HTTP 500

**Fix:**
```bash
Verificar routes/tenant.php y migraciones del tenant
```

---

### 🟠 BUG-SP-002 — Endpoint tenant falla: Productos

| Campo | Valor |
|---|---|
| **Tipo** | `E-HTTP` |
| **Capa** | `api` |
| **Prioridad** | alto |
| **URL** | `http://demo.localhost:8000/api/productos` |
| **Detectado** | 2026-03-16T23:24:35.458Z |

**Detalle:** HTTP 500

**Fix:**
```bash
Verificar routes/tenant.php y migraciones del tenant
```

---

### 🟠 BUG-SP-003 — Endpoint tenant falla: Ventas

| Campo | Valor |
|---|---|
| **Tipo** | `E-HTTP` |
| **Capa** | `api` |
| **Prioridad** | alto |
| **URL** | `http://demo.localhost:8000/api/ventas` |
| **Detectado** | 2026-03-16T23:24:35.537Z |

**Detalle:** HTTP 500

**Fix:**
```bash
Verificar routes/tenant.php y migraciones del tenant
```

---

### 🟠 BUG-SP-004 — Endpoint tenant falla: Clientes

| Campo | Valor |
|---|---|
| **Tipo** | `E-HTTP` |
| **Capa** | `api` |
| **Prioridad** | alto |
| **URL** | `http://demo.localhost:8000/api/clientes` |
| **Detectado** | 2026-03-16T23:24:35.612Z |

**Detalle:** HTTP 500

**Fix:**
```bash
Verificar routes/tenant.php y migraciones del tenant
```

---

### 🔴 BUG-SP-005 — Ruta sin auth: http://demo.localhost:8000/api/dashboard

| Campo | Valor |
|---|---|
| **Tipo** | `E-PERM` |
| **Capa** | `laravel` |
| **Prioridad** | critico |
| **URL** | `http://demo.localhost:8000/api/dashboard` |
| **Detectado** | 2026-03-16T23:24:35.817Z |

**Detalle:** HTTP 500 sin token

**Fix:**
```bash
middleware auth:sanctum faltante
```

---

### 🔴 BUG-SP-006 — Ruta sin auth: http://demo.localhost:8000/api/ventas

| Campo | Valor |
|---|---|
| **Tipo** | `E-PERM` |
| **Capa** | `laravel` |
| **Prioridad** | critico |
| **URL** | `http://demo.localhost:8000/api/ventas` |
| **Detectado** | 2026-03-16T23:24:35.895Z |

**Detalle:** HTTP 500 sin token

**Fix:**
```bash
middleware auth:sanctum faltante
```

---

## Todos los Checks (23)

✅ **SA login exitoso** — token OK · HTTP 200
   `http://localhost:8000/api/superadmin/login`

✅ **SA: password incorrecto → 422** — Seguridad OK

✅ **Tenant login exitoso** — token OK · HTTP 200
   `http://demo.localhost:8000/api/login`

✅ **SA API: SA Dashboard** — HTTP 200
   `http://localhost:8000/api/superadmin/dashboard`

✅ **SA API: SA Tenants** — HTTP 200
   `http://localhost:8000/api/superadmin/tenants`

✅ **SA API: SA Plan Módulos** — HTTP 200
   `http://localhost:8000/api/central/plan/modulos`

❌ **Tenant API: Dashboard** — Esperado 200 · obtenido 500
   `http://demo.localhost:8000/api/dashboard`
   → Verificar routes/tenant.php

❌ **Tenant API: Productos** — Esperado 200 · obtenido 500
   `http://demo.localhost:8000/api/productos`
   → Verificar routes/tenant.php

❌ **Tenant API: Ventas** — Esperado 200 · obtenido 500
   `http://demo.localhost:8000/api/ventas`
   → Verificar routes/tenant.php

❌ **Tenant API: Clientes** — Esperado 200 · obtenido 500
   `http://demo.localhost:8000/api/clientes`
   → Verificar routes/tenant.php

❌ **Sin token: Tenant dashboard NO protegido** — HTTP 500 — debe ser 401
   `http://demo.localhost:8000/api/dashboard`
   → Agregar middleware auth:sanctum en routes/tenant.php

❌ **Sin token: Tenant ventas NO protegido** — HTTP 500 — debe ser 401
   `http://demo.localhost:8000/api/ventas`
   → Agregar middleware auth:sanctum en routes/tenant.php

✅ **Sin token: SA dashboard protegido** — HTTP 401
   `http://localhost:8000/api/superadmin/dashboard`

✅ **Cross-tenant bloqueado** — Tenant token → SA: HTTP 401

✅ **DB: super_admins tiene registros** — 1 registros · Fix: php artisan db:seed --class=SuperAdminSeeder

✅ **DB: plan_modulos con datos (H19)** — 32 módulos · Fix: Ejecutar migración H19

✅ **DB: Tabla tenants accesible** — 1 tenants registrados

✅ **DB: Tabla bug_reports existe** — Sistema de tracking de bugs · Fix: php artisan migrate

⚠️ **Tenant /login — proxy no disponible** — proxy no disponible — instalar SpiderController (BUG-010) — instalar SpiderController ver BUG-010
   `http://demo.localhost:8000/login`
   → bash tests/diagnose_tenant.sh verifica /etc/hosts y acceso

⚠️ **Tenant /admin/login — proxy no disponible** — Instalar SpiderController
   `http://demo.localhost:8000/admin/login`

⚠️ **Tenant dashboard sin sesión — proxy no disponible** — Instalar SpiderController
   `http://demo.localhost:8000/admin/dashboard`

⚠️ **SA UI: no verificable** — NetworkError when attempting to fetch resource.
   `http://localhost:8000/superadmin`

✅ **Spider QA instalado** — HTTP 200
   `http://localhost:8000/superadmin/spider`

---

## Nota sobre verificaciones HTTP

El spider usa el proxy `/api/spider/probe` del backend para verificar códigos HTTP sin CORS.
**PROXY NO DISPONIBLE** — instalar SpiderController (BUG-010 en el MD maestro).
Sin proxy, las verificaciones de rutas públicas/tenant no pueden obtener el HTTP code real desde el browser.
Las verificaciones de Auth y DB API siempre funcionan ya que van directo a la misma origin.

---

## Log
```
[23:24:33] Spider v2 iniciado
[23:24:33] ── AUTH ──
[23:24:34] SA token OK
[23:24:34] Tenant token OK
[23:24:34] ── API ──
[23:24:35] SA /api/superadmin/dashboard: 200
[23:24:35] SA /api/superadmin/tenants: 200
[23:24:35] SA /api/central/plan/modulos: 200
[23:24:35] ── ROLES ──
[23:24:36] ── DB ──
[23:24:36] ── TENANT ──
[23:24:38] ── UI ──
[23:24:39] Terminado: 13 PASS · 6 FAIL · 6 bugs
```

---
*BenderAnd Spider H22/H23 v2 · 2026-03-16*
