# Antigravity — Fix: relation "usuarios" — búsqueda exhaustiva

## Situación

El fix anterior apuntó a `app/Models/User.php` pero el error persiste.
Hay dos posibilidades:

1. El fix no fue aplicado todavía
2. Hay otro modelo o archivo que también referencia la tabla `usuarios`

## Localizar TODOS los archivos que usan "usuarios"

```bash
# Buscar en todo el proyecto (excluye vendor y node_modules)
grep -rn "usuarios" app/ config/ database/ routes/ \
  --include="*.php" \
  --exclude-dir=vendor \
  --exclude-dir=node_modules
```

Los lugares más probables:

```bash
# Modelos
grep -rn "usuarios" app/Models/

# Migraciones
grep -rn "usuarios" database/migrations/

# Seeders
grep -rn "usuarios" database/seeders/

# Service Providers
grep -rn "usuarios" app/Providers/

# Auth config
grep -rn "usuarios" config/auth.php
```

## Fix más probable — config/auth.php

Laravel permite cambiar el nombre de tabla del provider de autenticación.
Si `config/auth.php` tiene esto, **ese es el origen real del error**:

```php
// config/auth.php

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
        'table'  => 'usuarios',  // <- ESTA línea causa el error
    ],
],
```

### Fix

```php
// config/auth.php

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
        // Sin 'table' — usa el default del modelo
    ],
],
```

## Fix — app/Models/User.php (confirmar que está eliminado)

```php
// app/Models/User.php

// VERIFICAR que esta línea NO existe:
protected $table = 'usuarios';

// Si existe, eliminarla.
```

## Fix — Migración con nombre incorrecto

Si la migración crea la tabla como `usuarios` en lugar de `users`:

```bash
grep -rn "create_usuarios\|Schema::create.*usuarios" database/migrations/
```

Si existe, la tabla en la DB del tenant es `usuarios` y la migración nunca creó `users`.
En ese caso hay dos opciones:

**Opción A** — Renombrar la tabla en la migración (requiere rollback):
```php
// Cambiar en la migración
Schema::create('users', function (Blueprint $table) { // era 'usuarios'
```

**Opción B** — Crear una nueva migración que renombra la tabla:
```php
Schema::rename('usuarios', 'users');
```

## Verificación final

```bash
# Confirmar qué tablas existen en el schema del tenant
docker exec benderandos_app php artisan tinker \
  --execute="
    tenancy()->initialize('df21b4b0-fdb8-43dd-8841-9de2ba7c6f38');
    \$tables = \DB::connection('tenant')
        ->select(\"SELECT tablename FROM pg_tables WHERE schemaname = 'public'\");
    foreach(\$tables as \$t) echo \$t->tablename . PHP_EOL;
    tenancy()->end();
  "
```

Esto muestra todas las tablas del tenant — confirma si existe `users`, `usuarios` o ninguna.
