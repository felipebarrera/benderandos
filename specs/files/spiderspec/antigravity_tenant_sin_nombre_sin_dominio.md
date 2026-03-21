# Antigravity — Tenant sin nombre y sin dominio registrado

## Síntomas observados

1. **Lista de tenants muestra el UUID en lugar del nombre** — el campo `name` del tenant está vacío
2. **Impersonar redirige a `{uuid}.benderand.cl`** — el tenant no tiene ningún dominio en la tabla `domains`

Ambos problemas son del mismo tenant: `df21b4b0-fdb8-43dd-8841-9de2ba7c6f38`

---

## Problema 1 — Tenant sin nombre

### Causa

El tenant fue creado sin pasar el campo `name`, o el modelo Tenant guarda el nombre
dentro de `data->name` (columna JSON) pero la vista lo lee de `$tenant->name` (columna directa),
o viceversa.

### Verificar en DB

```sql
SELECT id, name, data FROM tenants WHERE id = 'df21b4b0-fdb8-43dd-8841-9de2ba7c6f38';
```

**Caso A** — `name` es null y `data` tiene el nombre:
```json
{ "name": "Ferretería Don Pedro", "rubro": "ferreteria" }
```
→ La vista debe leer `$tenant->data['name']` o agregar un accessor en el modelo.

**Caso B** — `name` está vacío y `data` también:
→ El tenant fue creado incompleto. Actualizar directamente:

```php
// tinker
$tenant = Tenant::find('df21b4b0-fdb8-43dd-8841-9de2ba7c6f38');
$tenant->name = 'Ferretería Don Pedro';  // o el nombre que corresponda
$tenant->save();
```

### Fix en el modelo (si el nombre vive en data)

```php
// app/Models/Tenant.php

public function getNameAttribute(): string
{
    // Leer de columna directa, con fallback a data JSON
    return $this->attributes['name']
        ?? $this->data['name']
        ?? 'Sin nombre';
}
```

### Fix en la vista de lista de tenants

En el blade de la tabla, la celda del nombre actualmente muestra vacío:

```php
// ANTES (nombre vacío porque $tenant->name es null)
<div style="font-weight:600">{{ $tenant->name }}</div>

// DESPUÉS (con fallback)
<div style="font-weight:600">
    {{ $tenant->name ?? $tenant->data['name'] ?? '—' }}
</div>
```

---

## Problema 2 — Tenant sin dominio (causa del UUID en la URL de impersonar)

### Verificar en DB

```sql
SELECT tenant_id, domain FROM domains WHERE tenant_id = 'df21b4b0-fdb8-43dd-8841-9de2ba7c6f38';
```

Si no retorna filas, el tenant no tiene dominio registrado.

### Registrar el dominio desde tinker

```bash
php artisan tinker
```

```php
$tenant = Tenant::find('df21b4b0-fdb8-43dd-8841-9de2ba7c6f38');

// Registrar dominio — ajustar el slug según el nombre real del tenant
$tenant->domains()->create([
    'domain' => 'ferreteria-don-pedro.benderand.cl',
]);
```

### Fix en el controlador de impersonar

El controlador debe validar que el tenant tenga dominio antes de redirigir,
y nunca usar `$tenant->id` como subdominio:

```php
// app/Http/Controllers/Central/ImpersonateController.php

public function impersonar(Tenant $tenant)
{
    // ... log de auditoría ...

    $token = $this->generarToken($tenant);

    // NUNCA usar $tenant->id como dominio
    $dominio = $tenant->domains()->orderBy('id')->first()?->domain;

    if (!$dominio) {
        return response()->json([
            'message' => "El tenant '{$tenant->name}' no tiene dominio configurado.",
        ], 422);
    }

    $url = 'https://' . $dominio;

    return response()->json([
        'token' => $token,
        'url'   => $url,
    ]);
}
```

---

## Fix preventivo en el seeder / creación de tenants

Asegurarse que `TenantsPruebaSeeder` y cualquier flujo de creación de tenant
siempre registre el dominio después de crear el tenant:

```php
$tenant = Tenant::create([
    'id'   => (string) Str::uuid(),
    'name' => $data['name'],      // <-- siempre pasar name
    'data' => [
        'rubro' => $data['rubro'],
        'plan'  => 'trial',
    ],
]);

// Siempre crear el dominio inmediatamente después
$tenant->domains()->create([
    'domain' => $data['slug'] . '.benderand.cl',
]);
```

---

## Resumen de cambios

| Dónde | Qué |
|---|---|
| `tenants` (DB) | Actualizar `name` del tenant `df21b4b0` si está vacío |
| `domains` (DB) | Insertar fila con el dominio del tenant `df21b4b0` |
| `app/Models/Tenant.php` | Accessor `getNameAttribute` con fallback a `data['name']` |
| Blade lista tenants | Mostrar `$tenant->name ?? $tenant->data['name'] ?? '—'` |
| `ImpersonateController` | Usar `$tenant->domains()->first()->domain` en lugar de `$tenant->id` |
| Seeder / creación de tenants | Siempre pasar `name` y crear dominio en el mismo flujo |
