# Antigravity — Impersonar: construir URL del tenant desde config en lugar de hardcodear puerto

## Problema

La URL de impersonar se construye concatenando el dominio del tenant directamente,
sin respetar el puerto ni el esquema del entorno:

```php
// MAL — hardcodeado, rompe en local con :8000
$url = 'https://' . $dominio;
```

## Solución

Usar la `APP_URL` del `.env` como base para extraer esquema y puerto,
de modo que en local funcione con `:8000` y en producción sin puerto.

### .env

```dotenv
# Local
APP_URL=http://localhost:8000

# Producción
APP_URL=https://benderand.cl
```

### Fix en ImpersonateController

```php
// app/Http/Controllers/Central/ImpersonateController.php

$dominio = $tenant->domains()->orderBy('id')->first()?->domain;

if (!$dominio) {
    return response()->json(['message' => 'Tenant sin dominio configurado.'], 422);
}

$tenantUrl = $this->buildTenantUrl($dominio);

return response()->json([
    'token' => $token,
    'url'   => $tenantUrl,
]);

// ---

private function buildTenantUrl(string $dominio): string
{
    $base   = config('app.url');         // http://localhost:8000 o https://benderand.cl
    $parsed = parse_url($base);

    $scheme = $parsed['scheme'] ?? 'http';
    $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';

    return $scheme . '://' . $dominio . $port;
}
```

### Resultado

| Entorno | APP_URL | URL generada |
|---|---|---|
| Local | `http://localhost:8000` | `http://demo.localhost:8000` |
| Producción | `https://benderand.cl` | `https://demo.benderand.cl` |

---

## Alternativa: config dedicado

Si se prefiere tener una URL base explícita separada del `APP_URL` del landlord,
agregar una variable en `.env` y un config dedicado:

```dotenv
# .env
TENANT_BASE_URL=http://localhost:8000
```

```php
// config/tenancy.php  (o config/benderand.php)
'tenant_base_url' => env('TENANT_BASE_URL', env('APP_URL')),
```

```php
// En el controlador
private function buildTenantUrl(string $dominio): string
{
    $base   = config('tenancy.tenant_base_url');
    $parsed = parse_url($base);

    $scheme = $parsed['scheme'] ?? 'http';
    $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';

    return $scheme . '://' . $dominio . $port;
}
```

Esto permite configurar la URL base del tenant independientemente del APP_URL del panel central.
