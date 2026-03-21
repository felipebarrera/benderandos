# Antigravity — Impersonar: URL sin puerto y tabla "usuarios" no existe

## Bug 1 — URL de redirección no incluye el puerto

### Síntoma

```
https://demo.localhost/?token=15|lU7...
```

En local el servidor corre en `:8000`, la URL debe ser:

```
http://demo.localhost:8000/?token=15|lU7...
```

### Causa

Al construir la URL de impersonar, el código usa el dominio del tenant sin incluir
el puerto ni respetar el esquema (http vs https) del entorno actual.

### Fix en ImpersonateController

```php
// app/Http/Controllers/Central/ImpersonateController.php

$dominio = $tenant->domains()->orderBy('id')->first()?->domain;

if (!$dominio) {
    return response()->json(['message' => 'Tenant sin dominio configurado.'], 422);
}

// Construir URL respetando esquema y puerto del entorno
$scheme = request()->getScheme();           // 'http' en local, 'https' en prod
$port   = request()->getPort();             // 8000 en local, 80/443 en prod

// Solo incluir el puerto si no es el estándar del esquema
$incluirPuerto = ($scheme === 'http' && $port != 80)
              || ($scheme === 'https' && $port != 443);

$baseUrl = $scheme . '://' . $dominio
         . ($incluirPuerto ? ':' . $port : '');

return response()->json([
    'token' => $token,
    'url'   => $baseUrl,
]);
```

### Alternativa usando el helper de Laravel

```php
// Obtener el puerto del APP_URL configurado en .env
$appUrl  = config('app.url');           // http://localhost:8000
$parsed  = parse_url($appUrl);
$scheme  = $parsed['scheme'] ?? 'http';
$port    = $parsed['port'] ?? null;

$baseUrl = $scheme . '://' . $dominio
         . ($port ? ':' . $port : '');
```

Esto hace que en producción (sin puerto explícito en APP_URL) funcione automáticamente.

---

## Bug 2 — Tabla "usuarios" no existe (debe ser "users")

### Síntoma

```
SQLSTATE[42P01]: Undefined table: 7 ERROR:
relation "usuarios" does not exist
LINE 1: select * from "usuarios" where "id" = $1 limit 1
```

### Causa

El modelo `User` (o uno de sus relacionados) tiene la tabla sobreescrita en español:

```php
protected $table = 'usuarios';
```

Pero las migraciones de Laravel crean la tabla como `users` (nombre por defecto).
La tabla `usuarios` nunca fue creada.

### Localizar

```bash
grep -r "usuarios" app/Models/
grep -r "usuarios" database/migrations/
```

### Fix — opción A: eliminar el override si la tabla es "users"

Si la migración crea `users` y el modelo dice `usuarios`, eliminar la línea:

```php
// app/Models/User.php  (o el modelo que falle)

// ELIMINAR esta línea:
protected $table = 'usuarios';

// Laravel usará 'users' por defecto
```

### Fix — opción B: si la tabla real es "usuarios", verificar la migración

Si el proyecto decidió usar `usuarios` como nombre de tabla, verificar que
la migración exista y se haya ejecutado:

```bash
php artisan migrate:status | grep usuario
```

Si no aparece como `Ran`, ejecutar:

```bash
php artisan migrate
```

### Contexto tenant

El error ocurre en `demo.localhost:8000` — dentro del contexto del tenant.
Verificar que las migraciones de tenant también hayan corrido para este dominio:

```bash
php artisan tenants:migrate --tenants=df21b4b0-fdb8-43dd-8841-9de2ba7c6f38
```

O para todos los tenants:

```bash
php artisan tenants:migrate
```

---

## Resumen de cambios

| Archivo | Cambio |
|---|---|
| `ImpersonateController` | Construir URL con esquema + puerto desde `request()` o `APP_URL` |
| `app/Models/User.php` | Eliminar `protected $table = 'usuarios'` si la tabla real es `users` |
| Migraciones tenant | Correr `php artisan tenants:migrate` si la tabla no existe en el schema del tenant |
