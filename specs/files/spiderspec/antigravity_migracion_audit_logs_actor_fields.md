# Antigravity — Migración faltante: columnas actor_type y actor_email en audit_logs

## Causa

El código ya fue actualizado para usar `actor_type` y `actor_email` en `audit_logs`,
pero la migración que agrega esas columnas nunca fue creada ni ejecutada.
La tabla en la base de datos central (connection: `central`) no tiene esas columnas.

## Solución

### 1. Crear la migración

```bash
php artisan make:migration add_actor_fields_to_audit_logs_table
```

### 2. Contenido de la migración

```php
// database/migrations/[timestamp]_add_actor_fields_to_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Esta migración corre en la conexión central (landlord)
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('audit_logs', function (Blueprint $table) {
            // Hacer user_id nullable (actores externos al tenant no tienen user en la tabla users)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Tipo de actor: 'user' para usuarios del tenant, 'super_admin' para el landlord
            $table->string('actor_type')->default('user')->after('user_id');

            // Email del super_admin cuando actor_type = 'super_admin'
            $table->string('actor_email')->nullable()->after('actor_type');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_type', 'actor_email']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
```

### 3. Ejecutar la migración

```bash
php artisan migrate --path=database/migrations/[timestamp]_add_actor_fields_to_audit_logs_table.php
```

O simplemente:

```bash
php artisan migrate
```

## Verificar

Después de correr la migración, confirmar que las columnas existen:

```sql
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'audit_logs'
  AND column_name IN ('user_id', 'actor_type', 'actor_email');
```

Resultado esperado:

| column_name | data_type | is_nullable |
|---|---|---|
| user_id | bigint | YES |
| actor_type | character varying | NO |
| actor_email | character varying | YES |

## Nota

Si `audit_logs` existe tanto en la base central como en los schemas de tenant,
verificar si la migración de tenant también necesita estas columnas.
En ese caso agregar la misma migración para el contexto tenant usando la conexión del tenant en lugar de `central`.
