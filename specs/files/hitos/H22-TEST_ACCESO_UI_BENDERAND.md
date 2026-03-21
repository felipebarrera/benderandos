# 🧪 BenderAnd ERP — Pruebas de Acceso UI (Credenciales Verificadas)
**Herramienta:** [lightpanda browser](https://github.com/lightpanda-io/browser)  
**Fecha:** Marzo 2026  
**Estado:** ✅ URLs y credenciales confirmadas por el equipo dev

---

## 🔑 URLs y Credenciales Base

| Panel | URL | Usuario | Contraseña |
|---|---|---|---|
| **SuperAdmin** | `http://localhost:8000/superadmin` | `admin@benderand.cl` | `password` |
| **Tenant Admin** | `http://demo.localhost:8000/admin/login` | `admin@benderand.cl` | `admin1234` |

> **Importante:** El subdomain `demo.localhost` requiere que tu `/etc/hosts` tenga:
> ```
> 127.0.0.1  demo.localhost
> ```

---

## ⚙️ Setup — Lightpanda

```bash
# Verificar instalación
lightpanda --version

# Test rápido de conectividad antes de empezar
lightpanda fetch --url "http://localhost:8000/superadmin" --dump-headers
lightpanda fetch --url "http://demo.localhost:8000/admin/login" --dump-headers
# Ambos deben devolver HTTP 200 — si devuelven 404, revisar /etc/hosts y que el servidor esté corriendo
```

---

## 📋 PRUEBA 1 — SuperAdmin (Panel Global BenderAnd)

**URL base:** `http://localhost:8000`  
**Ruta login:** `/superadmin`  
**API:** `/api/superadmin/login` (prefijo `/api` ya corregido en `bootstrap/app.php`)

### Credenciales

| Usuario | Email | Password | Rol |
|---|---|---|---|
| Admin BenderAnd | `admin@benderand.cl` | `password` | `super_admin` |

### Casos de prueba

```bash
# TC-1.1 — Login superadmin exitoso
lightpanda fetch \
  --url "http://localhost:8000/api/superadmin/login" \
  --method POST \
  --header "Content-Type: application/json" \
  --body '{"email":"admin@benderand.cl","password":"password"}' \
  2>&1 | tee tests/results/TC-1.1.log
# ✅ Esperado: HTTP 200 + token en respuesta JSON

# TC-1.2 — Acceso al dashboard global post-login
# (usar cookie/token obtenido en TC-1.1)
lightpanda fetch \
  --url "http://localhost:8000/superadmin/dashboard" \
  --cookie "session=$COOKIE_SUPERADMIN" \
  --dump-dom \
  2>&1 | tee tests/results/TC-1.2.log
# ✅ Esperado: DOM contiene pipeline de tenants, MRR, botón "Impersonar"

# TC-1.3 — Password incorrecto no da pistas
lightpanda fetch \
  --url "http://localhost:8000/api/superadmin/login" \
  --method POST \
  --header "Content-Type: application/json" \
  --body '{"email":"admin@benderand.cl","password":"wrongpass"}' \
  2>&1 | tee tests/results/TC-1.3.log
# ✅ Esperado: HTTP 401 — mensaje genérico, no expone si el email existe

# TC-1.4 — Sin sesión redirige a login
lightpanda fetch \
  --url "http://localhost:8000/superadmin/dashboard" \
  --dump-headers \
  2>&1 | tee tests/results/TC-1.4.log
# ✅ Esperado: HTTP 302 redirect a /superadmin
```

---

## 📋 PRUEBA 2 — Tenant Admin (Panel por Empresa)

**URL base:** `http://demo.localhost:8000`  
**Ruta login:** `/admin/login` (antes era 404 — ya corregido con redirect desde `/admin/login`)

### Credenciales

| Usuario | Email | Password | Rol |
|---|---|---|---|
| Admin Tenant Demo | `admin@benderand.cl` | `admin1234` | `admin` |

### Casos de prueba

```bash
# TC-2.1 — La ruta /admin/login responde (verificar fix del 404)
lightpanda fetch \
  --url "http://demo.localhost:8000/admin/login" \
  --dump-headers \
  2>&1 | tee tests/results/TC-2.1.log
# ✅ Esperado: HTTP 200 — ya NO debe devolver 404

# TC-2.2 — Login tenant admin exitoso
lightpanda fetch \
  --url "http://demo.localhost:8000/api/admin/login" \
  --method POST \
  --header "Content-Type: application/json" \
  --body '{"email":"admin@benderand.cl","password":"admin1234"}' \
  2>&1 | tee tests/results/TC-2.2.log
# ✅ Esperado: HTTP 200 + token JWT

# TC-2.3 — Dashboard admin del tenant visible post-login
lightpanda fetch \
  --url "http://demo.localhost:8000/admin/dashboard" \
  --cookie "session=$COOKIE_TENANT_ADMIN" \
  --dump-dom \
  2>&1 | tee tests/results/TC-2.3.log
# ✅ Esperado: DOM del panel de administración del tenant "demo"

# TC-2.4 — Admin de tenant NO puede acceder al superadmin
lightpanda fetch \
  --url "http://localhost:8000/superadmin/dashboard" \
  --cookie "session=$COOKIE_TENANT_ADMIN" \
  --dump-headers \
  2>&1 | tee tests/results/TC-2.4.log
# ✅ Esperado: HTTP 403 o redirect a login de superadmin
```

---

## 📋 PRUEBA 3 — Verificación de los 2 Bugs Corregidos

Estos tests verifican específicamente que los fixes aplicados funcionan.

```bash
# FIX-1: /admin/login ya no da 404
# (la redirección desde /admin/login al controlador real)
lightpanda fetch \
  --url "http://demo.localhost:8000/admin/login" \
  --dump-headers \
  2>&1 | tee tests/results/FIX-1.log

echo "--- Verificación ---"
grep -q "HTTP/1.1 200" tests/results/FIX-1.log \
  && echo "✅ FIX-1 OK — /admin/login responde 200" \
  || echo "❌ FIX-1 FALLA — aún hay problema en la ruta"


# FIX-2: API superadmin usa prefijo /api correcto
# (corregido en bootstrap/app.php)
lightpanda fetch \
  --url "http://localhost:8000/api/superadmin/login" \
  --method POST \
  --header "Content-Type: application/json" \
  --body '{"email":"admin@benderand.cl","password":"password"}' \
  --dump-headers \
  2>&1 | tee tests/results/FIX-2.log

echo "--- Verificación ---"
grep -q "HTTP/1.1 200\|HTTP/1.1 422" tests/results/FIX-2.log \
  && echo "✅ FIX-2 OK — ruta /api/superadmin/login existe y responde" \
  || echo "❌ FIX-2 FALLA — ruta sigue sin prefijo /api (404)"
```

> **Nota sobre 422:** Un HTTP 422 en FIX-2 también es PASS — significa que la ruta existe pero la validación rechazó el body. Un 404 sería el bug original.

---

## 📋 PRUEBA 4 — Roles dentro del Tenant Demo

Una vez que el login de tenant funciona, verificar acceso por rol.

```bash
# Variables — reemplazar con tokens reales obtenidos del login
COOKIE_ADMIN="[token de admin@benderand.cl / admin1234]"

# TC-4.1 — Admin ve el panel completo
lightpanda fetch \
  --url "http://demo.localhost:8000/admin/dashboard" \
  --cookie "session=$COOKIE_ADMIN" \
  --js "document.querySelectorAll('[data-menu-item]').length" \
  2>&1 | tee tests/results/TC-4.1.log
# ✅ Esperado: número > 0 (hay ítems de menú visibles)

# TC-4.2 — POS accesible para admin
lightpanda fetch \
  --url "http://demo.localhost:8000/pos" \
  --cookie "session=$COOKIE_ADMIN" \
  --dump-headers \
  2>&1 | tee tests/results/TC-4.2.log
# ✅ Esperado: HTTP 200
```

---

## 🚀 Script de Smoke Test — Ejecutar primero

Antes de cualquier prueba detallada, correr este smoke test que verifica lo básico en 30 segundos:

```bash
#!/bin/bash
# tests/smoke_test.sh
echo "=== BenderAnd Smoke Test ==="
echo ""

BASE_SUPER="http://localhost:8000"
BASE_TENANT="http://demo.localhost:8000"
PASS=0
FAIL=0

check() {
  local label="$1"
  local url="$2"
  local expected_code="$3"

  actual=$(lightpanda fetch --url "$url" --dump-headers 2>&1 | grep "^HTTP" | head -1 | awk '{print $2}')

  if [ "$actual" = "$expected_code" ]; then
    echo "  ✅ $label (HTTP $actual)"
    ((PASS++))
  else
    echo "  ❌ $label — esperado HTTP $expected_code, obtenido HTTP $actual"
    ((FAIL++))
  fi
}

echo "── Rutas públicas ──"
check "SuperAdmin login page"          "$BASE_SUPER/superadmin"            200
check "Tenant admin login page"        "$BASE_TENANT/admin/login"          200

echo ""
echo "── Rutas protegidas (sin sesión → redirect) ──"
check "SuperAdmin dashboard sin sesión" "$BASE_SUPER/superadmin/dashboard"  302
check "Tenant dashboard sin sesión"     "$BASE_TENANT/admin/dashboard"      302

echo ""
echo "── API endpoints ──"
check "API superadmin login existe"    "$BASE_SUPER/api/superadmin/login"   405
# 405 = Method Not Allowed en GET → la ruta existe pero solo acepta POST ✅
# 404 = la ruta no existe → bug de prefijo /api

echo ""
echo "══════════════════════════"
echo "PASS: $PASS  |  FAIL: $FAIL"
echo "══════════════════════════"
```

```bash
# Ejecutar
chmod +x tests/smoke_test.sh
bash tests/smoke_test.sh
```

**Todos en PASS antes de continuar con el plan completo.**

---

## 📌 Referencia rápida — Qué cambió en el código

| Problema original | Fix aplicado | Archivo |
|---|---|---|
| `/admin/login` devolvía 404 | Redirect añadido hacia el controlador real | `routes/web.php` (tenant) |
| API superadmin buscaba `/api/superadmin/login` pero la ruta no tenía prefijo `/api` | Prefijo `/api` configurado para rutas centrales | `bootstrap/app.php` |

---

*Actualizado con credenciales verificadas · BenderAnd ERP · Marzo 2026*
