# Antigravity — Fix incompleto: "usuarios" sigue siendo referenciado

## Situación

El walkthrough anterior renombró la tabla y actualizó modelos y migraciones,
pero el error persiste. Hay al menos un archivo que quedó sin actualizar.

## Encontrar el archivo que quedó

```bash
grep -rn "usuarios" app/ config/ database/ routes/ \
  --include="*.php" \
  --exclude-dir=vendor \
  --exclude-dir=node_modules
```

Este comando muestra exactamente qué archivo y qué línea aún referencia `usuarios`.

## Lugares que el walkthrough probablemente no cubrió

### 1. config/auth.php — el más común que se olvida

```php
// Buscar
grep -n "usuarios" config/auth.php

// Si tiene esto, es el culpable:
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Tenant\Usuario::class,
        'table'  => 'usuarios',  // <- eliminar esta línea
    ],
],
```

### 2. Algún modelo con $table que no fue listado en el walkthrough

```bash
grep -rn "protected \$table" app/Models/
```

Verificar que ninguno diga `'usuarios'`.

### 3. Una relación hasMany / belongsTo que usa el nombre de tabla directamente

```bash
grep -rn "'usuarios'" app/Models/
grep -rn "\"usuarios\"" app/Models/
```

### 4. Un scope o query builder con tabla hardcodeada

```bash
grep -rn "from.*usuarios\|join.*usuarios\|table.*usuarios" app/
```

### 5. Algún seeder o factory que referencia la tabla

```bash
grep -rn "usuarios" database/seeders/ database/factories/
```

### 6. Un Policy o Gate que resuelve el modelo por nombre de tabla

```bash
grep -rn "usuarios" app/Policies/ app/Http/
```

## Acción inmediata

Ejecutar el grep completo y pegar el resultado — con eso se identifica
el archivo exacto en una sola pasada:

```bash
grep -rn "usuarios" . \
  --include="*.php" \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git
```
