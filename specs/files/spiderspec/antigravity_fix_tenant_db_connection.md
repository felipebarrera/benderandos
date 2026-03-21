# Antigravity — Fix: Tenant DB Connection Not Configured

## Cambios

### [MODIFY] config/database.php

Agregar la conexión `tenant` en el array `connections`.
Stancl/Tenancy la sobreescribe dinámicamente al inicializar cada tenant.

```php
// config/database.php

'connections' => [

    'central' => [
        'driver'         => 'pgsql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_HOST', '127.0.0.1'),
        'port'           => env('DB_PORT', '5432'),
        'database'       => env('DB_DATABASE', 'forge'),
        'username'       => env('DB_USERNAME', 'forge'),
        'password'       => env('DB_PASSWORD', ''),
        'charset'        => 'utf8',
        'prefix'         => '',
        'prefix_indexes' => true,
        'search_path'    => 'public',
        'sslmode'        => 'prefer',
    ],

    // Plantilla que Stancl/Tenancy sobreescribe por tenant
    'tenant' => [
        'driver'         => 'pgsql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_HOST', '127.0.0.1'),
        'port'           => env('DB_PORT', '5432'),
        'database'       => env('DB_DATABASE', 'forge'), // Stancl lo reemplaza
        'username'       => env('DB_USERNAME', 'forge'),
        'password'       => env('DB_PASSWORD', ''),
        'charset'        => 'utf8',
        'prefix'         => '',
        'prefix_indexes' => true,
        'search_path'    => 'public',
        'sslmode'        => 'prefer',
    ],

],
```

---

### [MODIFY] app/Providers/AppServiceProvider.php

Agregar guard antes de cualquier llamada a `Schema::connection('tenant')`
para evitar el error si por alguna razón la conexión no está inicializada aún.

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    // Guard: solo operar sobre la conexión tenant si está configurada e inicializada
    if (config('database.connections.tenant')) {
        // Cualquier lógica que antes llamaba Schema::connection('tenant') sin guard
        // Schema::connection('tenant')->...
    }
}
```

---

## Verificación

### Automatizada

```bash
# 1. Verificar que la conexión tenant está definida
docker exec benderandos_app php artisan tinker \
  --execute="var_dump(config('database.connections.tenant'));"
# Esperado: array con los datos de conexión (no NULL)

# 2. Verificar que el tenant inicializa y la tabla existe
docker exec benderandos_app php artisan tinker \
  --execute="tenancy()->initialize('df21b4b0-fdb8-43dd-8841-9de2ba7c6f38'); var_dump(Schema::connection('tenant')->hasTable('users'));"
# Esperado: bool(true)
```

> Nota: el segundo comando verifica `users` (no `usuarios`).
> Si retorna `bool(false)` correr `php artisan tenants:migrate` para crear las tablas del tenant.

### Manual

Acceder al panel Super Admin → Tenants → hacer clic en ⎆ Impersonar sobre cualquier tenant.
No deben aparecer errores `Database connection [tenant] is not configured` en la UI ni en los logs.

```bash
docker exec benderandos_app tail -f storage/logs/laravel.log | grep -i "tenant\|connection"
```
