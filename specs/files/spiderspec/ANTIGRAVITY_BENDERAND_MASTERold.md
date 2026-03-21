# BenderAnd ERP — Master Bug Report para Antigravity / Agente IA
**Generado:** 2026-03-16  
**Estado del sistema al momento de este reporte**

---

## IDENTIDAD DEL AGENTE

Eres un agente senior de debugging y desarrollo para **BenderAnd ERP**, una plataforma SaaS multi-tenant para gestión comercial en Chile. Tu trabajo es resolver cada bug listado en este documento con código concreto, comandos verificables y cero ambigüedad.

**Reglas de trabajo:**
1. Para cada bug: identifica el archivo exacto → propone el fix con código antes/después → da el comando de verificación post-fix
2. Ejecuta los fixes en orden de prioridad: 🔴 Crítico → 🟠 Alto → 🟡 Medio
3. Después de cada fix, cierra el bug en la DB con el comando indicado
4. Al terminar todos los bugs, ejecuta el smoke test y reporta los resultados
5. Si encuentras bugs nuevos durante el proceso, agrégalos al final con el formato establecido

---

## STACK COMPLETO DEL SISTEMA

```
Laravel 11 (PHP 8.2)
├── stancl/tenancy v3          — multi-tenant con DB separada por tenant
├── Laravel Sanctum            — autenticación JWT (tokens de acceso)
├── PostgreSQL 16              — DB central + DB por tenant
├── Redis                      — queues y cache
└── Docker                     — todo corre en contenedores

Contenedores activos:
  benderandos_app   → Laravel, puerto 8000
  benderandos_pg    → PostgreSQL 16
  benderandos_redis → Redis

DB central:    nombre=benderand  user=benderand  password=benderand123
App path:      /app  (dentro de benderandos_app)
.env path:     /app/.env
```

## CREDENCIALES VERIFICADAS

```
SuperAdmin:
  URL:      http://localhost:8000/superadmin
  API:      http://localhost:8000/api/superadmin/login
  Email:    admin@benderand.cl
  Password: password
  Tabla:    super_admins (DB central: benderand)

Tenant Admin (demo):
  URL:      http://demo.localhost:8000/admin/login
  API:      http://demo.localhost:8000/api/login
  Email:    admin@benderand.cl
  Password: admin1234
  Tabla:    usuarios (DB del tenant demo)
  /etc/hosts debe tener: 127.0.0.1 demo.localhost
```

## COMANDOS DOCKER DE REFERENCIA

```bash
# Artisan
docker exec benderandos_app php artisan [comando]

# Tinker (ejecutar código PHP)
docker exec benderandos_app php artisan tinker --execute="[código PHP]"

# PostgreSQL — DB central
docker exec benderandos_pg psql -U benderand -d benderand -c "[SQL]"

# PostgreSQL — DB de un tenant (ejemplo tenant con id=demo)
docker exec benderandos_pg psql -U benderand -d tenant_demo -c "[SQL]"

# Ver todos los tenants y sus DBs
docker exec benderandos_pg psql -U benderand -d benderand -c "SELECT id, tenancy_db_name FROM tenants;"

# Logs de Laravel
docker exec benderandos_app tail -200 /app/storage/logs/laravel.log

# Ver .env
docker exec benderandos_app cat /app/.env

# Seeders
docker exec benderandos_app php artisan db:seed --class=NombreSeeder

# Migraciones
docker exec benderandos_app php artisan migrate
docker exec benderandos_app php artisan tenants:migrate
docker exec benderandos_app php artisan migrate:status

# Cache/config
docker exec benderandos_app php artisan config:clear
docker exec benderandos_app php artisan cache:clear
docker exec benderandos_app php artisan route:clear
```

## ARQUITECTURA MULTI-TENANT

```
CENTRAL (benderandos_app en localhost:8000):
  routes/api.php      → /api/superadmin/*  (guard: superadmin)
  routes/web.php      → /superadmin  (UI HTML)
  bootstrap/app.php   → configura prefijo /api para rutas centrales
  app/Http/Controllers/CentralAuthController.php → login superadmin
  app/Models/SuperAdmin.php → modelo de superadmin (tabla: super_admins)

TENANT (benderandos_app en [tenant].localhost:8000):
  routes/tenant.php   → todas las rutas del tenant
  app/Http/Controllers/Tenant/AuthController.php → login tenant
  app/Models/Tenant/Usuario.php → modelo usuario (tabla: usuarios en DB tenant)
  stancl/tenancy resuelve el tenant por subdomain
  AUTH_MODEL=App\Models\Tenant\Usuario (en .env)

FLUJO LOGIN SUPERADMIN:
  POST /api/superadmin/login
  → CentralAuthController@login
  → valida contra tabla super_admins (DB central)
  → retorna { token: "...", user: {...} }

FLUJO LOGIN TENANT:
  POST /api/login (en subdominio tenant)
  → Tenant/AuthController@login
  → valida contra tabla usuarios (DB del tenant específico)
  → retorna { token: "...", user: {...} }
```

---

## BUGS RESUELTOS (historial — NO re-procesar)

### ✅ BUG-001 — Sintaxis lightpanda `--url` inválida
- **Tipo:** E-CONFIG | **Capa:** config
- **Fix aplicado:** Sintaxis posicional `lightpanda fetch URL` en lugar de `lightpanda fetch --url URL`
- **Resuelto:** 2026-03-16

### ✅ BUG-002 — `/admin/login` devolvía 404
- **Tipo:** E-REDIRECT | **Capa:** laravel
- **Fix aplicado:** Redirect añadido en `routes/web.php`
- **Resuelto:** 2026-03-16

### ✅ BUG-003 — API superadmin sin prefijo `/api`
- **Tipo:** E-HTTP | **Capa:** config
- **Fix aplicado:** Prefijo `/api` configurado en `bootstrap/app.php`
- **Resuelto:** 2026-03-16

### ✅ BUG-004 — Login SuperAdmin "Error de login" (tabla super_admins vacía)
- **Tipo:** E-AUTH | **Capa:** db
- **Fix aplicado:** `docker exec benderandos_app php artisan db:seed --class=SuperAdminSeeder`
- **Resuelto:** 2026-03-16

---

## BUGS ACTIVOS — RESOLVER EN ORDEN

---

### 🔴 BUG-005 — Tenant login falla (credenciales incorrectas)

| Campo | Valor |
|---|---|
| **Tipo** | E-AUTH |
| **Capa** | db |
| **Prioridad** | CRÍTICO |
| **URL** | `http://demo.localhost:8000/api/login` |
| **Síntoma** | POST con `admin@benderand.cl` / `admin1234` retorna "Las credenciales proporcionadas son incorrectas" |
| **Causa probable** | Tabla `usuarios` en DB del tenant demo vacía — TenantSeeder no ejecutado. O el tenant demo no existe. O la DB del tenant no fue migrada. |

**Diagnóstico paso a paso:**

```bash
# PASO 1: Ver qué tenants existen
docker exec benderandos_pg psql -U benderand -d benderand -c "SELECT id, tenancy_db_name FROM tenants;"

# PASO 2: Ver dominios registrados
docker exec benderandos_pg psql -U benderand -d benderand -c "SELECT domain, tenant_id FROM domains;"

# PASO 3: Si existe tenant con id cercano a 'demo', obtener su DB name del PASO 1
# Luego verificar si tiene usuarios (reemplazar TENANT_DB con el valor real):
docker exec benderandos_pg psql -U benderand -d TENANT_DB -c "SELECT id, email, rol FROM usuarios LIMIT 5;"

# PASO 4: Ver seeders disponibles
docker exec benderandos_app find /app/database/seeders -name "*.php" | xargs grep -l "Usuario\|usuario" 2>/dev/null

# PASO 5: Ver logs de error del intento de login
docker exec benderandos_app tail -50 /app/storage/logs/laravel.log | grep -A3 "credenciales\|login\|auth"
```

**Fix si tenant no existe:**
```bash
# Crear tenant demo
docker exec benderandos_app php artisan tinker --execute="
\$tenant = \App\Models\Tenant::create(['id' => 'demo']);
\$tenant->domains()->create(['domain' => 'demo.localhost']);
echo 'Tenant creado: ' . \$tenant->id;
"

# Migrar DB del tenant
docker exec benderandos_app php artisan tenants:migrate --tenants=demo

# Ejecutar seeder del tenant
docker exec benderandos_app php artisan tenants:seed --class=TenantSeeder --tenants=demo
```

**Fix si tenant existe pero usuarios vacío:**
```bash
# Ver nombre exacto del seeder
docker exec benderandos_app find /app/database/seeders -name "*.php" | xargs grep -l "usuario\|Usuario\|admin" 2>/dev/null

# Ejecutar seeder (reemplazar NombreDelSeeder con el nombre real encontrado)
docker exec benderandos_app php artisan tenants:seed --class=NombreDelSeeder --tenants=demo
```

**Fix si seeder no existe — crear usuario admin manualmente:**
```bash
docker exec benderandos_app php artisan tinker --execute="
\App\Models\Tenant\Tenant::find('demo')->run(function() {
    \App\Models\Tenant\Usuario::updateOrCreate(
        ['email' => 'admin@benderand.cl'],
        [
            'name'     => 'Admin Demo',
            'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
            'rol'      => 'admin',
        ]
    );
    echo 'Usuario admin creado en tenant demo';
});
"
```

**Verificación post-fix:**
```bash
curl -s -X POST http://demo.localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@benderand.cl","password":"admin1234"}' | python3 -m json.tool
# Esperado: {"token": "...", "user": {...}}
```

**Cerrar bug en DB:**
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c \
  "UPDATE bug_reports SET estado='resuelto', resuelto_en=NOW(), fix_commit='BUG-005-FIX' WHERE bug_id='BUG-005';"
```

---

### 🔴 BUG-006 — Spider H22/H23 no está instalado en el superadmin

| Campo | Valor |
|---|---|
| **Tipo** | E-UI |
| **Capa** | ui |
| **Prioridad** | CRÍTICO |
| **Síntoma** | `http://localhost:8000/superadmin/spider` devuelve 404 — la araña QA no está integrada |

**Fix — instalar spider.html como vista en Laravel:**
```bash
# Paso 1: Copiar el archivo spider.html como vista Blade
# (El archivo spider.html está en tests/spider.html o fue generado por el sistema)
docker exec benderandos_app mkdir -p /app/resources/views/superadmin
docker cp ~/trabajo/proyectos/src/benderandos/tests/spider.html \
  benderandos_app:/app/resources/views/superadmin/spider.blade.php
```

**Agregar ruta en `routes/web.php`:**
```php
// Agregar DENTRO del grupo de rutas superadmin existente
// Buscar el bloque Route::middleware(['superadmin.auth'])-> o similar
// y agregar:
Route::get('/superadmin/spider', function() {
    return view('superadmin.spider');
})->middleware(['web']); // ajustar middleware al que use el superadmin
```

**Si no existe middleware de superadmin, agregar verificación de token:**
```php
Route::get('/superadmin/spider', function() {
    return view('superadmin.spider');
});
```

**Agregar link en la navegación del superadmin (`resources/views/superadmin/` o `specs/files/superadmin.html`):**
```html
<!-- Agregar en el sidebar nav del superadmin -->
<a class="nav-item" onclick="window.location='/superadmin/spider'">
  <span class="nav-icon">◈</span> Spider QA
</a>
```

**Verificación:**
```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/superadmin/spider
# Esperado: 200
```

---

### 🔴 BUG-007 — Scripts H22/H23 usan `--url` inválido (versión antigua sin corregir en repo)

| Campo | Valor |
|---|---|
| **Tipo** | E-CONFIG |
| **Capa** | config |
| **Prioridad** | CRÍTICO |
| **Síntoma** | Scripts `tests/smoke_test.sh`, `tests/helpers.sh`, `tests/run_all.sh` usan `lightpanda fetch --url "..."` que devuelve `unknown argument` |

**Fix — reemplazar todos los usos de `--url` en los scripts de test:**
```bash
# Ver qué archivos tienen el problema
grep -rn "\-\-url" ~/trabajo/proyectos/src/benderandos/tests/ 2>/dev/null

# Fix automático — reemplazar --url "URL" por URL (posicional)
# El patrón es: lightpanda fetch --url "URL"  →  lightpanda fetch URL
sed -i 's/lightpanda fetch --url "\([^"]*\)"/lightpanda fetch "\1"/g' \
  ~/trabajo/proyectos/src/benderandos/tests/smoke_test.sh \
  ~/trabajo/proyectos/src/benderandos/tests/helpers.sh \
  ~/trabajo/proyectos/src/benderandos/tests/run_all.sh 2>/dev/null

# Para dump de HTML la sintaxis correcta es:
# lightpanda fetch --dump html URL   (posicional, --dump es flag válido, --url NO existe)
```

**Verificación:**
```bash
lightpanda fetch --dump html http://localhost:8000/superadmin | head -5
# Esperado: <!DOCTYPE html> o <html...
# Si devuelve HTML = sintaxis correcta
```

**Instalar scripts v2 (versión ya corregida):**
```bash
# Los scripts corregidos están en los archivos entregados:
# helpers_v2.sh, smoke_test_v2.sh, run_all_v2.sh
cp tests/helpers_v2.sh tests/helpers.sh
cp tests/smoke_test_v2.sh tests/smoke_test.sh
cp tests/run_all_v2.sh tests/run_all.sh
chmod +x tests/*.sh
```

**Verificación final:**
```bash
cd ~/trabajo/proyectos/src/benderandos
bash tests/smoke_test.sh
# Esperado: al menos FIX-1, FIX-2, FIX-3 en PASS
```

---

### 🔴 BUG-008 — Tabla `bug_reports` no existe en DB (sistema de registro de bugs no inicializado)

| Campo | Valor |
|---|---|
| **Tipo** | E-DATA |
| **Capa** | db |
| **Prioridad** | CRÍTICO |
| **Síntoma** | Los scripts de test intentan registrar bugs en `bug_reports` pero la tabla no existe — los `INSERT` fallan silenciosamente |

**Fix — crear la tabla:**
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c "
CREATE TABLE IF NOT EXISTS bug_reports (
  id              SERIAL PRIMARY KEY,
  bug_id          VARCHAR(40) UNIQUE,
  tc_id           VARCHAR(20),
  tipo            VARCHAR(20),
  capa            VARCHAR(20),
  descripcion     TEXT,
  detalle         TEXT,
  url             TEXT,
  http_esperado   VARCHAR(10),
  http_obtenido   VARCHAR(10),
  estado          VARCHAR(20) DEFAULT 'abierto',
  prioridad       VARCHAR(10) DEFAULT 'medio',
  encontrado      TIMESTAMP DEFAULT NOW(),
  resuelto_en     TIMESTAMP,
  fix_commit      VARCHAR(100),
  exportado       BOOLEAN DEFAULT FALSE
);
"
```

**Verificación:**
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c "\d bug_reports"
# Esperado: descripción de la tabla con todas las columnas
```

---

### 🟠 BUG-009 — `/etc/hosts` puede no tener `demo.localhost`

| Campo | Valor |
|---|---|
| **Tipo** | E-CONFIG |
| **Capa** | config |
| **Prioridad** | ALTO |
| **Síntoma** | `http://demo.localhost:8000` no responde — curl devuelve "Could not resolve host" |

**Diagnóstico:**
```bash
grep "demo.localhost" /etc/hosts
# Si no aparece nada = bug confirmado
```

**Fix:**
```bash
echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts
# Verificar
curl -s -o /dev/null -w "%{http_code}" http://demo.localhost:8000/login
# Esperado: 200 o 302 (no 000)
```

---

### 🟠 BUG-010 — Endpoint `/api/spider/lightpanda` no existe (UI spider requiere proxy backend)

| Campo | Valor |
|---|---|
| **Tipo** | E-HTTP |
| **Capa** | api |
| **Prioridad** | ALTO |
| **Síntoma** | El spider HTML hace fetch a `/api/spider/lightpanda?url=...` y `/api/spider/exec` pero esas rutas no existen en Laravel — la verificación UI del spider queda como WARN en lugar de PASS/FAIL |

**Fix — crear controlador SpiderController:**
```bash
docker exec benderandos_app php artisan make:controller SpiderController
```

**Contenido de `app/Http/Controllers/SpiderController.php`:**
```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class SpiderController extends Controller
{
    public function probe(Request $request)
    {
        $url = $request->query('url');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }
        // Solo URLs locales permitidas
        if (!str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1')) {
            return response()->json(['error' => 'Solo URLs locales'], 403);
        }
        $process = new Process(['curl', '-s', '-o', '/dev/null', '-w', '%{http_code}', '--max-time', '8', $url]);
        $process->run();
        return response()->json(['code' => (int) trim($process->getOutput())]);
    }

    public function lightpanda(Request $request)
    {
        $url      = $request->query('url');
        $selector = $request->query('selector', '');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }
        if (!str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1')) {
            return response()->json(['error' => 'Solo URLs locales'], 403);
        }
        $process = new Process(['lightpanda', 'fetch', '--dump', 'html', $url]);
        $process->setTimeout(15);
        $process->run();
        $html  = $process->getOutput();
        $found = $selector ? str_contains($html, $selector) : true;
        return response()->json(['found' => $found, 'html_length' => strlen($html)]);
    }

    public function dbCheck(Request $request)
    {
        $checks = [];
        // super_admins
        $saCount = \DB::table('super_admins')->count();
        $checks[] = ['label' => 'super_admins tiene registros', 'ok' => $saCount > 0,
            'detail' => "$saCount registros", 'fix' => 'php artisan db:seed --class=SuperAdminSeeder'];
        // plan_modulos
        $pmCount = \DB::table('plan_modulos')->count();
        $checks[] = ['label' => 'plan_modulos con datos (H19)', 'ok' => $pmCount > 0,
            'detail' => "$pmCount módulos", 'fix' => 'php artisan db:seed --class=PlanModulosSeeder'];
        // tenants
        $tCount = \DB::table('tenants')->count();
        $checks[] = ['label' => 'Tabla tenants accesible', 'ok' => true,
            'detail' => "$tCount tenants registrados", 'fix' => ''];
        // bug_reports
        $brExists = \DB::select("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_name='bug_reports'");
        $checks[] = ['label' => 'Tabla bug_reports existe', 'ok' => $brExists[0]->c > 0,
            'detail' => 'Sistema de tracking de bugs', 'fix' => 'Ver BUG-008'];
        return response()->json(['checks' => $checks]);
    }
}
```

**Agregar rutas en `routes/api.php`:**
```php
// Rutas del spider — solo accesibles con token superadmin
Route::middleware(['auth:sanctum'])->prefix('spider')->group(function () {
    Route::get('/probe',      [\App\Http\Controllers\SpiderController::class, 'probe']);
    Route::get('/lightpanda', [\App\Http\Controllers\SpiderController::class, 'lightpanda']);
    Route::get('/db-check',   [\App\Http\Controllers\SpiderController::class, 'dbCheck']);
});
```

**Verificación:**
```bash
# Obtener token SA primero
TOKEN=$(curl -s -X POST http://localhost:8000/api/superadmin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' | grep -oP '"token":"\K[^"]+')

# Probar endpoint
curl -s "http://localhost:8000/api/spider/db-check" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
# Esperado: {"checks": [...]}
```

---

### 🟠 BUG-011 — Tabla `plan_modulos` puede estar vacía (H19 seeder no ejecutado)

| Campo | Valor |
|---|---|
| **Tipo** | E-DATA |
| **Capa** | db |
| **Prioridad** | ALTO |
| **Síntoma** | `SELECT COUNT(*) FROM plan_modulos` devuelve 0 — el sistema de billing por módulo no tiene datos |

**Diagnóstico:**
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c "SELECT COUNT(*) FROM plan_modulos;"
```

**Fix:**
```bash
# Ver qué seeder existe para plan_modulos
docker exec benderandos_app find /app/database/seeders -name "*.php" | xargs grep -l "plan_modulos\|PlanModulo" 2>/dev/null

# Ejecutar el seeder encontrado (ajustar nombre)
docker exec benderandos_app php artisan db:seed --class=PlanModulosSeeder
# o
docker exec benderandos_app php artisan db:seed --class=DatabaseSeeder
```

**Verificación:**
```bash
docker exec benderandos_pg psql -U benderand -d benderand -c "SELECT COUNT(*) FROM plan_modulos;"
# Esperado: número > 0 (idealmente 32 módulos según el plan)
```

---

### 🟡 BUG-012 — Link al Spider QA faltante en sidebar del superadmin.html

| Campo | Valor |
|---|---|
| **Tipo** | E-UI |
| **Capa** | ui |
| **Prioridad** | MEDIO |
| **Síntoma** | El panel superadmin no tiene enlace visible al Spider QA — el usuario no sabe que existe |

**Fix en `specs/files/superadmin.html` (buscar el bloque `.nav-section` y agregar):**
```html
<!-- Buscar este bloque en superadmin.html: -->
<div class="nav-section">
  <div class="nav-section-lbl">Principal</div>
  <!-- AGREGAR esta línea: -->
  <a class="nav-item" href="/superadmin/spider">
    <span class="nav-icon">◈</span> Spider QA
  </a>
  <!-- resto del nav... -->
</div>
```

---

### 🟡 BUG-013 — `benderand-debug.js` no está incluido en ningún HTML

| Campo | Valor |
|---|---|
| **Tipo** | E-UI |
| **Capa** | ui |
| **Prioridad** | MEDIO |
| **Síntoma** | El archivo `specs/files/benderand-debug.js` existe pero no está `<script>` en ningún HTML del sistema |

**Diagnóstico:**
```bash
docker exec benderandos_app grep -r "benderand-debug" /app/resources/views /app/specs 2>/dev/null
# Si no devuelve nada = bug confirmado
```

**Fix — agregar en los HTMLs principales antes de `</body>`:**
```html
<!-- En superadmin.html, admin_dashboard_v2.html, pos_v3.html -->
<script src="/specs/files/benderand-debug.js"></script>
```

---

## PROCESO DE EJECUCIÓN PARA EL AGENTE

### Orden de trabajo recomendado:

```
1. BUG-008 → crear tabla bug_reports (base del sistema de tracking)
2. BUG-009 → verificar /etc/hosts (prerequisito para tenant)
3. BUG-005 → resolver login tenant (bloqueador principal)
4. BUG-007 → corregir scripts H22/H23 (instalar versiones v2)
5. BUG-006 → instalar spider en superadmin
6. BUG-010 → crear SpiderController (endpoints proxy)
7. BUG-011 → poblar plan_modulos si vacía
8. BUG-012 → link spider en sidebar
9. BUG-013 → incluir benderand-debug.js
```

### Smoke test final (ejecutar cuando todos los bugs críticos estén resueltos):

```bash
cd ~/trabajo/proyectos/src/benderandos

# Verificaciones rápidas manuales
echo "=== Verificaciones finales ==="

echo -n "1. SA login: "
curl -s -X POST http://localhost:8000/api/superadmin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' | grep -o '"token"' || echo "FALLO"

echo -n "2. Tenant login: "
curl -s -X POST http://demo.localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@benderand.cl","password":"admin1234"}' | grep -o '"token"' || echo "FALLO"

echo -n "3. SA UI: "
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/superadmin

echo -n "4. Spider QA: "
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/superadmin/spider

echo -n "5. Tenant login page: "
curl -s -o /dev/null -w "%{http_code}" http://demo.localhost:8000/login

echo -n "6. bug_reports en DB: "
docker exec benderandos_pg psql -U benderand -d benderand -t -c \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_name='bug_reports';" | tr -d ' '

echo ""
echo "=== Si todo muestra token/200/1 = sistema operativo ==="
```

### Ejecutar suite completa de tests:

```bash
bash tests/smoke_test.sh
# Esperado: todos en PASS

bash tests/run_all.sh
# Si hay FAILs -> genera tests/report/antigravity_FECHA.md automáticamente
# Ese archivo es el próximo input para el agente
```

---

## ESTADO DE HITOS DEL PROYECTO

| Hito | Descripción | Estado |
|---|---|---|
| H0 | Infraestructura Docker + Laravel | ✅ |
| H1 | POS Venta Minorista | ✅ |
| H2 | Multi-Operario + Roles | ✅ |
| H3 | Renta + Servicios + Fraccionados | ✅ |
| H4 | WhatsApp Onboarding | ✅ |
| H5 | Super Admin + Billing | ✅ |
| H6 | Portal Cliente Web | ✅ |
| H7 | Config Dinámica por Industria | ✅ |
| H8 | ERP ↔ WhatsApp Bot | ✅ |
| H9 | SII / LibreDTE | ✅ |
| H10 | Compras y Proveedores | ✅ |
| H11 | Delivery y Logística | ✅ |
| H12 | Restaurante: Recetas | ✅ |
| H13 | RRHH Completo Chile | ✅ |
| H14 | Reclutamiento & Postulación | ✅ |
| H15 | Marketing QR | ✅ |
| H16 | M31: Venta Software SaaS | ✅ |
| H17 | Dashboard Ejecutivo + API Pública | ✅ |
| H18 | Testing + Deploy | ❌ Pendiente |
| H19 | Billing Módulos + Onboarding | ✅ (seeder pendiente verificar) |
| H20 | UI Completa (Industrias + Módulos) | ✅ |
| H21 | Reportes Avanzados + Notif RT | ❌ Propuesto |
| H22 | Pruebas de Acceso UI (Spider) | 🟡 Scripts corregidos — falta instalar en superadmin |
| H23 | Estrategia de Errores (bug_reports) | 🟡 Diseñado — falta tabla en DB |

---

## ARCHIVOS CLAVE DEL PROYECTO

```
/app/
├── bootstrap/app.php                    ← prefijo /api para rutas centrales
├── routes/
│   ├── api.php                          ← rutas API central (superadmin)
│   ├── tenant.php                       ← rutas API tenant
│   └── web.php                          ← rutas web (redirect /admin/login)
├── app/Http/Controllers/
│   ├── CentralAuthController.php        ← login superadmin
│   └── Tenant/AuthController.php        ← login tenant
├── app/Models/
│   ├── SuperAdmin.php                   ← modelo superadmin (tabla super_admins)
│   └── Tenant/Usuario.php               ← modelo usuario tenant
├── database/seeders/
│   ├── SuperAdminSeeder.php             ← ✅ ejecutado
│   └── TenantSeeder.php (?)             ← ❓ verificar si existe y ejecutar
├── resources/views/superadmin/
│   └── spider.blade.php                 ← ❌ CREAR (BUG-006)
└── specs/files/
    ├── superadmin.html                  ← UI superadmin (agregar link spider)
    ├── admin_dashboard_v2.html          ← UI tenant admin
    └── benderand-debug.js               ← ❌ incluir en HTMLs (BUG-013)

tests/ (en host ~/trabajo/proyectos/src/benderandos/)
├── helpers_v2.sh                        ← funciones corregidas
├── smoke_test_v2.sh                     ← smoke test corregido
├── run_all_v2.sh                        ← suite completa corregida
├── spider.html                          ← araña QA para superadmin
├── BUGS.md                              ← bug tracker local
└── report/
    └── antigravity_FECHA.md             ← export automático para agente
```

---

## INSTRUCCIÓN FINAL PARA EL AGENTE

Cuando termines todos los bugs:

1. Ejecuta el smoke test: `bash tests/smoke_test.sh`
2. Si todos pasan, ejecuta la suite: `bash tests/run_all.sh`
3. Si la suite genera un nuevo `tests/report/antigravity_*.md`, ese archivo es el siguiente input
4. Reporta en este formato:

```
RESUMEN FINAL:
- BUG-005: RESUELTO — [descripción del fix aplicado]
- BUG-006: RESUELTO — [descripción del fix aplicado]
- BUG-007: RESUELTO — [descripción del fix aplicado]
- BUG-008: RESUELTO — [descripción del fix aplicado]
- BUG-009: RESUELTO — [descripción del fix aplicado]
- BUG-010: RESUELTO — [descripción del fix aplicado]
- BUG-011: RESUELTO — [descripción del fix aplicado]
- BUG-012: RESUELTO — [descripción del fix aplicado]
- BUG-013: RESUELTO — [descripción del fix aplicado]

Smoke test final: X/Y PASS
Sistema operativo: SÍ/NO
```

---
*BenderAnd ERP — Master Bug Report · Generado 2026-03-16*

---

## ACTUALIZACIÓN 2026-03-16 — diagnose_tenant.sh (FASE 0)

Se agrega `diagnose_tenant.sh` como **FASE 0** que corre automáticamente antes de cualquier test.

### Qué hace:
1. Verifica `/etc/hosts` — si falta `demo.localhost` lo agrega automáticamente
2. Lee todos los tenants y dominios reales de la DB (no asume que se llama "demo")
3. Verifica si existe la DB del tenant en PostgreSQL — si no, ejecuta `tenants:migrate`
4. Verifica tabla `usuarios` — si vacía, busca el seeder y lo ejecuta; si no hay seeder crea el usuario admin manualmente con `Hash::make('admin1234')`
5. Fuerza reset de password de `admin@benderand.cl` a `admin1234`
6. Ejecuta login real contra la URL real del tenant y reporta resultado
7. Guarda `tests/results/tenant_config.env` con `TENANT_ID`, `TENANT_DB`, `TENANT_URL` reales

### Instalar:
```bash
cp diagnose_tenant.sh ~/trabajo/proyectos/src/benderandos/tests/
cp run_all_v3.sh ~/trabajo/proyectos/src/benderandos/tests/run_all.sh
chmod +x ~/trabajo/proyectos/src/benderandos/tests/*.sh
```

### Ejecutar:
```bash
cd ~/trabajo/proyectos/src/benderandos
bash tests/run_all.sh
# La FASE 0 se ejecuta primero, diagnostica y corrige el tenant automáticamente,
# luego la suite completa usa la URL y DB reales detectadas
```
