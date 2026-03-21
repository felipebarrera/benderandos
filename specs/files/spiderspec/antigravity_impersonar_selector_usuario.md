# Antigravity — Impersonar: selector de usuario del tenant

## Contexto

Actualmente impersonar genera un token para el admin del tenant sin preguntar.
El objetivo es mostrar primero la lista de usuarios del tenant para elegir
con qué usuario ingresar — útil para revisar la UI desde cada rol.

## Flujo nuevo

```
Super Admin hace clic en ⎆ Impersonar
        ↓
GET /central/tenants/{id}/usuarios
        ↓
Modal muestra lista de usuarios del tenant con nombre, email y rol
        ↓
Super Admin selecciona un usuario y confirma
        ↓
POST /central/tenants/{id}/impersonar  { user_id: X }
        ↓
Backend genera token para ese user_id y devuelve URL
        ↓
Redirect al tenant con ?token=...
```

---

## 1. Nuevo endpoint: listar usuarios del tenant

```php
// routes/central.php (o web.php del contexto central)

Route::get('/central/tenants/{tenant}/usuarios', [ImpersonateController::class, 'usuarios']);
Route::post('/central/tenants/{tenant}/impersonar', [ImpersonateController::class, 'impersonar']);
```

```php
// app/Http/Controllers/Central/ImpersonateController.php

public function usuarios(Tenant $tenant): JsonResponse
{
    // Inicializar el contexto del tenant para consultar su DB
    tenancy()->initialize($tenant);

    $usuarios = \App\Models\User::select('id', 'name', 'email', 'rol')
        ->orderByRaw("CASE rol WHEN 'admin' THEN 1 WHEN 'cajero' THEN 2 WHEN 'operario' THEN 3 ELSE 4 END")
        ->get();

    tenancy()->end();

    return response()->json($usuarios);
}

public function impersonar(Request $request, Tenant $tenant): JsonResponse
{
    $request->validate(['user_id' => 'required|integer']);

    // Log de auditoría antes de switchear contexto
    AuditService::log('impersonar', [
        'super_admin_email' => auth()->user()->email,
        'tenant_id'         => $tenant->id,
        'tenant_name'       => $tenant->name,
        'user_id_destino'   => $request->user_id,
    ]);

    // Generar token dentro del contexto del tenant
    tenancy()->initialize($tenant);

    $user = \App\Models\User::findOrFail($request->user_id);
    $token = $user->createToken('impersonar')->plainTextToken;

    tenancy()->end();

    // Construir URL desde APP_URL
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

---

## 2. Modal de selección en el frontend (JS en la vista de tenants)

Reemplazar la función `confirmImpersonate` actual:

```js
// Reemplazar el script existente en la vista de tenants

async function confirmImpersonate(tenantId) {
    // 1. Cargar usuarios del tenant
    let usuarios = [];
    try {
        const res = await fetch(`/central/tenants/${tenantId}/usuarios`, {
            headers: { 'Accept': 'application/json' }
        });
        usuarios = await res.json();
    } catch (e) {
        alert('Error al cargar usuarios del tenant.');
        return;
    }

    if (!usuarios.length) {
        alert('Este tenant no tiene usuarios registrados.');
        return;
    }

    // 2. Mostrar modal de selección
    showImpersonateModal(tenantId, usuarios);
}

function showImpersonateModal(tenantId, usuarios) {
    // Remover modal previo si existe
    document.getElementById('impersonate-modal')?.remove();

    const roleColors = {
        admin:    '#ff6b35',
        cajero:   '#00e5a0',
        operario: '#4488ff',
        bodega:   '#7878a0',
        cliente:  '#f5c518',
    };

    const filas = usuarios.map(u => `
        <tr style="cursor:pointer;transition:background .1s" 
            onmouseover="this.style.background='#1e1e28'" 
            onmouseout="this.style.background=''"
            onclick="selectUser(${u.id}, '${u.name}', '${u.rol}')">
            <td style="padding:10px 12px">
                <div style="font-weight:600;font-size:12px">${u.name}</div>
                <div style="font-family:monospace;font-size:10px;color:#7878a0">${u.email}</div>
            </td>
            <td style="padding:10px 12px">
                <span style="
                    display:inline-block;padding:2px 8px;border-radius:4px;
                    font-size:10px;font-weight:700;font-family:monospace;
                    background:${roleColors[u.rol] ?? '#333'}22;
                    color:${roleColors[u.rol] ?? '#aaa'}
                ">${u.rol}</span>
            </td>
            <td style="padding:10px 12px;text-align:right">
                <button onclick="event.stopPropagation();doImpersonate('${tenantId}', ${u.id})"
                    style="padding:4px 12px;border-radius:6px;border:none;
                           background:rgba(224,64,251,.15);color:#e040fb;
                           font-size:11px;font-weight:600;cursor:pointer">
                    Entrar
                </button>
            </td>
        </tr>
    `).join('');

    const modal = document.createElement('div');
    modal.id = 'impersonate-modal';
    modal.innerHTML = `
        <div onclick="this.parentElement.remove()" style="
            position:fixed;inset:0;background:rgba(0,0,0,.7);
            display:flex;align-items:center;justify-content:center;
            z-index:1000;padding:20px">
            <div onclick="event.stopPropagation()" style="
                background:#111115;border:1px solid #2a2a3a;border-radius:12px;
                width:100%;max-width:480px;overflow:hidden">
                <div style="padding:16px 20px;border-bottom:1px solid #1e1e28;
                            display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <div style="font-weight:700;font-size:14px">Seleccionar usuario</div>
                        <div style="font-size:11px;color:#7878a0;margin-top:2px">
                            Entrar al tenant como:
                        </div>
                    </div>
                    <button onclick="document.getElementById('impersonate-modal').remove()"
                        style="background:none;border:none;color:#7878a0;font-size:18px;cursor:pointer">✕</button>
                </div>
                <table style="width:100%;border-collapse:collapse;font-family:'IBM Plex Sans',sans-serif">
                    <thead>
                        <tr style="border-bottom:1px solid #1e1e28">
                            <th style="text-align:left;padding:8px 12px;font-size:10px;
                                       color:#7878a0;letter-spacing:1px;text-transform:uppercase">
                                Usuario
                            </th>
                            <th style="text-align:left;padding:8px 12px;font-size:10px;
                                       color:#7878a0;letter-spacing:1px;text-transform:uppercase">
                                Rol
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                </table>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function doImpersonate(tenantId, userId) {
    document.getElementById('impersonate-modal')?.remove();

    try {
        const response = await fetch(`/central/tenants/${tenantId}/impersonar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept':       'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId }),
        });

        const data = await response.json();

        if (data.token) {
            window.open(data.url + '?token=' + data.token, '_blank');
        } else {
            alert('Error: ' + (data.message ?? 'Sin respuesta del servidor'));
        }
    } catch (e) {
        alert('Error crítico al intentar impersonar.');
    }
}
```

---

## 3. Resumen de cambios

| Archivo | Cambio |
|---|---|
| `routes/central.php` | Agregar `GET /tenants/{tenant}/usuarios` |
| `ImpersonateController::usuarios()` | Nuevo método: lista usuarios del tenant |
| `ImpersonateController::impersonar()` | Recibe `user_id` en body, genera token para ese usuario |
| Vista blade de tenants | Reemplazar `confirmImpersonate` con modal de selección |
