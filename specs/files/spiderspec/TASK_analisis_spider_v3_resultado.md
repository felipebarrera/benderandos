# Análisis Spider QA — 2026-03-17 02:08

## Progreso real

| Métrica | Antes | Ahora | Delta |
|---|---|---|---|
| Total checks | 374 | 329 | ↓ sin duplicados |
| PASS | 14 (4%) | 174 (53%) | ✅ +160 |
| FAIL | 360 | 155 | ✅ -205 |

El spider ahora apunta correctamente a `demo.localhost:8000` para las rutas tenant. La autenticación y DB están 100% PASS. Los bugs restantes son reales.

---

## Los 155 FAIL restantes se agrupan en 6 patrones

---

### Grupo A — HTTP 0 en todos los http_checks (47 FAIL, prioridad baja)

**Causa:** El proxy `/api/spider/probe` no tiene CSRF en las llamadas del spider, o el browser bloquea fetch hacia rutas protegidas que hacen redirect. Es un problema del spider, no de la app.

**Rutas afectadas:** H-1 a H-47 completo.

**Fix:** El proxy ya existe y funciona (SA-5 probe devuelve 200 con token). El spider debe enviar el token al llamar al proxy:

```javascript
// En la función probe() del JS:
const r = await fetch(SU + '/api/spider/probe?url=' + encodeURIComponent(url), {
    headers: {
        'Authorization': 'Bearer ' + S.saT,  // ← agregar esto
        'Accept': 'application/json'
    }
})
```

Sin el header `Authorization`, el proxy puede estar fallando silenciosamente y cayendo al fetch directo que siempre da HTTP 0 por CORS.

---

### Grupo B — HTTP 403 en rutas de Delivery, Recetas, RRHH, Rentas, Reclutamiento (57 FAIL, prioridad alta)

**Causa:** El token de tenant que usa el spider es del usuario `admin@benderand.cl` con rol `admin`. Estas rutas tienen middleware `CheckRole` que require un rol específico distinto de `admin` (ej: `repartidor`, `cocina`, `rrhh`).

**Rutas afectadas:**
- `api/delivery/*` (T-23 a T-31) — 9 FAIL, todos HTTP 403
- `api/recetas/*` (T-58 a T-64) — 7 FAIL, todos HTTP 403
- `api/rrhh/*` (T-76 a T-88) — 13 FAIL, todos HTTP 403
- `api/rentas/*` (T-72 a T-74) — 3 FAIL, todos HTTP 403
- `api/reclutamiento/*` (T-65 a T-71) — 7 FAIL, todos HTTP 403

**Fix opción A (correcto para producción):** Crear un usuario de prueba con rol `super_admin` o `admin` que tenga acceso a todos los módulos, y verificar que `CheckRole` permite `admin` en estos endpoints.

**Fix opción B (correcto para el spider):** Ajustar el JSON para marcar estas rutas con `expected_with_auth: 403` si el rol admin no debe acceder, o usar un token de usuario con el rol correcto. El admin debería poder acceder a todos los módulos — revisar si `CheckRole` está bien configurado para `admin`.

**Comando de diagnóstico:**
```bash
# Ver qué roles acepta CheckRole para delivery:
grep -r 'delivery' app/Http/Middleware/CheckRole.php
grep -r 'delivery' routes/tenant.php
```

---

### Grupo C — HTTP 500 en rutas con `{id}` literal (53 FAIL, prioridad media)

**Causa:** El spider llama rutas con el placeholder `{id}` literal en la URL, por ejemplo `GET /api/ventas/{id}`. Laravel recibe `{id}` como string, lo pasa al controller, que intenta hacer un query `WHERE id = '{id}'` → error de base de datos (500).

**Rutas afectadas (todas comparten el patrón `/{id}` en la URL):**
- `api/clientes/{id}`, `api/compras/{id}`, `api/productos/{id}`, `api/ventas/{id}`, etc.
- `api/saas/clientes/{cliente}`, `api/saas/pipeline/{id}/*`, etc.
- `api/sii/dtes/{id}`, `api/sii/emitir/{ventaId}`, etc.

**Fix en el JSON del spider:** Los tests con `{id}` en el path deben excluirse del check `expected_with_auth` o usar IDs reales. La forma correcta es marcarlos como no verificables con token o cambiar el expected:

En `SpiderController::sync()`, al generar tests con `{id}` en el path:
```php
// Si el path tiene parámetros dinámicos, no testear con auth (no hay ID real)
$hasParam = str_contains($path, '{');
$tests['api_tenant_checks'][] = [
    'id'                  => 'T-'.$i,
    'label'               => $desc,
    'path'                => $path,
    'url_key'             => 'tenant',
    'expected'            => 401,
    'expected_with_auth'  => $hasParam ? null : 200,  // null = no testear con token
];
```

Y en el JS de `phaseJsonTests()`:
```javascript
if (token && t.expected_with_auth !== null && t.expected_with_auth !== undefined) {
    // solo testear con token si expected_with_auth está definido
}
```

---

### Grupo D — HTTP 401 en rutas bot e internal (6 FAIL, prioridad media)

**Causa:** Las rutas `api/bot/*` e `api/internal/*` usan un guard distinto — probablemente esperan un JWT de WhatsApp Bot, no el token Sanctum del usuario admin. El token de admin no es válido para estos endpoints.

**Rutas afectadas:**
- `api/bot/agenda/disponibilidad`, `api/bot/cliente/{telefono}`, `api/bot/pedido`, `api/bot/precio/{sku}`, `api/bot/stock/{sku}` → HTTP 401 con token admin
- `api/internal/clientes/buscar`, `api/internal/productos/stock`, `api/internal/ventas/remota` → HTTP 401 con token admin

**Fix en el JSON:** Marcar estas rutas con `expected_with_auth: 401` porque el token del spider (admin) no es el token correcto para estas rutas. Son rutas de bot/integración, no de usuario:

```json
{
  "id": "T-1",
  "label": "API Bot: agenda disponibilidad",
  "path": "/api/bot/agenda/disponibilidad",
  "url_key": "tenant",
  "expected": 401,
  "expected_with_auth": 401,
  "nota": "Requiere JWT de bot, no token Sanctum"
}
```

---

### Grupo E — HTTP 419 en `/central/spider/token` (2 FAIL, prioridad alta)

**Causa:** 419 = CSRF token mismatch. La ruta `POST /central/spider/token` requiere CSRF pero el spider lo llama con el token del meta tag, que puede haber expirado o no estar siendo leído correctamente.

**Fix:** Verificar que el meta tag CSRF se actualiza antes de la llamada, o cambiar la ruta a una API route (sin CSRF):

```php
// En routes/api.php en lugar de web.php:
Route::post('/spider/token', [SpiderController::class, 'generateToken'])
    ->middleware('auth:super_admin');
```

Y actualizar la llamada en el JS:
```javascript
const r = await fetch('/api/spider/token', {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
        // Sin CSRF — es ruta API
    }
})
```

---

### Grupo F — Rutas faltantes o 404 (3 FAIL, prioridad baja)

- `SA-3` `/api/central/plan/modulos/{id}/impacto` → HTTP 404 — la ruta no existe o no está registrada
- `SA-2` `/api/central/plan/modulos/{id}` → HTTP 422 — existe pero falla validación con `{id}` literal (mismo problema que Grupo C)
- `T-14` `/api/config/aplicar-preset/{industria}` → HTTP 404 — ruta no registrada o nombre incorrecto

---

### Grupo G — `api/login` devuelve 422 sin token (1 FAIL, prioridad baja)

`T-38` `api/login` sin token → HTTP 422 (esperado 401). Es un endpoint público (no requiere token para recibir la petición), pero devuelve 422 porque el body está vacío. Esto es comportamiento correcto — `api/login` es público por definición, debería marcarse como `expected: 422` (sin credenciales = validation error, no 401).

```json
{
  "id": "T-38",
  "label": "API Tenant: api/login",
  "expected": 422,
  "expected_with_auth": 422
}
```

---

## Resumen de acciones

| Grupo | Bugs | Acción | Archivo |
|---|---|---|---|
| A — HTTP 0 probe | 47 | Agregar `Authorization` al llamar `/api/spider/probe` | JS spider |
| B — HTTP 403 roles | 57 | Verificar `CheckRole` permite `admin` en delivery/rrhh/recetas/rentas/reclutamiento | `CheckRole.php`, `tenant.php` |
| C — HTTP 500 `{id}` literal | 53 | No testear con auth rutas con parámetros dinámicos | `SpiderController::sync()` + JS |
| D — HTTP 401 bot/internal | 6 | Marcar `expected_with_auth: 401` en JSON | `SpiderController::sync()` |
| E — HTTP 419 spider/token | 2 | Mover ruta a `api.php` para evitar CSRF | `routes/api.php` |
| F — 404 rutas faltantes | 3 | Registrar `modulos/{id}/impacto` y `config/aplicar-preset` | `routes/api.php` |
| G — login 422 | 1 | Cambiar expected de login a 422 | JSON |

## Estado actual positivo (no tocar)

Las siguientes áreas están **100% funcionales** confirmadas por el spider:
- Autenticación SA y tenant ✅
- Protección de rutas (401 sin token) ✅ en todos los tenant checks
- DB: tablas, seeders, dominios ✅
- Cross-tenant bloqueado ✅
- UI de central: brand, menú, sidebar ✅
- Ventas (list, por-cliente) ✅
- Productos (list) ✅
- Clientes (list) ✅
- Marketing (campanas list, dashboard, escaneos) ✅
- SAAS (cobros, pipeline, dashboard, snapshot) ✅
- SII (config, dtes list, libro-ventas) ✅
- Compras OC (list) ✅
- Roles ✅
- Producciones ✅
- Proveedores (list) ✅
