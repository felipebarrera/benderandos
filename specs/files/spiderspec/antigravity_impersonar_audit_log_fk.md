# Antigravity — Bug: FK violation al impersonar tenant

## Contexto del error

Al ejecutar la acción **Impersonar** desde el panel Super Admin, el sistema intenta registrar en `audit_logs` usando `user_id = 1` (el super_admin del landlord). Sin embargo, la tabla `audit_logs` tiene una FK hacia `users` del contexto del tenant activo, y ese usuario no existe en ese tenant.

```
SQLSTATE[23503]: Foreign key violation: 7 ERROR:
insert or update on table "audit_logs" violates foreign key constraint "audit_logs_user_id_foreign"
DETAIL: Key (user_id)=(1) is not present in table "users".
SQL: insert into "audit_logs" ("user_id", "tenant_id", "accion", ...) values (1, df21b4b0-..., impersonar, ...)
```

## Diagnóstico

El flujo de impersonar ocurre desde el **contexto del landlord** (super_admin), pero al cambiar al tenant, el sistema intenta hacer el log de auditoría **dentro del schema/conexión del tenant destino**, donde `user_id = 1` no existe porque es un usuario del landlord.

Hay dos problemas compuestos:

1. `audit_logs` usa FK a `users` — esto impide registrar actores externos al tenant.
2. El servicio de auditoría no distingue si el actor es un super_admin (landlord) vs un usuario del tenant.

## Solución

### 1. Hacer `user_id` nullable en `audit_logs` + agregar campo `actor_type`

Modificar la tabla `audit_logs` para soportar actores que no pertenecen al tenant:

```php
// database/migrations/[timestamp]_update_audit_logs_actor_fields.php

Schema::table('audit_logs', function (Blueprint $table) {
    // Hacer user_id nullable para permitir actores externos (super_admin)
    $table->unsignedBigInteger('user_id')->nullable()->change();

    // Nuevo campo para identificar el tipo de actor
    $table->string('actor_type')->default('user')->after('user_id');
    // Valores posibles: 'user' | 'super_admin'

    // Email del super_admin para trazabilidad cuando actor_type = super_admin
    $table->string('actor_email')->nullable()->after('actor_type');
});
```

### 2. Actualizar el servicio de auditoría

En el servicio o trait que genera el log (p.ej. `AuditService` o `LogsActivity`), separar la lógica según si el actor es super_admin:

```php
// app/Services/AuditService.php

public static function log(string $accion, array $detalles = []): void
{
    $tenantId = tenant('id');
    $user = auth()->user();

    // Detectar si el actor es super_admin (landlord, no pertenece al tenant)
    $esSuperAdmin = $user && $user->is_super_admin; // o rol, o guard landlord

    AuditLog::create([
        'user_id'     => $esSuperAdmin ? null : $user?->id,
        'actor_type'  => $esSuperAdmin ? 'super_admin' : 'user',
        'actor_email' => $esSuperAdmin ? $user->email : null,
        'tenant_id'   => $tenantId,
        'accion'      => $accion,
        'ip'          => request()->ip(),
        'detalles'    => $detalles,
    ]);
}
```

### 3. Actualizar la acción de impersonar para pasar el contexto correcto

En el controlador o acción de impersonar (p.ej. `ImpersonateController` o `SuperAdminImpersonateAction`):

```php
// Antes de switchear al tenant, loguear con contexto correcto
AuditService::log('impersonar', [
    'super_admin_email' => auth()->user()->email,
    'tenant_name'       => $tenant->name ?? null,
    'tenant_id'         => $tenant->id,
]);
```

Si el log ocurre **después** del switch al tenant (dentro del contexto del tenant), asegurarse que el guard del super_admin esté activo y se detecte correctamente con `$user->is_super_admin`.

### 4. Actualizar el modelo `AuditLog`

```php
// app/Models/AuditLog.php

protected $fillable = [
    'user_id',
    'actor_type',   // nuevo
    'actor_email',  // nuevo
    'tenant_id',
    'accion',
    'ip',
    'detalles',
];

// El cast de detalles si es JSON
protected $casts = [
    'detalles' => 'array',
];
```

## Consideraciones adicionales

- La FK `audit_logs_user_id_foreign` puede mantenerse como restricción opcional (FK nullable), o eliminarse si se prefiere que `audit_logs` no dependa de `users`. Depende de si se necesita JOIN a usuarios frecuentemente.
- En la UI del panel Super Admin (sección Ficha Tenant → Historial), mostrar el campo `actor_email` cuando `actor_type = super_admin` en lugar del nombre de usuario.
- Para los tenants de prueba que se están creando por industria, verificar que el flujo de impersonar llame al log **antes** del `tenancy()->initialize($tenant)` para evitar este problema por completo en el contexto de onboarding.

## Resumen del cambio

| Archivo | Cambio |
|---|---|
| `migrations/[ts]_update_audit_logs_actor_fields.php` | `user_id` nullable, agregar `actor_type`, `actor_email` |
| `app/Models/AuditLog.php` | Agregar campos al `$fillable` |
| `app/Services/AuditService.php` | Detectar super_admin, log sin user_id con email |
| `ImpersonateController` o acción equivalente | Pasar contexto correcto al log |
