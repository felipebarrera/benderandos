# BENDERAND — IMPLEMENTATION PLAN: AUDIT UNIFICADA v1 FIXES
*Fecha: Marzo 2026 · Para: antigraviity · Stack: Laravel 11 + PostgreSQL 16 + stancl/tenancy v3*

---

## ORDEN DE EJECUCIÓN

```
Paso 1  → Entorno: /etc/hosts + tenant demo activo
Paso 2  → Spider security: auth:sanctum en rutas spider + getToken()
Paso 3  → Ruta faltante: /api/config/mi-plan
Paso 4  → Fix crash 500: PublicApiController@productos
Paso 5  → Migraciones: tenants:migrate en todos los demos
Paso 6  → Verificación: re-run spider
```

---

## PASO 1 — ENTORNO

### 1.1 /etc/hosts

```bash
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

### 1.2 Tenant demo — activar plan y todos los módulos

```bash
php artisan tinker --no-interaction << 'EOF'
use App\Models\Tenant;

$tenant = Tenant::where('id', 'demo')->first();

if (!$tenant) {
    echo "ERROR: tenant 'demo' no encontrado\n";
    echo "Tenants disponibles: " . Tenant::pluck('id')->join(', ') . "\n";
    exit(1);
}

// Activar plan sin restricciones
$tenant->update([
    'estado'        => 'activo',
    'trial_ends_at' => null,
    'plan_id'       => $tenant->plan_id ?? 1,
]);

// Activar todos los módulos dentro del contexto del tenant
$tenant->run(function () {
    \App\Models\ConfigModulo::query()->update(['activo' => true]);
    $count = \App\Models\ConfigModulo::where('activo', true)->count();
    echo "Módulos activos en demo: {$count}\n";
});

echo "Tenant demo: estado={$tenant->fresh()->estado}\n";
EOF
```

### 1.3 Verificar migraciones en todos los tenants demo

```bash
# Correr migraciones pendientes en TODOS los tenants demo
php artisan tenants:migrate --tenants=demo
php artisan tenants:migrate --tenants=demo-legal
php artisan tenants:migrate --tenants=demo-padel
php artisan tenants:migrate --tenants=demo-motel
php artisan tenants:migrate --tenants=demo-abarrotes
php artisan tenants:migrate --tenants=demo-ferreteria
php artisan tenants:migrate --tenants=demo-medico
php artisan tenants:migrate --tenants=demo-saas

# O en un solo comando si el sistema lo soporta:
php artisan tenants:migrate
```

---

## PASO 2 — SPIDER SECURITY (SP-048 a SP-054)

### 2.1 Modificar `routes/api.php` (central)

Buscar el bloque actual de rutas del spider y reemplazarlo:

```php
// ============================================================
// ANTES (buscar este patrón en routes/api.php):
// ============================================================
Route::get('/spider/db-check', [SpiderController::class, 'dbCheck']);
Route::get('/spider/probe',    [SpiderController::class, 'probe']);
Route::get('/spider/sync',     [SpiderController::class, 'sync']);
Route::get('/spider/tests',    [SpiderController::class, 'tests']);

// ============================================================
// DESPUÉS — envolver en auth:sanctum + agregar /token:
// ============================================================
Route::middleware('auth:sanctum')->prefix('spider')->group(function () {
    Route::get('/db-check', [\App\Http\Controllers\Central\SpiderController::class, 'dbCheck']);
    Route::get('/probe',    [\App\Http\Controllers\Central\SpiderController::class, 'probe']);
    Route::get('/sync',     [\App\Http\Controllers\Central\SpiderController::class, 'sync']);
    Route::get('/tests',    [\App\Http\Controllers\Central\SpiderController::class, 'tests']);
    Route::post('/token',   [\App\Http\Controllers\Central\SpiderController::class, 'getToken']);
});
```

> Si el namespace de `SpiderController` es diferente, ajustar el path. Buscar con:
> ```bash
> grep -r "SpiderController" app/Http/Controllers/ --include="*.php" -l
> ```

### 2.2 Modificar `routes/web.php` (central) — proteger `/central/spider`

```php
// Buscar la ruta actual:
Route::get('/central/spider', ...);

// Asegurar que esté dentro del middleware de auth del central:
Route::middleware(['auth:central_web'])->group(function () {
    // ... otras rutas del central ...
    Route::get('/spider', [\App\Http\Controllers\Central\SpiderController::class, 'dashboard'])
         ->name('central.spider');
});
```

> El guard `central_web` puede tener otro nombre. Verificar con:
> ```bash
> grep -r "central_web\|CentralAuth\|central.auth" config/auth.php routes/
> ```

### 2.3 Agregar método `getToken()` en `SpiderController`

```bash
# Encontrar el archivo:
grep -r "class SpiderController" app/ --include="*.php"
```

Agregar al final de la clase, antes del cierre `}`:

```php
/**
 * Genera un nuevo token Sanctum para el spider.
 * POST /api/spider/token
 * Requiere: auth:sanctum (super admin autenticado)
 */
public function getToken(Request $request): \Illuminate\Http\JsonResponse
{
    // Revocar tokens anteriores del spider para no acumular
    $request->user()
            ->tokens()
            ->where('name', 'spider-qa-token')
            ->delete();

    $token = $request->user()
                     ->createToken('spider-qa-token')
                     ->plainTextToken;

    return response()->json([
        'token'      => $token,
        'type'       => 'Bearer',
        'expires_in' => null, // Sanctum tokens no expiran por defecto
        'generated'  => now()->toISOString(),
    ]);
}
```

---

## PASO 3 — RUTA FALTANTE `/api/config/mi-plan` (SP-059)

### 3.1 Agregar ruta en `routes/tenant.php`

```php
// Buscar el grupo de rutas de config del tenant:
// Probablemente hay un bloque: Route::prefix('config')->group(...)

// Agregar dentro del grupo auth:sanctum existente de config:
Route::middleware('auth:sanctum')->group(function () {
    // ... rutas existentes de config ...

    // NUEVA:
    Route::get('/config/mi-plan', [\App\Http\Controllers\Tenant\ConfigController::class, 'miPlan'])
         ->name('tenant.config.mi-plan');
});
```

### 3.2 Agregar método `miPlan()` en `ConfigController`

```bash
# Encontrar el archivo:
grep -r "class ConfigController" app/ --include="*.php"
```

Agregar el método:

```php
/**
 * Retorna el plan activo y los módulos habilitados del tenant actual.
 * GET /api/config/mi-plan
 */
public function miPlan(Request $request): \Illuminate\Http\JsonResponse
{
    // Obtener módulos activos del tenant (tabla en el schema del tenant)
    $modulosActivos = \App\Models\ConfigModulo::where('activo', true)
                                               ->pluck('modulo_id')
                                               ->toArray();

    // Intentar obtener la suscripción/plan desde el central
    // El modelo Tenant está en el schema public — accedemos via tenant()
    $tenantId = tenant('id');

    // Datos del tenant desde el central (sin cambiar de schema)
    $tenantData = \DB::connection('pgsql') // conexión al schema public
                    ->table('tenants')
                    ->where('id', $tenantId)
                    ->select('id', 'estado', 'plan_id', 'trial_ends_at', 'rubro')
                    ->first();

    // Si no se puede leer el central desde el tenant, usar datos locales
    if (!$tenantData) {
        return response()->json([
            'tenant_id'     => $tenantId,
            'estado'        => 'desconocido',
            'plan_id'       => null,
            'trial_ends_at' => null,
            'rubro'         => config('tenant.rubro', null),
            'modulos'       => $modulosActivos,
            'total_modulos' => count($modulosActivos),
        ]);
    }

    return response()->json([
        'tenant_id'     => $tenantData->id,
        'estado'        => $tenantData->estado,
        'plan_id'       => $tenantData->plan_id,
        'trial_ends_at' => $tenantData->trial_ends_at,
        'rubro'         => $tenantData->rubro,
        'modulos'       => $modulosActivos,
        'total_modulos' => count($modulosActivos),
        'es_trial'      => !is_null($tenantData->trial_ends_at) 
                           && now()->lt($tenantData->trial_ends_at),
    ]);
}
```

> **Nota sobre la conexión al central:** En stancl/tenancy v3, cuando estás dentro del contexto de un tenant, la conexión por defecto apunta al schema del tenant. Para leer la tabla `tenants` del schema `public` necesitas usar la conexión del landlord. Alternativa más limpia:
>
> ```php
> // Alternativa usando el helper de tenancy:
> $tenantModel = \Stancl\Tenancy\Database\Models\Tenant::find(tenant('id'));
> // O si tienes un modelo propio:
> $tenantModel = \App\Models\Tenant::find(tenant('id'));
> ```

---

## PASO 4 — FIX CRASH 500: PublicApiController (SP-084)

### 4.1 Encontrar el controller

```bash
grep -r "public/productos\|v1/public" app/ --include="*.php" -l
grep -r "class PublicApiController\|productosPublicos\|public.*productos" app/ --include="*.php" -l
```

### 4.2 Código completo corregido del método `productos()`

Reemplazar el método `productos()` (o como se llame) con:

```php
/**
 * Catálogo público de productos del tenant.
 * GET /api/v1/public/productos
 *
 * Fixes aplicados:
 * - Removido .with('categoria') — relación no existe en el modelo Producto
 * - where('activo', true) → where('estado', 'activo')
 * - Campos mapeados a nombres correctos post-audit: valor_venta, cantidad
 * - Envuelto en try-catch para evitar 500
 */
public function productos(Request $request): \Illuminate\Http\JsonResponse
{
    try {
        $query = \App\Models\Producto::query()
            ->where('estado', 'activo')  // FIX: era where('activo', true)
            // ->with('categoria')        // FIX: REMOVIDO — relación no existe
            ->select([
                'id',
                'nombre',
                'descripcion',
                'codigo_barras',
                'valor_venta',           // FIX: era 'precio'
                'cantidad',              // FIX: era 'stock'
                'tipo_producto',         // FIX: era 'tipo'
                'unidad_medida',
            ]);

        // Filtros opcionales
        if ($request->has('q')) {
            $q = $request->get('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('codigo_barras', 'like', "%{$q}%");
            });
        }

        if ($request->has('tipo')) {
            $query->where('tipo_producto', $request->get('tipo'));
        }

        $productos = $query->orderBy('nombre')->get();

        // Mapear a nombres de API consistentes con el POS
        $data = $productos->map(function ($p) {
            return [
                'id'           => $p->id,
                'nombre'       => $p->nombre,
                'descripcion'  => $p->descripcion,
                'codigo'       => $p->codigo_barras,
                'precio'       => $p->valor_venta,      // alias para el POS
                'valor_venta'  => $p->valor_venta,
                'stock'        => $p->cantidad,         // alias para el POS
                'cantidad'     => $p->cantidad,
                'tipo'         => $p->tipo_producto,
                'unidad'       => $p->unidad_medida,
            ];
        });

        return response()->json([
            'data'  => $data,
            'total' => $data->count(),
        ]);

    } catch (\Exception $e) {
        \Log::error('PublicApiController@productos: ' . $e->getMessage(), [
            'tenant' => tenant('id'),
            'trace'  => $e->getTraceAsString(),
        ]);

        return response()->json([
            'data'    => [],
            'total'   => 0,
            'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
        ], 500);
    }
}
```

### 4.3 Fix del método `stock()` en el mismo controller

```php
/**
 * Stock de un producto por SKU/código de barras.
 * GET /api/v1/public/stock/{sku}
 *
 * Fixes: stock → cantidad, precio_venta → valor_venta
 */
public function stock(string $sku): \Illuminate\Http\JsonResponse
{
    $producto = \App\Models\Producto::where('codigo_barras', $sku)
                                    ->where('estado', 'activo')   // FIX: era 'activo'=true
                                    ->first();

    if (!$producto) {
        return response()->json(['error' => 'Producto no encontrado'], 404);
    }

    return response()->json([
        'sku'         => $producto->codigo_barras,
        'nombre'      => $producto->nombre,
        'stock'       => $producto->cantidad,        // FIX: era $producto->stock
        'cantidad'    => $producto->cantidad,
        'precio'      => $producto->valor_venta,     // FIX: era $producto->precio_venta
        'valor_venta' => $producto->valor_venta,
        'disponible'  => $producto->cantidad > 0,
    ]);
}
```

---

## PASO 5 — LIMPIEZA Y CACHÉ

```bash
# Limpiar vistas compiladas (después de cambios en blade)
php artisan view:clear

# Limpiar caché de rutas (después de cambios en routes/)
php artisan route:clear
php artisan route:cache

# Limpiar caché general
php artisan cache:clear

# Verificar que no hay errores de sintaxis en los controllers modificados
php artisan route:list --path=api/spider
php artisan route:list --path=api/config/mi-plan
php artisan route:list --path=api/v1/public

# Verificar template literals en blades corregidos
grep -n "\\\\`\|\\\\${" resources/views/tenant/config.blade.php && echo "ERROR" || echo "OK"
grep -n "\\\\`\|\\\\${" resources/views/tenant/recetas.blade.php && echo "ERROR" || echo "OK"
```

---

## PASO 6 — VERIFICACIÓN POST-FIX

### 6.1 Verificar spider security (SP-048 a SP-054)

```bash
# Sin token — debe retornar 401
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/spider/db-check
# Esperado: 401

curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/spider/probe
# Esperado: 401

# Generar token primero
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@benderand.cl","password":"demo1234"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")

# Con token — debe retornar 200
curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/spider/db-check
# Esperado: 200

# Obtener spider token (nuevo endpoint)
curl -s -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  http://localhost:8000/api/spider/token
# Esperado: 200 con {"token":"...", "type":"Bearer"}
```

### 6.2 Verificar /api/config/mi-plan (SP-059)

```bash
# Obtener token del tenant demo
TENANT_TOKEN=$(curl -s -X POST http://demo.localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@demo.cl","password":"demo1234"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")

# Llamar mi-plan
curl -s \
  -H "Authorization: Bearer $TENANT_TOKEN" \
  http://demo.localhost:8000/api/config/mi-plan | python3 -m json.tool
# Esperado: {"tenant_id":"demo","estado":"activo","modulos":[...32 módulos...]}
```

### 6.3 Verificar fix del 500 en productos públicos (SP-084)

```bash
# Verificar que ya no retorna 500
curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer $TENANT_TOKEN" \
  http://demo.localhost:8000/api/v1/public/productos
# Esperado: 200

# Verificar estructura del response
curl -s \
  -H "Authorization: Bearer $TENANT_TOKEN" \
  http://demo.localhost:8000/api/v1/public/productos | python3 -m json.tool
# Esperado: {"data":[{id, nombre, precio, valor_venta, stock, cantidad, ...}], "total": N}

# Verificar que precio y valor_venta son el mismo número
curl -s \
  -H "Authorization: Bearer $TENANT_TOKEN" \
  "http://demo.localhost:8000/api/v1/public/productos" \
  | python3 -c "
import sys,json
data = json.load(sys.stdin)['data']
if data:
    p = data[0]
    assert p['precio'] == p['valor_venta'], f'MISMATCH: precio={p[\"precio\"]} vs valor_venta={p[\"valor_venta\"]}'
    assert p['stock'] == p['cantidad'], f'MISMATCH: stock={p[\"stock\"]} vs cantidad={p[\"cantidad\"]}'
    print('OK: aliases correctos')
print(f'Total productos: {len(data)}')
"
```

### 6.4 Verificar que la migración corrió en los tenants

```bash
# Verificar que la columna giro existe en clientes del tenant demo
php artisan tinker --no-interaction << 'EOF'
$tenant = App\Models\Tenant::where('id','demo')->first();
$tenant->run(function() {
    $cols = \Schema::getColumnListing('clientes');
    $hasGiro = in_array('giro', $cols);
    $hasDireccion = in_array('direccion', $cols);
    echo "giro: " . ($hasGiro ? 'OK' : 'FALTA') . "\n";
    echo "direccion: " . ($hasDireccion ? 'OK' : 'FALTA') . "\n";
    
    // Verificar campos de productos
    $colsProd = \Schema::getColumnListing('productos');
    echo "valor_venta: " . (in_array('valor_venta', $colsProd) ? 'OK' : 'FALTA') . "\n";
    echo "cantidad: " . (in_array('cantidad', $colsProd) ? 'OK' : 'FALTA') . "\n";
    echo "tipo_producto: " . (in_array('tipo_producto', $colsProd) ? 'OK' : 'FALTA') . "\n";
});
EOF
```

### 6.5 Re-run spider y verificar resultado esperado

```bash
# Ejecutar el spider
cd /path/to/spider
node spider.js  # o el comando que corresponda

# Resultado esperado después de los fixes:
# Total checks: ~271
# PASS: ~260+
# FAIL reales: < 10
# Los 47 bugs de HTTP 0 deben desaparecer
# Los bugs de módulos inactivos (403) siguen apareciendo → son correctos
```

---

## REFERENCIA RÁPIDA — ARCHIVOS MODIFICADOS

| Archivo | Cambio | Fix |
|---|---|---|
| `/etc/hosts` | Agregar 8 subdominios demo-* | C1: DNS |
| `routes/api.php` (central) | Envolver spider en `auth:sanctum` + agregar POST `/token` | SP-048 a SP-054 |
| `routes/web.php` (central) | Verificar que `/central/spider` tiene `auth:central_web` | SP-054 |
| `app/Http/Controllers/Central/SpiderController.php` | Agregar método `getToken()` | SP-052/053 |
| `routes/tenant.php` | Agregar `GET /api/config/mi-plan` | SP-059 |
| `app/Http/Controllers/Tenant/ConfigController.php` | Agregar método `miPlan()` | SP-059 |
| `app/Http/Controllers/Tenant/PublicApiController.php` | Fix `productos()`: remover `with('categoria')`, `where('estado','activo')`, campos `valor_venta`/`cantidad` | SP-084 |
| `app/Http/Controllers/Tenant/PublicApiController.php` | Fix `stock()`: campos `cantidad`/`valor_venta` | SP-084 |
| DB: tenant demo | `estado='activo'`, `trial_ends_at=null`, todos los módulos ON | C3: 402 |
| Todos los tenants | `php artisan tenants:migrate` | Migraciones pendientes |

---

## NOTA SOBRE SP-083 — TOKEN SANCTUM CROSS-TENANT

Este bug requiere un cambio en la lógica del spider, no en el servidor.

**El problema:** El spider genera un token autenticándose en el central (`localhost:8000/api/login`) y luego lo usa para hacer requests a los tenants (`demo.localhost:8000/api/user`). En stancl/tenancy v3, los tokens de Sanctum se guardan en la tabla `personal_access_tokens` del schema `public`. Al hacer un request al tenant, el guard busca el token en el schema del tenant (que no lo tiene), retornando 401.

**Fix en el spider** (no en el servidor):

```javascript
// El spider debe autenticarse por separado en cada tenant:

async function getTenantToken(tenantHost, email, password) {
    const res = await fetch(`http://${tenantHost}/api/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await res.json();
    return data.token;
}

// Para cada tenant demo:
const tokens = {
    central:       await getCentralToken('superadmin@benderand.cl', 'demo1234'),
    demo:          await getTenantToken('demo.localhost:8000', 'admin@demo.cl', 'demo1234'),
    'demo-legal':  await getTenantToken('demo-legal.localhost:8000', 'admin@demo-legal.cl', 'demo1234'),
    // ... etc
};

// Usar el token correcto en cada request:
// requests a demo.localhost → usar tokens['demo']
// requests a localhost:8000 → usar tokens['central']
```

**Alternativa en el servidor** (si se quiere que el token del central funcione en tenants):

Configurar Sanctum para usar la conexión del landlord al verificar tokens en el tenant. Esto requiere un custom guard — no recomendado a menos que sea necesario para el flujo del spider.

---

*BenderAnd ERP · Implementation Plan: Audit Unificada v1 Fixes · Marzo 2026*
*Archivos a modificar: 8 · Fixes de código: 4 · Fixes de entorno: 2*
