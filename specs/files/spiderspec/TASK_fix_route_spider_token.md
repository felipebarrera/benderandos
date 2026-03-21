# Fix: Route [central.spider.token] not defined

## Error

```
RouteNotFoundException: Route [central.spider.token] not defined.
resources/views/central/spider.blade.php:286
```

## Causa

El blade usa `{{ route('central.spider.token') }}` pero la ruta POST para obtener el token del spider no está registrada, o fue registrada en `api.php` con un nombre distinto.

## Fix

### Opción A — Registrar la ruta en `routes/web.php` dentro del grupo central

```php
// En el grupo central autenticado:
Route::post('/spider/token', [SpiderController::class, 'generateToken'])
    ->name('central.spider.token');
```

### Opción B — Si ya está en `routes/api.php`, cambiar el blade para usar URL directa

En `resources/views/central/spider.blade.php` línea 286, reemplazar:

```javascript
// ANTES:
const r_token = await fetch('{{ route('central.spider.token') }}', {

// DESPUÉS:
const r_token = await fetch('/api/spider/token', {
```

Y registrar en `routes/api.php`:

```php
Route::post('/spider/token', [SpiderController::class, 'generateToken'])
    ->middleware('auth:super_admin');
```

### Agregar el método `generateToken` en SpiderController si no existe

```php
public function generateToken(Request $request)
{
    $token = auth('super_admin')->user()
        ->createToken('spider-session')
        ->plainTextToken;

    return response()->json(['token' => $token]);
}
```

## Opción recomendada

Usar **Opción A** — mantener la ruta en `web.php` dentro del grupo central autenticado para que herede el middleware `auth:super_admin` automáticamente y el nombre `central.spider.token` coincida con lo que espera el blade.

## Criterio de aceptación

- `GET /central/spider` carga sin error
- El spider obtiene token SA sin prompt de contraseña
