# Fix: Spider — 2 problemas en spider_tests.json y proxy CORS

## Resumen de lo que pasó

El spider funcionó correctamente (AUTH, ROLES, DB todos PASS). Los 11 FAIL son todos de `http_checks` y tienen el mismo patrón:

```
HTTP 0 esperado undefined
```

Esto indica **dos bugs distintos pero relacionados**:

---

## Bug 1 — Campos incorrectos en http_checks del JSON

El JSON de tests usa `desc` y `expect`, pero el spider espera `label` y `expected`:

```json
// Estado actual en spider_tests.json — INCORRECTO:
{
  "id": "H-1",
  "path": "//",
  "desc": "Vista: /",
  "expect": "200|301|302"
}

// Lo que el spider lee:
t.label    → undefined  (busca "label", no "desc")
t.expected → undefined  (busca "expected", no "expect")
```

Por eso el reporte muestra `[H-1] undefined HTTP 0 (esp undefined)` — el label es `undefined` porque el campo se llama distinto.

### Fix opción A — Corregir el JSON (recomendada)

En `SpiderController` (el que genera `spider_tests.json` via `php artisan`), cambiar los campos al generar los tests:

```php
// En SpiderController::sync() o donde se construye el array de tests:
[
    'id'       => 'H-'.$i,
    'label'    => 'Vista: '.$path,   // antes: 'desc'
    'path'     => $path,
    'expected' => '200|301|302',      // antes: 'expect'
    'url_key'  => 'super',
]
```

### Fix opción B — Hacer el spider tolerar ambos nombres (fallback)

En `phaseJsonTests()` del JS del spider, leer con fallback:

```javascript
const label    = t.label    || t.desc  || t.id
const expected = t.expected || t.expect || '200'
```

> Aplicar **ambas** opciones: A para que el JSON sea correcto desde la fuente, B como defensa.

---

## Bug 2 — HTTP 0 en http_checks: CORS bloqueando fetch directo

Todos los `http_checks` devuelven `HTTP 0`, lo que significa que el fetch falló antes de recibir respuesta. Causas posibles en orden de probabilidad:

### 2a — El proxy `/api/spider/probe` no está disponible

El spider intenta primero usar el proxy del backend para evitar CORS:
```javascript
const r = await fetch(SU + '/api/spider/probe?url='+encodeURIComponent(url), ...)
```

Si `SpiderController::probe()` no existe o devuelve error, el spider hace fallback a fetch directo. Desde el browser, hacer fetch a `http://localhost:8000//` o `http://localhost:8000/admin/clientes` **sin credenciales de sesión** resulta en un redirect a `/central/login` que el browser bloquea por CORS en modo `redirect:'manual'`, devolviendo `HTTP 0`.

**Fix:** Implementar o verificar que `GET /api/spider/probe` existe y funciona:

```php
// SpiderController::probe()
public function probe(Request $request)
{
    $url = $request->query('url');
    
    try {
        $response = Http::timeout(8)
            ->withoutVerifying()
            ->withHeaders(['Accept' => 'text/html'])
            ->get($url);
            
        return response()->json([
            'code' => $response->status(),
            'url'  => $url,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'code'  => 0,
            'error' => $e->getMessage(),
            'url'   => $url,
        ]);
    }
}
```

La ruta debe estar registrada:
```php
Route::get('/api/spider/probe', [SpiderController::class, 'probe'])
    ->middleware('auth:sanctum');
```

### 2b — Las rutas `/admin/*` no existen en el dominio central

El JSON tiene rutas como `/admin/clientes`, `/admin/dashboard`, etc. Estas son rutas de **tenant** (accesibles desde `demo.localhost:8000`), no desde `localhost:8000` (dominio central).

Al probarlas desde `localhost:8000`, Laravel no las encuentra y devuelve 404 o redirect, que el browser bloquea.

**Fix en el JSON:** Los `http_checks` con path `/admin/*` deben usar `url_key: "tenant"` para que el spider los pruebe contra `demo.localhost:8000`:

```json
{
  "id": "H-3",
  "label": "Vista: admin/clientes",
  "path": "/admin/clientes",
  "expected": "200|301|302",
  "url_key": "tenant"
}
```

Actualizar `SpiderController::sync()` para que al generar `http_checks` desde `route:list`, detecte el tipo de ruta y asigne el `url_key` correcto:

```php
// Lógica para asignar url_key:
$urlKey = 'super'; // default: dominio central

if (str_starts_with($path, '/admin/') ||
    str_starts_with($path, '/pos') ||
    str_starts_with($path, '/portal/') ||
    str_starts_with($path, '/rentas') ||
    str_starts_with($path, '/operario') ||
    str_starts_with($path, '/auth/')) {
    $urlKey = 'tenant';
}

if (str_starts_with($path, '/central/') ||
    str_starts_with($path, '/webhook/') ||
    str_starts_with($path, '/api/spider/') ||
    str_starts_with($path, '/api/central/')) {
    $urlKey = 'super';
}
```

---

## Resumen de cambios

| Archivo | Cambio |
|---|---|
| `spider_tests.json` | Regenerar con `sync` tras corregir SpiderController |
| `SpiderController::sync()` | Cambiar `desc`→`label`, `expect`→`expected`, agregar lógica `url_key` |
| `SpiderController::probe()` | Implementar si no existe (proxy HTTP del backend) |
| `routes/web.php` o `api.php` | Verificar que `GET /api/spider/probe` está registrado |
| `central/spider.blade.php` JS | Agregar fallback `t.label \|\| t.desc` y `t.expected \|\| t.expect` |

## Criterio de aceptación

- Los `http_checks` muestran labels reales (no `undefined`)
- Los checks de rutas `/admin/*` se ejecutan contra `demo.localhost:8000`
- Los checks de rutas `/central/*` se ejecutan contra `localhost:8000`
- `HTTP 0` desaparece — se reemplaza por códigos reales (200, 302, 401, etc.)
- Tasa de éxito sube significativamente desde el 48% actual
