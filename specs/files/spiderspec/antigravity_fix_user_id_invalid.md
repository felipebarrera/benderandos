# Antigravity — Fix: "The selected user id is invalid"

## Causa

La validación `exists:users,id` en `ImpersonateController::impersonar()`
corre contra la conexión `central` (landlord), donde ese `user_id` no existe.
El usuario pertenece al tenant, no a la DB central.

```php
// PROBLEMA: valida en la conexión central
$request->validate([
    'user_id' => 'required|integer|exists:users,id',
]);
```

## Fix

### Opción A — Inicializar el tenant antes de validar

```php
// app/Http/Controllers/Central/ImpersonateController.php

public function impersonar(Request $request, Tenant $tenant): JsonResponse
{
    $request->validate([
        'user_id' => 'required|integer',
    ]);

    // Inicializar contexto del tenant antes de buscar el usuario
    tenancy()->initialize($tenant);

    $user = \App\Models\Tenant\Usuario::find($request->user_id);

    if (!$user) {
        tenancy()->end();
        return response()->json(['message' => 'Usuario no encontrado en este tenant.'], 422);
    }

    $token = $user->createToken('impersonar')->plainTextToken;

    tenancy()->end();

    $dominio = $tenant->domains()->orderBy('id')->first()?->domain;

    if (!$dominio) {
        return response()->json(['message' => 'Tenant sin dominio configurado.'], 422);
    }

    $base   = config('app.url');
    $parsed = parse_url($base);
    $scheme = $parsed['scheme'] ?? 'http';
    $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';

    return response()->json([
        'token' => $token,
        'url'   => $scheme . '://' . $dominio . $port,
    ]);
}
```

### Opción B — Validar con la conexión del tenant explícita

```php
$request->validate([
    'user_id' => [
        'required',
        'integer',
        \Illuminate\Validation\Rule::exists('tenant.users', 'id'),
    ],
]);
```

La Opción A es más segura porque además verifica que el usuario
pertenece al tenant correcto antes de generar el token.

## Verificación

El modal de selección de usuarios debe funcionar end-to-end:

1. Clic en ⎆ → carga lista de usuarios del tenant ✓
2. Seleccionar usuario → `POST /impersonar` con `user_id` ✓  
3. Backend encuentra el usuario en el tenant ✓
4. Genera token y devuelve URL con puerto ✓
5. Abre `http://demo.localhost:8000/?token=...` en nueva pestaña
