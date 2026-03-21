# Plan de correcciones Spider QA — 2026-03-17

## Diagnóstico general

Los 360 FAIL se reducen a **4 causas raíz**, no 360 problemas distintos. Resolver las 4 causas lleva la tasa de éxito del 4% a un estimado ~85%+.

---

## Causa 1 — `api_tenant_checks` se ejecutan contra `localhost:8000` en vez de `demo.localhost:8000`

**Impacto:** ~250 FAIL (todos los T-1 a T-125, duplicados por ejecución paralela)

**Evidencia:** Todas las URLs de `api_tenant_checks` muestran `http://localhost:8000/api/...` pero esas rutas solo existen en el dominio tenant (`demo.localhost:8000`). Laravel devuelve 404 porque el dominio central no las registra.

**Fix en `SpiderController::sync()`:** Agregar campo `url_key` al generar `api_tenant_checks`:

```php
// Todos los api_tenant_checks deben tener url_key: "tenant"
$tests['api_tenant_checks'][] = [
    'id'       => 'T-'.$i,
    'label'    => $desc,
    'path'     => $path,
    'method'   => $method,
    'url_key'  => 'tenant',   // ← FALTABA ESTO
    'expected' => 401,
    'expected_with_auth' => 200,
];
```

**Fix en `phaseJsonTests()` del JS:** Verificar que el spider usa `url_key` correctamente:

```javascript
const baseUrl = urlMap[t.url_key || 'super'] || SU;
// urlMap = { super: SU, tenant: TU }
```

---

## Causa 2 — `http_checks` todas devuelven HTTP 0 (proxy `/api/spider/probe` no funciona desde browser)

**Impacto:** ~94 FAIL (todos los H-1 a H-47, duplicados)

**Evidencia:** HTTP 0 en todas las rutas, incluyendo `/central/login` y `/central/tenants` que sí existen. El proxy devuelve respuesta pero el fetch directo falla por CORS/redirect.

**Fix en `SpiderController::probe()`:** El proxy existe (`SA-4 db-check` y `SA-7 tests` pasan, pero `SA-5 probe` devuelve 422). El 422 indica que llega al controller pero falla validación — falta el parámetro `url` o está mal nombrado.

```php
// Verificar que acepta ?url= como query param:
public function probe(Request $request)
{
    $request->validate(['url' => 'required|url']);  // ← puede estar fallando aquí
    $url = $request->query('url');
    // ...
}
```

El spider llama: `GET /api/spider/probe?url=...` — confirmar que el controller lee `$request->query('url')` y no `$request->input('url')` (que busca en body).

**Fix adicional:** Las rutas de `http_checks` que son de tenant (`/admin/*`, `/pos`, `/portal/*`, etc.) deben tener `url_key: "tenant"` en el JSON — igual que causa 1. Actualizar `SpiderController::sync()` con la lógica de clasificación:

```php
$urlKey = 'super';
$tenantPrefixes = ['/admin/', '/pos', '/portal/', '/rentas', '/operario', '/auth/'];
foreach ($tenantPrefixes as $prefix) {
    if (str_starts_with($path, $prefix)) {
        $urlKey = 'tenant';
        break;
    }
}
```

---

## Causa 3 — `api_sa_checks` usan `expected: 200` pero deberían esperar `401` sin token

**Impacto:** ~8 FAIL (SA-1, SA-2, SA-3, SA-5, SA-6 y duplicados)

**Evidencia:** El spider testa sin token y espera 200 → falla porque la API correctamente devuelve 401. Esto **no es un bug de la API** — la API está bien. Es la lógica del test.

El comportamiento correcto para `api_sa_checks` sin token es:
- `expected: 401` (sin token debe rechazar)  
- `expected_with_auth: 200` (con token SA debe aceptar)

**Fix en `SpiderController::sync()`:**

```php
$tests['api_sa_checks'][] = [
    'id'                  => 'SA-'.$i,
    'label'               => $desc,
    'path'                => $path,
    'method'              => $method,
    'url_key'             => 'super',
    'expected'            => 401,    // sin token → 401 es CORRECTO
    'expected_with_auth'  => 200,    // con token SA → 200
];
```

**Excepciones conocidas:**
- `SA-4` (`/api/spider/db-check`) y `SA-7` (`/api/spider/tests`) devuelven 200 sin token → estas rutas no tienen middleware auth. Evaluar si deben protegerse.
- `SA-5` (`/api/spider/probe`) devuelve 422 → bug real (ver Causa 2).
- `SA-6` (`/api/spider/sync`) devuelve 405 → probablemente está registrado como GET pero se llama como POST, o viceversa. Verificar en `routes/api.php`.

---

## Causa 4 — `phaseUI` busca `/superadmin` pero ya no existe

**Impacto:** 6 FAIL (BUG-SP-349 a 351, 358 a 360)

**Evidencia:**
```
SA UI: Input email — "login-email" NO encontrado en http://localhost:8000/superadmin
SA UI: Función doLogin — "doLogin" NO encontrado en http://localhost:8000/superadmin  
SA UI: Overlay login — "login-overlay" NO encontrado en http://localhost:8000/superadmin
```

`/superadmin` fue eliminado. `phaseUI` sigue buscando ahí.

**Fix en `phaseUI()` del JS del spider:**

```javascript
async function phaseUI(){
  if(!chk('c-ui')) return
  log('── UI ──','inf'); setP('UI...',98)
  const SU=cfg('u-super')
  // Cambiar /superadmin por /central
  try{
    const r=await fetch(SU+'/central')
    const html=await r.text()
    // El login de central usa sesión Laravel, no overlay JS
    // Cambiar los selectores a los que realmente existen en central:
    ;[['tb-brand','Brand B&'],['nav-item','Menú lateral'],['sidebar','Sidebar presente']].forEach(([sel,lbl])=>{
      if(html.includes(sel)) addR('pass','Central UI: '+lbl,'"'+sel+'" OK',SU+'/central','','ui')
      else{ addR('fail','Central UI: '+lbl,'"'+sel+'" NO encontrado',SU+'/central','Revisar layouts/central.blade.php','ui')
        addBug('E-UI','ui','medio',lbl+' faltante','Selector "'+sel+'" no en HTML','Revisar layouts/central.blade.php',SU+'/central') }
    })
  }catch(e){ addR('warn','Central UI: no verificable',e.message,SU+'/central','','ui') }
}
```

---

## Causa 5 (menor) — `demo.localhost` no resuelve en el entorno

**Impacto:** 2 FAIL críticos (BUG-SP-347, BUG-SP-357)

**Evidencia:** `Tenant URL no alcanzable — HTTP 0 — http://demo.localhost:8000`

**Fix:** Agregar entrada en `/etc/hosts` de la máquina donde corre el servidor:

```bash
echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts
```

Verificar también en el contenedor Docker si aplica:
```bash
docker exec benderandos_app bash -c 'echo "127.0.0.1 demo.localhost" >> /etc/hosts'
```

---

## Orden de ejecución recomendado

| # | Acción | Archivos | Impacto esperado |
|---|---|---|---|
| 1 | Agregar `url_key: "tenant"` a todos los `api_tenant_checks` en `SpiderController::sync()` | `SpiderController.php` | Elimina ~250 FAIL falsos |
| 2 | Agregar `expected: 401` a `api_sa_checks` y `api_tenant_checks` | `SpiderController.php` | Elimina ~100 FAIL falsos |
| 3 | Corregir `SpiderController::probe()` — validación de parámetro `url` | `SpiderController.php` | Resuelve HTTP 0 en http_checks |
| 4 | Agregar `url_key` correcto a `http_checks` (tenant vs super) | `SpiderController.php` | http_checks contra dominio correcto |
| 5 | Corregir ruta `api/spider/sync` — verificar método HTTP (GET vs POST) | `routes/api.php` | Resuelve SA-6 405 |
| 6 | Actualizar `phaseUI()` en JS del spider — cambiar `/superadmin` por `/central` y actualizar selectores | `central/spider.blade.php` | Elimina 6 FAIL de UI |
| 7 | Agregar `demo.localhost` a `/etc/hosts` | Sistema | Resuelve tenant no alcanzable |
| 8 | Hacer `Sync desde Laravel` para regenerar `spider_tests.json` con los cambios | — | Regenera JSON correcto |

## Resultado esperado tras el fix

| Métrica | Antes | Después estimado |
|---|---|---|
| Total checks | 374 | ~200 (sin duplicados) |
| PASS | 14 (4%) | ~170 (85%+) |
| FAIL reales | ~10 | ~30 (bugs reales de API) |
| FAIL falsos | ~350 | 0 |

Los FAIL que queden después serán **bugs reales de la aplicación** que el spider detectó correctamente.
