# BENDERAND — H26: LAYOUT, CLIENTE LANDING, AGENDA Y ROLES
**Fix layout operador · Cliente desde landing · Roles de agenda · Recursos**
*Marzo 2026 · Antigravity*

---

## PROBLEMAS IDENTIFICADOS (5)

1. **Cliente que pagó por el landing no aparece en `/admin/clientes`**
2. **Recepcionista (cajero) tiene vista de agenda propia** — no debería tener `prof-shell` ni acceso a agenda personal
3. **Layout operador/admin-agenda distinto al layout admin** — el sidebar no llega al borde superior visible, el `prof-shell` está dentro de `<main>` en vez de ser root del layout
4. **Admin tiene "Mi Agenda" en el sidebar general** — debe ser condicional: solo si el usuario tiene `recurso_id` asignado
5. **No hay forma de desactivar agenda a un usuario** — falta control de `recurso_id` en la vista de usuarios

---

## ANÁLISIS DE LOS HTMLS RECIBIDOS

### Problema del layout — causa raíz

**Admin (`/admin/clientes`)** — estructura correcta:
```
<body>
  <header mobile-topbar />          ← 52px
  <div class="flex flex-1">
    <aside id="sidebar" />           ← sidebar propio, fixed inset-y-0
    <main class="flex-1" />          ← contenido principal
  </div>
  <nav mobile-nav />
</body>
```

**Operador/cajero agenda (`/pos/agenda`)** — estructura rota:
```
<body>
  <header mobile-topbar />           ← 52px
  <div class="flex flex-1">
    <!-- sidebar vacío — generado por layout.blade diferente -->
    <main class="flex-1">
      <div class="prof-shell">       ← ← ← ERROR: prof-shell DENTRO de main
        <nav class="prof-nav" />
        <div class="prof-body" />
      </div>
    </main>
  </div>
</body>
```

El `prof-shell` está anidado dentro del `<main>` que ya tiene `overflow-y:auto`. Eso hace que el sidebar del shell compita con el scroll del main. La solución correcta es que la vista de agenda use el MISMO layout base que admin (con `<aside>` propio), no el layout genérico con `<main>` scrolleable.

---

## FIX 1 — CLIENTE DESDE LANDING NO APARECE EN CLIENTES

### Diagnóstico

El flujo de pago WebPay desde el portal crea al cliente en la tabla `clientes` pero probablemente también crea un registro en `usuarios` con rol `cliente`. El endpoint `GET /api/clientes` filtra por `rol != 'cliente'` o usa una query que excluye a quienes vinieron del portal.

Verificar en `ClienteController.php`:

```php
// BUSCAR este patrón o similar y corregirlo:
// MAL: excluye clientes del portal
$clientes = Cliente::where('origen', '!=', 'portal')->get();

// BIEN: todos los clientes del tenant
$clientes = Cliente::orderBy('nombre')->paginate(100);
```

También verificar que el `ClientePortalController` SÍ crea el cliente en la tabla `clientes`:

```php
// En el flujo de confirmación de pago WebPay (callback):
// Debe ejecutarse esto:
$cliente = Cliente::firstOrCreate(
    ['rut' => $datos['rut']],      // buscar por RUT si ya existe
    [
        'nombre'    => $datos['nombre'],
        'email'     => $datos['email'],
        'telefono'  => $datos['telefono'],
        'origen'    => 'portal',   // marcar origen pero NO excluir
    ]
);

// Y la cita debe quedar vinculada:
$cita->update(['cliente_id' => $cliente->id]);
```

### Fix en `ClienteController@index`

```php
public function index(Request $request)
{
    $q = $request->input('q', '');
    $perPage = $request->input('per_page', 100);

    $query = Cliente::query()
        ->when($q, fn($query) => $query->where(function($q2) use ($q) {
            $q2->where('nombre', 'ilike', "%{$q}%")
               ->orWhere('rut', 'ilike', "%{$q}%")
               ->orWhere('email', 'ilike', "%{$q}%");
        }))
        ->orderBy('nombre');

    // NO filtrar por origen — clientes del portal también deben aparecer
    return response()->json($query->paginate($perPage));
}
```

---

## FIX 2 — LAYOUT: OPERADOR Y CAJERO DEBEN USAR EL MISMO SIDEBAR QUE ADMIN

### Causa

Las vistas `/pos/agenda` y el equivalente para cajero usan un layout Blade diferente al de `/admin/*`. El layout admin tiene el `<aside>` propio que ocupa `fixed inset-y-0`. El layout del operador genera un `<main>` scrolleable y el `prof-shell` queda anidado dentro.

### Fix en el layout Blade

Las vistas de agenda deben extender el **mismo layout** que admin, no el layout de POS. Cambiar en `agenda.blade.php` (vista del operador):

```php
// ANTES (extiende layout POS):
@extends('layouts.tenant')

// DESPUÉS (extiende layout admin o un layout propio que no tenga main scrolleable):
@extends('layouts.tenant_fullscreen')
```

### Crear `layouts/tenant_fullscreen.blade.php`

Este layout no tiene `<main class="flex-1 overflow-y-auto">` — el scroll lo manejan los hijos:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
</head>
<body style="background:#08080a;color:#e8e8f0;margin:0;padding:0;height:100vh;overflow:hidden;">

{{-- Mobile topbar --}}
<header class="mobile-topbar md:hidden">
    @include('partials.mobile_topbar')
</header>

{{-- Alertas de suscripción --}}
@include('partials.subscription_alerts')

{{-- Layout principal: sidebar + contenido sin scroll en el root --}}
<div style="display:flex;height:100vh;overflow:hidden;">

    {{-- Sidebar overlay mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden md:hidden"></div>

    {{-- Sidebar del rol --}}
    @include('partials.sidebar_' . auth()->user()->rol)

    {{-- Zona de contenido: el componente hijo controla su propio scroll --}}
    <div style="flex:1;overflow:hidden;display:flex;flex-direction:column;min-width:0;">
        @yield('content')
    </div>
</div>

{{-- Mobile bottom nav --}}
@include('partials.mobile_nav')

@stack('scripts')
</body>
</html>
```

### Estructura del componente de agenda (sin prof-shell dentro de main)

La vista de agenda pasa a ser el root del contenido, ya no está anidada:

```blade
{{-- resources/views/tenant/pos/agenda.blade.php --}}
@extends('layouts.tenant_fullscreen')

@section('content')
<div style="display:flex;height:100%;overflow:hidden;background:#08080a;">

    {{-- Sidebar de agenda (nav contextual) --}}
    <nav style="width:240px;min-width:240px;background:#0d0d11;border-right:1px solid #1e1e28;
                display:flex;flex-direction:column;height:100%;overflow:hidden;flex-shrink:0;">

        {{-- Brand + usuario --}}
        <div style="padding:20px;border-bottom:1px solid #1e1e28;flex-shrink:0;">
            <div style="font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:20px;">
                B<span style="color:#00e5a0">&</span>
            </div>
            <div style="font-size:13px;font-weight:600;color:#e8e8f0;margin-top:4px;">{{ $tenantNombre }}</div>
            <div style="font-size:11px;color:#00e5a0;font-family:'IBM Plex Mono',monospace;margin-top:4px;text-transform:capitalize;">{{ auth()->user()->rol }}</div>
        </div>

        {{-- KPIs del profesional --}}
        <div style="padding:16px 14px 12px;border-bottom:1px solid #1e1e28;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <span style="width:8px;height:8px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>
                <span style="font-size:13px;font-weight:700;color:#e8e8f0;">{{ auth()->user()->nombre }}</span>
            </div>
            <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:.5px;">
                {{ $recurso->especialidad ?? 'Sin especialidad' }}
            </div>
            <div id="kpisNav" style="margin-top:12px;">
                <div style="display:flex;justify-content:space-between;padding:3px 0;font-size:11px;">
                    <span style="color:#3a3a55;">Citas Hoy</span>
                    <span id="kpiCitasHoy" style="font-family:'IBM Plex Mono',monospace;font-weight:700;color:#00e5a0;">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:3px 0;font-size:11px;">
                    <span style="color:#3a3a55;">Esta semana</span>
                    <span id="kpiCitasSem" style="font-family:'IBM Plex Mono',monospace;font-weight:700;color:#00e5a0;">—</span>
                </div>
            </div>
        </div>

        {{-- Items de nav --}}
        <div style="padding:8px 0;flex:1;overflow-y:auto;min-height:0;">
            <div class="nav-section-lbl">Agenda</div>
            <a onclick="cambiarTab('agenda')" id="nav-agenda" class="pni active">📅 Agenda</a>
            <a onclick="cambiarTab('pacientes')" id="nav-pacientes" class="pni">👤 Clientes</a>
            <a onclick="cambiarTab('seguimiento')" id="nav-seguimiento" class="pni">
                📋 Seguimiento
                <span class="pni-badge" id="badgeSeguimiento" style="display:none;">0</span>
            </a>

            <div class="nav-section-lbl" style="margin-top:16px;">Operación</div>
            {{-- Enlace contextual según rol --}}
            @if(auth()->user()->rol === 'admin')
                <a href="/admin/dashboard" class="pni">← Volver a Admin</a>
            @elseif(auth()->user()->rol === 'cajero')
                <a href="/pos" class="pni">← Volver a POS</a>
            @else
                <a href="/operario" class="pni">← Volver a Stock</a>
            @endif
        </div>

        {{-- Footer usuario + logout --}}
        <div style="padding:12px;border-top:1px solid #1e1e28;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(0,229,160,.15);
                            color:#00e5a0;display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;border:1px solid rgba(0,229,160,.3);">
                    {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:600;color:#e8e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ auth()->user()->nombre }}
                    </div>
                    <div style="font-size:11px;color:#7878a0;text-transform:capitalize;">{{ auth()->user()->rol }}</div>
                </div>
            </div>
            <form action="{{ route('web.logout') }}" method="POST">
                @csrf
                <button type="submit" style="display:flex;align-items:center;gap:8px;padding:7px 10px;
                    width:100%;background:none;border:none;border-radius:6px;color:#7878a0;
                    font-size:12px;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;">
                    ⏻ Cerrar sesión
                </button>
            </form>
        </div>
    </nav>

    {{-- Contenido principal (tabs) --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">
        <div id="tab-agenda" class="tab-panel" style="display:flex;flex-direction:column;height:100%;">
            <div id="toolbarCalendario"></div>
            <div class="prof-content" id="timelineCitas" style="flex:1;overflow-y:auto;padding:12px;"></div>
        </div>
        <div id="tab-pacientes" class="tab-panel" style="display:none;flex-direction:column;height:100%;">
            <div style="padding:10px 16px;background:#111115;border-bottom:1px solid #1e1e28;flex-shrink:0;">
                <span style="font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:13px;">Clientes</span>
            </div>
            <div id="listaPacientes" style="flex:1;overflow-y:auto;padding:16px;"></div>
        </div>
        <div id="tab-seguimiento" class="tab-panel" style="display:none;flex-direction:column;height:100%;">
            <div style="padding:10px 16px;background:#111115;border-bottom:1px solid #1e1e28;flex-shrink:0;">
                <span style="font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:13px;">Seguimiento</span>
            </div>
            <div id="listaSeguimiento" style="flex:1;overflow-y:auto;padding:16px;"></div>
        </div>
    </div>

    {{-- Panel de detalle (derecha) --}}
    <div class="prof-detalle cerrado" id="profDetalle"></div>
</div>
@endsection
```

### En mobile: el `prof-nav` se oculta, el contenido ocupa todo

```css
@media (max-width: 767px) {
    /* El nav lateral se oculta en mobile — se usa el bottom nav */
    #agendaSidebar { display: none; }
}
```

---

## FIX 3 — RECEPCIONISTA (CAJERO) NO DEBE VER AGENDA PERSONAL

### Regla de roles para agenda

| Rol | Puede ver `/pos/agenda` | Tiene panel prof-shell propio |
|---|---|---|
| `admin` | Sí — ve todas las citas | Solo si tiene `recurso_id` |
| `operario` | Sí — solo sus citas | Sí (es médico/profesional) |
| `cajero` | **NO** — redirigir a `/pos` | **NO** |

### Fix en `AgendaController` o middleware de ruta

```php
// routes/tenant.php
Route::get('/pos/agenda', [AgendaController::class, 'posAgenda'])
    ->middleware(['auth', 'module:M08', function($req, $next) {
        // Cajero NO tiene agenda personal
        if (auth()->user()->rol === 'cajero') {
            return redirect('/pos')->with('info', 'La agenda personal es solo para profesionales.');
        }
        return $next($req);
    }]);
```

O en el controller:

```php
public function posAgenda()
{
    $user = auth()->user();

    // Cajero → redirigir al POS
    if ($user->rol === 'cajero') {
        return redirect('/pos');
    }

    // Verificar que tiene recurso asignado (solo si es operario)
    if ($user->rol === 'operario' && !$user->recurso_id) {
        // Mostrar la vista pero con mensaje de "sin recurso asignado"
    }

    $recurso = $user->recurso_id
        ? \App\Models\Tenant\Recurso::find($user->recurso_id)
        : null;

    return view('tenant.pos.agenda', [
        'recurso'       => $recurso,
        'tenantNombre'  => tenant('nombre') ?? config('app.name'),
        'userRol'       => $user->rol,
    ]);
}
```

### Fix en sidebar de admin — "Mi Agenda" solo si tiene recurso

En el partial del sidebar de admin (`resources/views/partials/sidebar_admin.blade.php`):

```blade
{{-- Solo mostrar "Mi Agenda" si el admin tiene recurso_id asignado --}}
@if(auth()->user()->recurso_id)
<div class="nav-section-lbl">Mi Agenda</div>
<a href="/pos/agenda" class="nav-link-item">
    <svg ...>...</svg>
    Mi Agenda
</a>
@endif
```

---

## FIX 4 — GESTIÓN DE RECURSO/AGENDA POR USUARIO

### Qué falta

En `/admin/usuarios`, al editar un usuario, debe poder:
1. Asignar o desasignar `recurso_id` (vincularlo a un recurso de agenda)
2. Ver claramente si ese usuario es "profesional con agenda" o no

### Cambios en `UsuarioController`

```php
// GET /api/usuarios/{id} — incluir recurso
public function show($id)
{
    $usuario = Usuario::with('recurso')->findOrFail($id);
    return response()->json([
        'id'         => $usuario->id,
        'nombre'     => $usuario->nombre,
        'email'      => $usuario->email,
        'rol'        => $usuario->rol,
        'recurso_id' => $usuario->recurso_id,
        'recurso'    => $usuario->recurso ? [
            'id'          => $usuario->recurso->id,
            'nombre'      => $usuario->recurso->nombre,
            'especialidad'=> $usuario->recurso->especialidad,
        ] : null,
        'tiene_agenda' => (bool) $usuario->recurso_id,
    ]);
}

// PUT /api/usuarios/{id}
public function update(Request $request, $id)
{
    $usuario = Usuario::findOrFail($id);
    $usuario->update([
        'nombre'      => $request->nombre,
        'rol'         => $request->rol,
        'recurso_id'  => $request->recurso_id ?? null,  // null = sin agenda
    ]);
    return response()->json(['ok' => true]);
}
```

### Nuevo endpoint — listar recursos disponibles para asignar

```php
// GET /api/recursos — lista de recursos del tenant para asignar a usuarios
Route::get('/api/recursos', function() {
    $recursos = \App\Models\Tenant\Recurso::orderBy('nombre')->get(['id','nombre','especialidad','tipo']);
    return response()->json($recursos);
});
```

### UI en `/admin/usuarios` — modal de edición con campo recurso

En el JS de la vista de usuarios, agregar al modal de edición:

```javascript
// Al abrir modal de edición de usuario, cargar recursos disponibles
async function editarUsuario(u) {
    // ... campos existentes ...

    // Cargar recursos para el select
    try {
        const recursos = await api('GET', '/api/recursos');
        const opts = `<option value="">Sin agenda (no es profesional)</option>` +
            recursos.map(r => `<option value="${r.id}" ${u.recurso_id == r.id ? 'selected' : ''}>
                ${r.nombre} — ${r.especialidad ?? 'Sin especialidad'}
            </option>`).join('');
        document.getElementById('muRecurso').innerHTML = opts;
    } catch(e) { console.error(e); }

    // Indicador visual
    const tieneAgenda = !!u.recurso_id;
    document.getElementById('agendaStatus').innerHTML = tieneAgenda
        ? `<span style="color:#00e5a0;font-size:11px;">✓ Tiene agenda asignada</span>`
        : `<span style="color:#3a3a55;font-size:11px;">Sin agenda personal</span>`;

    openModal('mUsuario');
}

// En guardarUsuario, incluir recurso_id:
const payload = {
    nombre:     document.getElementById('muNombre').value,
    rol:        document.getElementById('muRol').value,
    recurso_id: document.getElementById('muRecurso').value || null,
};
```

En el HTML del modal de usuarios, agregar el campo:

```html
<div class="field">
    <label class="label">Agenda / Recurso</label>
    <div id="agendaStatus" style="margin-bottom:6px;"></div>
    <select id="muRecurso">
        <option value="">Sin agenda (no es profesional)</option>
        <!-- Se llena por JS -->
    </select>
    <div style="font-size:10px;color:#7878a0;margin-top:4px;">
        Solo asignar si el usuario es un profesional que atiende citas.
        Los cajeros y bodegueros no deben tener recurso asignado.
    </div>
</div>
```

---

## RESUMEN DE ARCHIVOS A MODIFICAR

| Archivo | Cambio | Prioridad |
|---|---|---|
| `app/Http/Controllers/Tenant/ClienteController.php` | Remover filtro que excluye clientes del portal en `index()` | 🔴 |
| `app/Http/Controllers/Tenant/ClientePortalController.php` | Verificar que pago WebPay crea `Cliente` con `cliente_id` en cita | 🔴 |
| `resources/views/layouts/tenant_fullscreen.blade.php` | CREAR — layout sin main scrolleable para agenda | 🔴 |
| `resources/views/tenant/pos/agenda.blade.php` | Extender `tenant_fullscreen`, estructura como HTML de admin | 🔴 |
| `app/Http/Controllers/Tenant/AgendaController.php` | `posAgenda()` — redirigir cajero, pasar recurso a la vista | 🔴 |
| `routes/tenant.php` | Middleware en `/pos/agenda` que bloquea cajero | 🟡 |
| `resources/views/partials/sidebar_admin.blade.php` | "Mi Agenda" condicional a `recurso_id` | 🟡 |
| `app/Http/Controllers/Tenant/UsuarioController.php` | `show()` y `update()` incluyen `recurso_id` | 🟡 |
| `resources/views/tenant/admin/usuarios.blade.php` | Modal con select de recurso y indicador visual | 🟡 |
| `routes/tenant.php` | `GET /api/recursos` para listar recursos disponibles | 🟡 |

---

## VERIFICACIÓN

```bash
# 1. Verificar que clientes del portal están en la tabla
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run demo-medico \
  --function=\"fn() => \App\Models\Tenant\Cliente::where('origen','portal')->get(['id','nombre','email'])->each(fn(\$c) => print(\$c->nombre.PHP_EOL));\""

# 2. Verificar que las citas del portal tienen cliente_id
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run demo-medico \
  --function=\"fn() => \DB::table('citas')->whereNull('cliente_id')->count();\""

# 3. Verificar recurso_id de usuarios
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run demo-medico \
  --function=\"fn() => \App\Models\Tenant\Usuario::select('nombre','rol','recurso_id')->get()->each(fn(\$u) => print(\$u->nombre.' ('.\$u->rol.') recurso:'.\$u->recurso_id.PHP_EOL));\""

# 4. Listar recursos del tenant
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run demo-medico \
  --function=\"fn() => \DB::table('recursos')->get()->each(fn(\$r) => print(\$r->id.' '.\$r->nombre.PHP_EOL));\""
```

---

## REGLA DEFINITIVA DE ROLES EN AGENDA

```
ACCESO A /pos/agenda:
  admin    → SÍ, ve todas las citas. "Mi Agenda" en sidebar SOLO si tiene recurso_id.
  operario → SÍ, ve solo sus citas. Siempre tiene panel prof-shell.
  cajero   → NO. Redirect a /pos. No tiene agenda personal.
  bodega   → NO. Redirect a /operario.
  cliente  → NO. Redirect a /portal.

SIDEBAR ADMIN — "Mi Agenda":
  admin con recurso_id    → mostrar enlace a /pos/agenda
  admin sin recurso_id    → NO mostrar "Mi Agenda"

MODAL DE USUARIO:
  Puede asignarse recurso a: admin, operario
  NO se asigna recurso a:    cajero, bodega, cliente
```

---

*BenderAnd · H26 Layout + Cliente Landing + Roles Agenda · Marzo 2026 · Antigravity*
