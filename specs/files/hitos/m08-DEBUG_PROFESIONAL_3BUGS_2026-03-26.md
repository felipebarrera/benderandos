# DEBUG — Vista Profesional: 3 Bugs Post-Deploy
**Fecha:** 2026-03-26  **Clasificación:** E-CONFIG + E-UI + E-DATA

---

## BUG 1 — Menú "Mi Agenda" aparece aunque M08 esté inactivo

**Síntoma:** Operario sin M08 ve el ítem "Mi Agenda" en el sidebar y al hacer clic
recibe `{"error":"modulo_no_activo","modulo":"M08"}`.

**Causa:** El layout renderiza el link `/profesional` sin verificar si M08 está activo
en `rubros_config.modulos_activos`. El check de módulo solo está en la API, no en la vista.

### Fix — `resources/views/tenant/layout.blade.php`

Buscar el bloque donde está el link "Mi Agenda" (va a verse algo así):

```blade
<a href="/profesional" class="nav-link-item ...">Mi Agenda</a>
```

Envolverlo en un `@if` que verifique M08:

```blade
@php
    // Calcular una sola vez — evitar N+1 en el layout
    $modulosActivos ??= \App\Models\Tenant\RubroConfig::first()?->modulos_activos ?? [];
    $tieneM08 = in_array('M08', $modulosActivos);
    $urlOperario = '/operario';

    if ($tieneM08) {
        $tieneRecurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())
            ->where('activo', true)->exists();
        if ($tieneRecurso) $urlOperario = '/profesional';
    }
@endphp

{{-- Link "Mi Agenda" — solo si M08 activo Y usuario tiene recurso --}}
@if($tieneM08 && isset($tieneRecurso) && $tieneRecurso)
<a href="/profesional" class="nav-link-item {{ request()->is('profesional*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Mi Agenda
</a>
@endif

{{-- Link "Stock & Ventas" — siempre para operario, o si no tiene recurso --}}
@if(auth()->user()?->rol === 'operario' || auth()->user()?->rol === 'bodega')
<a href="{{ $urlOperario }}" class="nav-link-item {{ request()->is('operario*') && !request()->is('profesional*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>
    Stock &amp; Ventas
</a>
@endif
```

**Si el layout ya pasa `$modulosActivos` como variable desde el controlador**, usarla
directamente sin consultar otra vez. Buscar en `WebPanelController` cómo se comparte
esa variable con todas las vistas (probablemente en un View Composer o en `share()`).

---

## BUG 2 — Recepcionista ve "Sin recursos configurados"

**Síntoma:** Usuario con rol `recepcionista` (o `cajero`) entra a `/profesional`
y ve "Sin recursos configurados. Ir a Admin → Agenda."

**Causa raíz:** En `AgendaAutoRegistroService::registrarOperario()` el guard es:

```php
if (!in_array($usuario->rol, ['operario', 'cajero', 'admin'])) return null;
```

Si el rol se llama `recepcionista` en el sistema (no `cajero`), el servicio
devuelve `null` y la vista muestra el error. También ocurre si el usuario
simplemente no tiene recurso aún y el auto-create falla silenciosamente.

### Fix A — Ampliar roles en `AgendaAutoRegistroService::registrarOperario()`

Archivo: `app/Services/AgendaAutoRegistroService.php`

```php
public function registrarOperario(Usuario $usuario): ?AgendaRecurso
{
    if (!$this->m08Activo()) return null;

    // Ampliar roles que pueden tener recurso de agenda
    $rolesPermitidos = ['operario', 'cajero', 'recepcionista', 'admin', 'super_admin'];
    if (!in_array($usuario->rol, $rolesPermitidos)) return null;

    // ... resto igual
}
```

### Fix B — `WebPanelController::profesional()` — no mostrar error, redirigir

Si el auto-create devuelve null (usuario no tiene recurso), no renderizar la vista
con mensaje de error — redirigir a `/operario` con mensaje explicativo:

```php
public function profesional()
{
    $usuario = auth()->user();

    $recurso = \App\Models\Tenant\AgendaRecurso::with(['servicios','horarios'])
        ->where('usuario_id', $usuario->id)
        ->where('activo', true)
        ->first();

    if (!$recurso) {
        // Intentar auto-crear
        $recurso = app(\App\Services\AgendaAutoRegistroService::class)
            ->registrarOperario($usuario);

        if ($recurso) {
            $recurso->load(['servicios','horarios']);
        }
    }

    // Si después del intento sigue sin recurso → redirigir al operario normal
    // con mensaje, no mostrar vista rota
    if (!$recurso) {
        return redirect('/operario')
            ->with('info', 'Tu usuario no tiene agenda configurada. Pide al admin que te vincule.');
    }

    $rubroConfig   = \App\Models\Tenant\RubroConfig::first();
    $labelCliente  = $rubroConfig?->label_cliente ?? 'Paciente';
    $labelOperario = $rubroConfig?->label_operario ?? 'Profesional';

    return view('tenant.profesional.index', compact(
        'recurso', 'usuario', 'rubroConfig', 'labelCliente', 'labelOperario'
    ));
}
```

### Fix C — Crear recurso manualmente para los usuarios que ya existen

El observer solo actúa en usuarios nuevos. Para los existentes (como el recepcionista
de demo-medico), ejecutar el comando de inicialización:

```bash
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos --tenant=demo-medico"
```

Si el comando no existe todavía, ejecutar desde tinker:

```bash
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"
```

```php
tenancy()->initialize(App\Models\Central\Tenant::find('demo-medico'));

$svc = app(App\Services\AgendaAutoRegistroService::class);

// Ver usuarios sin recurso
$sin = App\Models\Tenant\Usuario::whereIn('rol', ['operario','cajero','recepcionista'])
    ->where('activo', true)
    ->get()
    ->filter(fn($u) => !App\Models\Tenant\AgendaRecurso::where('usuario_id', $u->id)->exists());

foreach ($sin as $u) {
    $r = $svc->registrarOperario($u);
    echo "{$u->nombre} → recurso " . ($r ? $r->id : 'NULL') . "\n";
}
```

---

## BUG 3 — Doble sidebar (440px de navegación lateral)

**Síntoma visual exacto:**
```
[layout aside 240px: "B& / Clínica Demo / Mi Agenda / Stock & Ventas"]
[prof-nav 200px: "Dr. Demo / Agenda Hoy / Cliente / Seguimiento"]
[prof-main: contenido]
```

**Causa:** La vista `profesional/index.blade.php` usa `@extends('tenant.layout')`.
El layout ya renderiza el `<aside>` de 240px. Dentro del `@section('content')`,
el `prof-shell` empieza con `<nav class="prof-nav">` de 200px adicionales.
Resultado: 440px de navegación en pantalla.

El `prof-nav` es correcto — son los tabs internos de la vista (Agenda/Pacientes/etc).
El problema es que el layout padre **también está visible** mostrando el sidebar principal.

**Hay dos opciones de fix:**

---

### Opción A — Ocultar el sidebar del layout en la vista profesional (recomendada)

En la vista `profesional/index.blade.php`, pasar una variable al layout
para que oculte el `<aside>`:

```blade
@extends('tenant.layout')
@section('hide_sidebar', true)   {{-- ← señal al layout --}}

@section('content')
<div class="prof-shell">
    ...
</div>
@endsection
```

En `layout.blade.php`, envolver el `<aside>` en un condicional:

```blade
@unless(View::hasSection('hide_sidebar'))
<aside id="sidebar" ...>
    ...
</aside>
@endunless
```

Y ajustar el flex del main para que ocupe todo el ancho:

```blade
<div class="flex flex-1 overflow-hidden">
    @unless(View::hasSection('hide_sidebar'))
    <aside ...> ... </aside>
    @endunless

    <main class="flex-1 overflow-y-auto main-content" style="min-width:0;">
        @yield('content')
    </main>
</div>
```

---

### Opción B — Convertir el prof-nav en tabs horizontales top (más simple)

En vez de un nav lateral de 200px, convertir los tabs a una barra horizontal
dentro del `prof-center`. El `prof-shell` quedaría solo como `display:flex`
sin el `prof-nav` como columna separada.

En `profesional/index.blade.php`, reemplazar el CSS y HTML del `prof-shell`:

```css
/* ANTES — 3 columnas */
.prof-shell { display: flex; height: calc(100vh - 56px); }
.prof-nav   { width: 200px; ... }

/* DESPUÉS — solo 2 columnas: centro + detalle */
.prof-shell  { display: flex; flex-direction: column; height: calc(100vh - 56px); }
.prof-tabs   { display: flex; gap: 2px; padding: 8px 12px; background: #0d0d11;
               border-bottom: 1px solid #1e1e28; flex-shrink: 0; }
.prof-body   { flex: 1; display: flex; overflow: hidden; }
```

HTML reestructurado (reemplazar `<nav class="prof-nav">` y `<div class="prof-main">`):

```blade
<div class="prof-shell">

    {{-- Header con info del profesional + tabs horizontales --}}
    <div class="prof-tabs">
        <div style="display:flex;align-items:center;gap:8px;margin-right:16px;padding-right:16px;border-right:1px solid #1e1e28;">
            <span class="prof-dot" style="background:{{ $recurso->color ?? '#3b82f6' }};width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
            <span style="font-size:12px;font-weight:700;color:#e8e8f0;">{{ $usuario->nombre }}</span>
            <span style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#7878a0;text-transform:uppercase;">{{ $recurso->especialidad ?? '' }}</span>
        </div>
        <button onclick="cambiarTab('agenda')" id="nav-agenda"
            class="pni active" style="padding:6px 12px;border-radius:6px;">
            📅 Agenda Hoy
        </button>
        <button onclick="cambiarTab('pacientes')" id="nav-pacientes"
            class="pni" style="padding:6px 12px;border-radius:6px;">
            👤 {{ $labelCliente }}s
        </button>
        <button onclick="cambiarTab('seguimiento')" id="nav-seguimiento"
            class="pni" style="padding:6px 12px;border-radius:6px;">
            📋 Seguimiento
            <span class="pni-badge" id="badgeSeguimiento" style="display:none;">0</span>
        </button>
        <button onclick="cambiarTab('perfil')" id="nav-perfil"
            class="pni" style="padding:6px 12px;border-radius:6px;">
            ⚙️ Config
        </button>

        {{-- KPIs inline --}}
        <div style="margin-left:auto;display:flex;gap:14px;align-items:center;">
            <div class="kpi-item"><span class="kpi-lbl" style="font-size:10px;color:#3a3a55;">Hoy</span> <span class="kpi-val" id="kpiCitasHoy" style="font-size:12px;">—</span></div>
            <div class="kpi-item"><span class="kpi-lbl" style="font-size:10px;color:#3a3a55;">Semana</span> <span class="kpi-val" id="kpiCitasSem" style="font-size:12px;">—</span></div>
        </div>
    </div>

    {{-- Cuerpo: panel central + panel detalle --}}
    <div class="prof-body">
        <div class="prof-center">
            <div id="tab-agenda"       class="tab-panel"><div class="prof-topbar"><span class="prof-titulo" id="agendaTituloFecha">...</span></div><div class="prof-content" id="timelineCitas"></div></div>
            <div id="tab-pacientes"    class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">{{ $labelCliente }}s</span></div><div class="prof-content" id="listaPacientes"></div></div>
            <div id="tab-seguimiento"  class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">Seguimiento</span></div><div class="prof-content" id="listaSeguimiento"></div></div>
            <div id="tab-perfil"       class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">Mi Configuración</span></div><div class="prof-content" id="perfilContent"></div></div>
        </div>
        <div class="prof-detalle cerrado" id="profDetalle"></div>
    </div>

</div>
```

CSS adicional para el `prof-body`:

```css
.prof-body   { flex: 1; display: flex; overflow: hidden; }
.prof-center { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
```

---

## RESUMEN DE ARCHIVOS A TOCAR

| Bug | Archivo | Cambio |
|---|---|---|
| Bug 1 — Menú sin M08 | `resources/views/tenant/layout.blade.php` | Envolver link "Mi Agenda" en `@if($tieneM08 && $tieneRecurso)` |
| Bug 2 — Recepcionista sin recurso | `app/Services/AgendaAutoRegistroService.php` | Agregar `recepcionista` a `$rolesPermitidos` |
| Bug 2 — Fallback redirect | `app/Http/Controllers/Tenant/WebPanelController.php` | Redirigir a `/operario` si recurso null tras auto-create |
| Bug 2 — Usuarios existentes | tinker o `agenda:init-recursos` | Crear recursos para usuarios que existían antes del observer |
| Bug 3 — Doble sidebar | `resources/views/tenant/profesional/index.blade.php` | Opción A: `@section('hide_sidebar')` + layout condicional. Opción B: convertir prof-nav a tabs horizontales top |

---

## COMANDOS EN ORDEN

```bash
# 1. Verificar qué roles tienen los usuarios en demo-medico
docker exec benderandos_app sh -c "cd /app && php artisan tinker --execute=\"
tenancy()->initialize(App\\\Models\\\Central\\\Tenant::find('demo-medico'));
App\\\Models\\\Tenant\\\Usuario::select('id','nombre','rol','activo')->get()->each(fn(\\\$u) => print_r([
  'nombre' => \\\$u->nombre,
  'rol'    => \\\$u->rol,
  'recurso'=> App\\\Models\\\Tenant\\\AgendaRecurso::where('usuario_id',\\\$u->id)->value('id') ?? 'NONE'
]));
\""

# 2. Crear recursos para usuarios sin recurso
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos --tenant=demo-medico 2>&1"
# Si el comando no existe aún, usar tinker manualmente (ver Fix C del Bug 2)

# 3. Limpiar caché tras cambios en Blade y PHP
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear && php artisan view:clear"

# 4. Verificar logs post-fix
docker exec benderandos_app sh -c "tail -20 /app/storage/logs/laravel.log"
```

---

*BenderAnd ERP · Debug Vista Profesional · 2026-03-26*
