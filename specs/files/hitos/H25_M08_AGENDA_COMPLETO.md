# H25 — M08 AGENDA COMPLETO: VISTAS, RUTAS, MENÚS Y LANDING
**BenderAnd ERP · 2026-03-26 · Antigravity**
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade*

---

## PROBLEMA A RESOLVER

El módulo M08 tiene DB y service implementados pero **ninguna vista funciona**.
El operario médico (`demo-medico`) ve solo "Stock & Ventas".
La cajera/recepcionista no tiene menú de agenda.
El admin no tiene sección de agenda en el sidebar.
El landing público no muestra médicos ni permite agendar hora.

**Tenants afectados:** `demo-medico`, `demo-padel`, `demo-legal`

---

## PARTE 1 — MENÚ LATERAL (app-shell / layouts)

El archivo que genera el sidebar en todas las vistas es el layout Blade principal.
Hay que agregar la entrada de Agenda condicionada a M08.

### 1.1 Archivo a modificar: `resources/views/layouts/app.blade.php`

Buscar el bloque donde se renderizan los ítems del nav. Actualmente existe la sección
`Operación` con POS e Historial. Agregar Agenda **antes** de POS, solo si M08 activo:

```blade
{{-- AGENDA (M08) --}}
@if(in_array('M08', $modulosActivos ?? []))
<a href="/pos/agenda"
   class="nav-link-item {{ request()->is('pos/agenda*') ? 'nav-active' : '' }}">
  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round"
      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
  </svg>
  Agenda
</a>
@endif
```

La variable `$modulosActivos` ya existe en el layout compartido (viene de `RubroConfig`).
Si no está disponible, leerla en el layout así:

```php
// En el @php del layout o en un view composer
$modulosActivos = \App\Models\Tenant\RubroConfig::first()?->modulos_activos ?? [];
```

### 1.2 Mobile bottom nav (dentro del mismo `app.blade.php`)

En el bloque de la mobile-nav, agregar Agenda para roles cajero y operario con M08:

```blade
@if(in_array('M08', $modulosActivos ?? []))
<a href="/pos/agenda" class="mobile-nav-item {{ request()->is('pos/agenda*') ? 'active' : '' }}">
  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round"
      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
  </svg>
  Agenda
</a>
@endif
```

### 1.3 Sidebar admin — sección Módulos

En el bloque de admin que lista módulos (después de RRHH, SII, etc.), agregar:

```blade
@if(in_array('M08', $modulosActivos ?? []))
<a href="/admin/agenda"
   class="nav-link-item {{ request()->is('admin/agenda*') ? 'nav-active' : '' }}">
  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round"
      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
  </svg>
  Agenda
</a>
@endif
```

---

## PARTE 2 — RUTAS

### 2.1 Archivo a modificar: `routes/tenant.php`

Agregar dentro del grupo con `auth:sanctum` (no dentro de ningún otro middleware específico,
ya que `check.module` se aplica en el grupo):

```php
// ── M08 AGENDA ────────────────────────────────────────────────────
Route::middleware(['check.module:M08'])->group(function () {

    // Vistas Blade
    Route::get('/pos/agenda',   [AgendaController::class, 'posIndex'])->name('pos.agenda');
    Route::get('/admin/agenda', [AgendaController::class, 'adminIndex'])->name('admin.agenda');

    // API autenticada
    Route::prefix('api/agenda')->group(function () {
        Route::get('/dia',                              [AgendaController::class, 'getDia']);
        Route::get('/slots',                            [AgendaController::class, 'getSlots']);
        Route::get('/sugerencia',                       [AgendaController::class, 'sugerencia']);
        Route::post('/citas',                           [AgendaController::class, 'crearCita']);
        Route::put('/citas/{id}',                       [AgendaController::class, 'actualizarCita']);
        Route::put('/citas/{id}/estado',                [AgendaController::class, 'cambiarEstado']);
        Route::delete('/citas/{id}',                    [AgendaController::class, 'cancelarCita']);
        Route::post('/citas/{id}/iniciar-consulta',     [AgendaController::class, 'iniciarConsulta']);
        Route::post('/citas/{id}/completar',            [AgendaController::class, 'completarCita']);
        Route::get('/recursos',                         [AgendaController::class, 'getRecursos']);
        Route::post('/recursos',                        [AgendaController::class, 'crearRecurso']);
        Route::put('/recursos/{id}/horarios',           [AgendaController::class, 'actualizarHorarios']);
        Route::get('/config',                           [AgendaController::class, 'getConfig']);
        Route::put('/config',                           [AgendaController::class, 'updateConfig']);
        Route::get('/paciente/{clienteId}/historial',   [AgendaController::class, 'historialPaciente']);
    });
});

// ── LANDING PÚBLICO AGENDA (sin auth, solo tenant activo) ─────────
Route::get('/agenda', [AgendaController::class, 'landing'])->name('agenda.landing');
Route::prefix('api/public/agenda')->group(function () {
    Route::get('/recursos',     [AgendaController::class, 'publicRecursos']);
    Route::get('/slots',        [AgendaController::class, 'publicSlots']);
    Route::post('/cita',        [AgendaController::class, 'publicCrearCita']);
});
```

Agregar el `use` al inicio del archivo (si no existe):

```php
use App\Http\Controllers\Tenant\AgendaController;
```

---

## PARTE 3 — CONTROLADOR: métodos faltantes

### 3.1 Agregar en `app/Http/Controllers/Tenant/AgendaController.php`

Los siguientes métodos deben agregarse a la clase existente:

```php
/** GET /api/public/agenda/recursos — Lista recursos públicos para el landing */
public function publicRecursos()
{
    $recursos = AgendaRecurso::with('servicios')
        ->where('activo', true)
        ->orderBy('orden')
        ->get(['id','nombre','especialidad','color','tipo']);
    return response()->json($recursos);
}

/** GET /api/agenda/config */
public function getConfig()
{
    return response()->json(\App\Models\Tenant\AgendaConfig::firstOrCreate([]));
}

/** PUT /api/agenda/config */
public function updateConfig(Request $r)
{
    $config = \App\Models\Tenant\AgendaConfig::firstOrCreate([]);
    $config->update($r->only([
        'titulo_landing','descripcion_landing','landing_publico_activo',
        'confirmacion_wa_activa','recordatorio_activo','recordatorio_horas_antes',
        'requiere_telefono','requiere_email','color_primario',
    ]));
    return response()->json($config);
}

/**
 * GET /api/agenda/paciente/{clienteId}/historial
 * Historial de citas del paciente.
 * Solo el operario (médico) con usuario_id vinculado a un recurso lo puede ver con notas_internas.
 * La cajera/admin ve todo excepto notas_internas.
 */
public function historialPaciente(int $clienteId)
{
    $usuario = auth()->user();
    $esProfesional = AgendaRecurso::where('usuario_id', $usuario->id)->exists();

    $citas = AgendaCita::where('cliente_id', $clienteId)
        ->with(['recurso:id,nombre,especialidad','servicio:id,nombre'])
        ->orderByDesc('fecha')
        ->get();

    // Si NO es el profesional dueño de las notas, eliminar notas_internas
    if (!$esProfesional && !in_array($usuario->rol, ['admin','super_admin'])) {
        $citas->each(fn($c) => $c->makeHidden('notas_internas'));
    }

    return response()->json($citas);
}

/** GET /agenda — Landing público */
public function landing()
{
    $config = \App\Models\Tenant\AgendaConfig::first();
    if (!$config || !$config->landing_publico_activo) {
        abort(404, 'El sistema de agenda no está disponible.');
    }
    $recursos = AgendaRecurso::with('servicios')
        ->where('activo', true)->orderBy('orden')->get();
    return view('public.agenda', compact('config', 'recursos'));
}
```

---

## PARTE 4 — VISTA POS AGENDA

### Archivo a CREAR: `resources/views/pos/agenda.blade.php`

Esta vista reemplaza el comportamiento del POS para roles con M08 activo.
Dos layouts según rol:
- **Cajero / Admin**: ve todas las columnas (todos los recursos/médicos)
- **Operario (médico)**: ve solo su columna con timeline vertical

```blade
@extends('layouts.app')

@section('content')
<style>
/* ── AGENDA SHELL ─────────────────────────────────────── */
.agenda-shell { display:flex; flex-direction:column; height:calc(100vh - 56px); background:#08080a; overflow:hidden; }
.agenda-topbar { display:flex; align-items:center; gap:12px; padding:10px 16px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0; flex-wrap:wrap; }
.agenda-titulo { font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:15px; color:#e8e8f0; }
.agenda-fecha-nav { display:flex; align-items:center; gap:6px; }
.fecha-btn { background:#18181e; border:1px solid #2a2a3a; color:#7878a0; border-radius:7px; width:30px; height:30px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .12s; }
.fecha-btn:hover { border-color:#00e5a0; color:#00e5a0; }
.fecha-display { font-family:'IBM Plex Mono',monospace; font-size:13px; color:#e8e8f0; font-weight:600; min-width:140px; text-align:center; }
.vista-pill { display:flex; gap:4px; }
.vpill { padding:5px 12px; border-radius:20px; font-size:10px; font-weight:700; font-family:'IBM Plex Mono',monospace; cursor:pointer; border:1px solid #2a2a3a; background:#18181e; color:#7878a0; transition:all .12s; }
.vpill.on { background:rgba(0,229,160,.1); border-color:#00e5a0; color:#00e5a0; }
.nueva-cita-btn { margin-left:auto; background:#00e5a0; color:#000; border:none; border-radius:8px; padding:8px 16px; font-family:'IBM Plex Sans',sans-serif; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; }
.nueva-cita-btn:hover { background:#00b87c; }

/* ── COLUMNAS ──────────────────────────────────────────── */
.agenda-body { flex:1; display:flex; overflow:hidden; }
.agenda-cols { flex:1; display:flex; overflow-x:auto; overflow-y:hidden; }
.agenda-col { min-width:220px; flex:1; display:flex; flex-direction:column; border-right:1px solid #1e1e28; }
.col-header { padding:10px 12px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0; }
.col-nombre { font-size:13px; font-weight:700; color:#e8e8f0; }
.col-esp { font-size:10px; color:#7878a0; margin-top:2px; }
.col-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
.col-scroll { flex:1; overflow-y:auto; padding:6px; scrollbar-width:thin; }
.col-scroll::-webkit-scrollbar { width:3px; }
.col-scroll::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:2px; }

/* ── SLOTS Y CITAS ─────────────────────────────────────── */
.slot-libre { padding:6px 10px; margin-bottom:4px; border-radius:7px; background:#0d0d11; border:1px dashed #1e1e28; cursor:pointer; display:flex; align-items:center; gap:8px; font-size:11px; color:#3a3a55; transition:all .15s; }
.slot-libre:hover { border-color:#00e5a0; color:#00e5a0; background:rgba(0,229,160,.04); }
.slot-hora { font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:600; width:40px; flex-shrink:0; }

.cita-card { padding:10px 12px; margin-bottom:5px; border-radius:9px; border:1px solid #1e1e28; cursor:pointer; position:relative; transition:all .15s; }
.cita-card:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(0,0,0,.3); }
.cita-hora { font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:700; margin-bottom:4px; }
.cita-nombre { font-size:12px; font-weight:600; color:#e8e8f0; margin-bottom:2px; }
.cita-rut { font-size:10px; color:#7878a0; font-family:'IBM Plex Mono',monospace; }
.cita-servicio { font-size:10px; color:#7878a0; margin-top:3px; }
.cita-badge { display:inline-flex; align-items:center; padding:2px 7px; border-radius:4px; font-size:9px; font-weight:700; font-family:'IBM Plex Mono',monospace; letter-spacing:.5px; margin-top:4px; }
/* Estado colors */
.st-pendiente   { background:rgba(245,197,24,.12);  color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.st-confirmada  { background:rgba(0,229,160,.1);    color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.st-en_curso    { background:rgba(68,136,255,.12);  color:#4488ff; border:1px solid rgba(68,136,255,.2); }
.st-completada  { background:rgba(136,136,160,.1);  color:#8888a0; border:1px solid rgba(136,136,160,.2); }
.st-cancelada   { background:rgba(255,63,91,.08);   color:#ff3f5b; border:1px solid rgba(255,63,91,.15); opacity:.6; }
.st-no_asistio  { background:rgba(255,63,91,.06);   color:#ff3f5b; opacity:.5; }

/* ── PANEL LATERAL DETALLE ─────────────────────────────── */
.agenda-panel { width:340px; min-width:340px; background:#111115; border-left:1px solid #1e1e28; display:flex; flex-direction:column; transition:transform .25s; }
.agenda-panel.cerrado { display:none; }
.panel-head { padding:14px 16px; border-bottom:1px solid #1e1e28; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.panel-titulo { font-family:'IBM Plex Mono',monospace; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#7878a0; }
.panel-close { background:none; border:none; color:#7878a0; font-size:18px; cursor:pointer; line-height:1; padding:2px 6px; border-radius:6px; }
.panel-close:hover { background:#18181e; color:#e8e8f0; }
.panel-body { flex:1; overflow-y:auto; padding:16px; }
.panel-foot { padding:12px 16px; border-top:1px solid #1e1e28; flex-shrink:0; }

/* ── DETALLE FIELDS ────────────────────────────────────── */
.det-row { display:flex; justify-content:space-between; align-items:flex-start; padding:6px 0; border-bottom:1px solid #1a1a22; font-size:12px; }
.det-row:last-child { border-bottom:none; }
.det-lbl { color:#7878a0; }
.det-val { font-weight:600; text-align:right; max-width:180px; word-break:break-word; }
.textarea-nota { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Sans',sans-serif; font-size:12px; padding:9px 11px; outline:none; resize:none; min-height:70px; transition:border-color .15s; }
.textarea-nota:focus { border-color:#00e5a0; }
.nota-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#3a3a55; margin-bottom:5px; }
.nota-privada { border-color:rgba(255,63,91,.25) !important; }
.nota-privada:focus { border-color:rgba(255,63,91,.5) !important; }

/* ── ACCIONES RÁPIDAS ──────────────────────────────────── */
.accion-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:12px; }
.ac-btn { padding:8px 6px; border-radius:8px; border:1.5px solid #2a2a3a; background:#18181e; color:#7878a0; font-family:'IBM Plex Sans',sans-serif; font-size:11px; font-weight:600; cursor:pointer; text-align:center; transition:all .12s; }
.ac-btn:hover { border-color:#00e5a0; color:#00e5a0; }
.ac-btn.danger:hover { border-color:#ff3f5b; color:#ff3f5b; }
.ac-btn.primary { background:#00e5a0; border-color:#00e5a0; color:#000; }
.ac-btn.primary:hover { background:#00b87c; }
.ac-btn.full { grid-column:1/-1; padding:11px; font-size:13px; }

/* ── PRÓXIMOS SLOTS ────────────────────────────────────── */
.slot-sugerido { padding:8px 10px; background:#0d0d11; border:1px solid #1e1e28; border-radius:7px; font-size:11px; margin-bottom:5px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:all .12s; }
.slot-sugerido:hover { border-color:#00e5a0; }
.slot-sugerido .sf { font-family:'IBM Plex Mono',monospace; font-size:10px; color:#7878a0; }

/* ── MODAL NUEVA CITA ──────────────────────────────────── */
.modal-cita-wrap { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; display:none; align-items:center; justify-content:center; }
.modal-cita-wrap.open { display:flex; }
.modal-cita { background:#111115; border:1px solid #2a2a3a; border-radius:14px; width:min(440px,95vw); max-height:90vh; overflow-y:auto; padding:24px; }
.mc-titulo { font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:14px; margin-bottom:18px; }
.mc-field { margin-bottom:12px; }
.mc-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#7878a0; margin-bottom:5px; display:block; }
.mc-input { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Mono',monospace; font-size:13px; padding:9px 11px; outline:none; transition:border-color .15s; }
.mc-input:focus { border-color:#00e5a0; }
.mc-select { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Sans',sans-serif; font-size:13px; padding:9px 11px; outline:none; }
.mc-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.mc-foot { display:flex; gap:8px; margin-top:18px; }
.btn-cancelar { flex:1; padding:10px; border-radius:8px; border:1.5px solid #2a2a3a; background:#18181e; color:#7878a0; font-size:13px; font-weight:600; cursor:pointer; }
.btn-guardar { flex:2; padding:10px; border-radius:8px; border:none; background:#00e5a0; color:#000; font-size:13px; font-weight:700; cursor:pointer; }
.btn-guardar:hover { background:#00b87c; }

/* ── HISTORIAL PACIENTE ────────────────────────────────── */
.hist-cita { padding:10px 12px; background:#0d0d11; border:1px solid #1e1e28; border-radius:8px; margin-bottom:6px; }
.hist-fecha { font-family:'IBM Plex Mono',monospace; font-size:10px; color:#7878a0; margin-bottom:4px; }
.hist-titulo { font-size:12px; font-weight:600; color:#e8e8f0; }
.hist-nota { font-size:11px; color:#7878a0; margin-top:4px; font-style:italic; }
.lock-badge { display:inline-flex; align-items:center; gap:4px; font-size:9px; color:#ff3f5b; font-family:'IBM Plex Mono',monospace; font-weight:700; letter-spacing:.5px; padding:2px 7px; background:rgba(255,63,91,.08); border:1px solid rgba(255,63,91,.15); border-radius:4px; }

@media(max-width:768px) {
  .agenda-panel { position:fixed; inset:0; z-index:100; width:100vw; }
  .agenda-col { min-width:180px; }
}
</style>

<div class="agenda-shell">

  {{-- TOPBAR --}}
  <div class="agenda-topbar">
    <div class="agenda-fecha-nav">
      <button class="fecha-btn" onclick="cambiarFecha(-1)">‹</button>
      <div class="fecha-display" id="fechaDisplay">Cargando...</div>
      <button class="fecha-btn" onclick="cambiarFecha(1)">›</button>
      <button class="fecha-btn" onclick="irHoy()" title="Hoy" style="font-size:11px;width:auto;padding:0 8px;">Hoy</button>
    </div>

    <div class="vista-pill">
      <button class="vpill on" id="vpDia" onclick="setVista('dia')">Día</button>
      <button class="vpill" id="vpSem" onclick="setVista('semana')">Semana</button>
    </div>

    @php $esProfesional = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())->exists(); @endphp

    @if(!$esProfesional)
    <button class="nueva-cita-btn" onclick="abrirModalNuevaCita()">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Nueva cita
    </button>
    @endif
  </div>

  {{-- BODY --}}
  <div class="agenda-body">

    {{-- COLUMNAS --}}
    <div class="agenda-cols" id="agendaCols">
      <div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-family:'IBM Plex Mono',monospace;font-size:12px;">
        Cargando agenda...
      </div>
    </div>

    {{-- PANEL LATERAL --}}
    <div class="agenda-panel cerrado" id="agendaPanel">
      <div class="panel-head">
        <span class="panel-titulo" id="panelTitulo">Detalle cita</span>
        <button class="panel-close" onclick="cerrarPanel()">✕</button>
      </div>
      <div class="panel-body" id="panelBody">
        {{-- Se rellena por JS --}}
      </div>
      <div class="panel-foot" id="panelFoot"></div>
    </div>

  </div>
</div>

{{-- MODAL NUEVA CITA --}}
<div class="modal-cita-wrap" id="modalNuevaCita">
  <div class="modal-cita">
    <div class="mc-titulo">📅 Nueva cita</div>

    <div class="mc-field">
      <label class="mc-label">Profesional / Recurso</label>
      <select class="mc-select" id="mcRecurso" onchange="cargarServiciosModal()"></select>
    </div>

    <div class="mc-field">
      <label class="mc-label">Servicio</label>
      <select class="mc-select" id="mcServicio" onchange="calcularHoraFin()"></select>
    </div>

    <div class="mc-2col">
      <div class="mc-field">
        <label class="mc-label">Fecha</label>
        <input type="date" class="mc-input" id="mcFecha" onchange="cargarSlotsModal()">
      </div>
      <div class="mc-field">
        <label class="mc-label">Hora</label>
        <select class="mc-select" id="mcHora" onchange="calcularHoraFin()"></select>
      </div>
    </div>

    <div class="mc-field" style="background:rgba(245,197,24,.05);border:1px solid rgba(245,197,24,.15);border-radius:8px;padding:10px;">
      <label class="mc-label">Buscar paciente (RUT o nombre)</label>
      <div style="display:flex;gap:6px;">
        <input type="text" class="mc-input" id="mcRutBusca" placeholder="RUT o nombre..." style="flex:1;" oninput="buscarPacienteModal()">
      </div>
      <div id="mcRutResultado" style="margin-top:6px;font-size:11px;color:#7878a0;"></div>
    </div>

    <input type="hidden" id="mcClienteId">
    <div class="mc-field">
      <label class="mc-label">Nombre paciente *</label>
      <input type="text" class="mc-input" id="mcNombre" placeholder="Nombre completo">
    </div>
    <div class="mc-2col">
      <div class="mc-field">
        <label class="mc-label">RUT</label>
        <input type="text" class="mc-input" id="mcRut" placeholder="12.345.678-9">
      </div>
      <div class="mc-field">
        <label class="mc-label">Teléfono</label>
        <input type="tel" class="mc-input" id="mcTelefono" placeholder="+56 9...">
      </div>
    </div>
    <div class="mc-field">
      <label class="mc-label">Notas (visibles al paciente)</label>
      <textarea class="mc-input textarea-nota" id="mcNotas" style="min-height:50px;font-family:'IBM Plex Sans',sans-serif;font-size:12px;" placeholder="Motivo de consulta..."></textarea>
    </div>
    <div class="mc-foot">
      <button class="btn-cancelar" onclick="cerrarModalNuevaCita()">Cancelar</button>
      <button class="btn-guardar" onclick="guardarNuevaCita()">Crear cita →</button>
    </div>
  </div>
</div>

<script>
const ROL = '{{ auth()->user()->rol }}';
const ES_PROFESIONAL = {{ $recurso ? 'true' : 'false' }};
const MI_RECURSO_ID  = {{ $recurso?->id ?? 'null' }};
const TODOS_RECURSOS = @json($recursos);

let fechaActual = new Date();
let citaActual  = null;
let vistaActual = 'dia';

// ── FECHA ────────────────────────────────────────────────
function formatFecha(d) {
  return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
}
function toISO(d) {
  return d.toISOString().split('T')[0];
}
function cambiarFecha(delta) {
  fechaActual.setDate(fechaActual.getDate() + delta);
  cargarAgenda();
}
function irHoy() {
  fechaActual = new Date();
  cargarAgenda();
}
function setVista(v) {
  vistaActual = v;
  document.getElementById('vpDia').classList.toggle('on', v === 'dia');
  document.getElementById('vpSem').classList.toggle('on', v === 'semana');
  cargarAgenda();
}

// ── CARGAR AGENDA ────────────────────────────────────────
async function cargarAgenda() {
  document.getElementById('fechaDisplay').textContent = formatFecha(fechaActual);
  const cols = document.getElementById('agendaCols');
  cols.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-family:\'IBM Plex Mono\',monospace;font-size:12px;">Cargando...</div>';

  try {
    let url = `/api/agenda/dia?fecha=${toISO(fechaActual)}`;
    if (ES_PROFESIONAL && MI_RECURSO_ID) url += `&recurso_id=${MI_RECURSO_ID}`;

    const data = await api('GET', url);
    renderColumnas(data);
  } catch(e) {
    cols.innerHTML = `<div style="padding:32px;color:#ff3f5b;font-size:12px;">Error: ${e.message}</div>`;
  }
}

// ── RENDER COLUMNAS ──────────────────────────────────────
function renderColumnas(columnas) {
  const cols = document.getElementById('agendaCols');
  if (!columnas.length) {
    cols.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-size:12px;font-family:\'IBM Plex Mono\',monospace;">Sin recursos configurados.<br>Ir a Admin → Agenda.</div>';
    return;
  }

  cols.innerHTML = columnas.map(col => `
    <div class="agenda-col">
      <div class="col-header">
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="col-dot" style="background:${col.recurso.color};"></span>
          <div>
            <div class="col-nombre">${col.recurso.nombre}</div>
            ${col.recurso.especialidad ? `<div class="col-esp">${col.recurso.especialidad}</div>` : ''}
          </div>
        </div>
        ${!col.tiene_horario ? '<div style="font-size:9px;color:#3a3a55;margin-top:4px;font-family:\'IBM Plex Mono\',monospace;">Sin horario este día</div>' : ''}
      </div>
      <div class="col-scroll">
        ${renderCitasYSlots(col)}
      </div>
    </div>
  `).join('');
}

function renderCitasYSlots(col) {
  if (!col.tiene_horario) return '<div style="padding:16px;font-size:11px;color:#3a3a55;text-align:center;">Día libre</div>';

  const items = [];

  // Mezclar citas y slots libres por hora
  col.citas.forEach(c => items.push({ tipo: 'cita', data: c, hora: c.hora_inicio }));
  col.slots_libres.forEach(s => {
    const ocupado = col.citas.some(c => c.hora_inicio === s.hora_inicio);
    if (!ocupado) items.push({ tipo: 'slot', data: s, hora: s.hora_inicio });
  });
  items.sort((a,b) => a.hora.localeCompare(b.hora));

  return items.map(item => {
    if (item.tipo === 'cita') return renderCitaCard(item.data, col.recurso);
    return `<div class="slot-libre" onclick="abrirModalNuevaCitaConSlot(${col.recurso.id}, '${item.data.hora_inicio}', '${item.data.hora_fin}')">
      <span class="slot-hora">${item.data.hora_inicio}</span>
      <span style="font-size:10px;">— disponible</span>
    </div>`;
  }).join('');
}

function renderCitaCard(c, recurso) {
  const stClass = `st-${c.estado}`;
  const estadoLabel = {
    pendiente:'Pendiente', confirmada:'Confirmada', en_curso:'En curso',
    completada:'Completada', cancelada:'Cancelada', no_asistio:'No asistió'
  }[c.estado] || c.estado;

  return `<div class="cita-card ${c.estado === 'cancelada' ? 'opacity-40' : ''}"
    style="border-left:3px solid ${recurso.color};"
    onclick="abrirPanel(${JSON.stringify(c).replace(/"/g, '&quot;')}, ${JSON.stringify(recurso).replace(/"/g, '&quot;')})">
    <div class="cita-hora">${c.hora_inicio} – ${c.hora_fin}</div>
    <div class="cita-nombre">${c.paciente_nombre}</div>
    ${c.paciente_rut ? `<div class="cita-rut">${c.paciente_rut}</div>` : ''}
    ${c.servicio ? `<div class="cita-servicio">📋 ${c.servicio}</div>` : ''}
    <span class="cita-badge ${stClass}">${estadoLabel}</span>
  </div>`;
}

// ── PANEL LATERAL ────────────────────────────────────────
function abrirPanel(cita, recurso) {
  citaActual = cita;
  document.getElementById('agendaPanel').classList.remove('cerrado');
  document.getElementById('panelTitulo').textContent = `${cita.hora_inicio} · ${cita.paciente_nombre}`;

  const esMedico = ES_PROFESIONAL && MI_RECURSO_ID === recurso.id;
  const esAdmin  = ['admin','super_admin'].includes(ROL);

  document.getElementById('panelBody').innerHTML = `
    <div style="margin-bottom:12px;">
      <div class="det-row"><span class="det-lbl">Paciente</span><span class="det-val">${cita.paciente_nombre}</span></div>
      ${cita.paciente_rut ? `<div class="det-row"><span class="det-lbl">RUT</span><span class="det-val" style="font-family:'IBM Plex Mono',monospace;">${cita.paciente_rut}</span></div>` : ''}
      <div class="det-row"><span class="det-lbl">Horario</span><span class="det-val" style="font-family:'IBM Plex Mono',monospace;">${cita.hora_inicio} – ${cita.hora_fin}</span></div>
      ${cita.servicio ? `<div class="det-row"><span class="det-lbl">Servicio</span><span class="det-val">${cita.servicio}</span></div>` : ''}
      <div class="det-row"><span class="det-lbl">Estado</span><span class="cita-badge st-${cita.estado}">${cita.estado.replace('_',' ')}</span></div>
      ${cita.notas_publicas ? `<div style="margin-top:10px;"><div class="nota-label">Notas del paciente</div><div style="font-size:12px;color:#7878a0;">${cita.notas_publicas}</div></div>` : ''}
    </div>

    ${(esMedico || esAdmin) ? `
    <div style="margin-top:12px;">
      <div class="nota-label">
        🔒 Notas internas
        <span class="lock-badge" style="margin-left:6px;">PRIVADO</span>
      </div>
      <textarea class="textarea-nota nota-privada" id="notaInterna" placeholder="Diagnóstico, observaciones clínicas (solo staff)..."
        onblur="guardarNotaInterna(${cita.id})">${cita.notas_internas || ''}</textarea>
      <div style="font-size:10px;color:#3a3a55;margin-top:4px;">Se guarda automáticamente al salir del campo</div>
    </div>` : ''}

    ${cita.cliente_id ? `
    <div style="margin-top:16px;">
      <button onclick="verHistorialPaciente(${cita.cliente_id}, '${cita.paciente_nombre}')"
        style="width:100%;padding:8px;background:rgba(68,136,255,.1);border:1px solid rgba(68,136,255,.2);border-radius:8px;color:#4488ff;font-size:12px;font-weight:600;cursor:pointer;">
        📋 Ver historial del paciente →
      </button>
    </div>` : ''}

    <div style="margin-top:16px;" id="proximosSlots"></div>
  `;

  // Acciones según estado
  renderAccionesPanel(cita, esMedico, esAdmin);

  // Si está en_curso, cargar próximos slots
  if (cita.estado === 'en_curso' && (esMedico || esAdmin)) {
    cargarProximosSlots(recurso.id);
  }
}

function renderAccionesPanel(cita, esMedico, esAdmin) {
  const foot = document.getElementById('panelFoot');
  const puede = esMedico || esAdmin || ROL === 'cajero';

  let acciones = '';

  if (cita.estado === 'pendiente') {
    acciones += `<button class="ac-btn" onclick="cambiarEstadoCita(${cita.id},'confirmada')">✅ Confirmar llegada</button>`;
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'no_asistio')">❌ No asistió</button>`;
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'cancelada')">🗑 Cancelar cita</button>`;
  } else if (cita.estado === 'confirmada') {
    if (esMedico || esAdmin) {
      acciones += `<button class="ac-btn primary" onclick="iniciarConsulta(${cita.id})">▶ Iniciar consulta</button>`;
    }
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'no_asistio')">❌ No asistió</button>`;
  } else if (cita.estado === 'en_curso') {
    acciones += `<button class="ac-btn primary full" onclick="completarYCobrar(${cita.id})">💳 Completar y cobrar →</button>`;
  }

  foot.innerHTML = acciones ? `<div class="accion-grid">${acciones}</div>` : '';
}

function cerrarPanel() {
  document.getElementById('agendaPanel').classList.add('cerrado');
  citaActual = null;
}

// ── ACCIONES ─────────────────────────────────────────────
async function cambiarEstadoCita(id, estado) {
  try {
    await api('PUT', `/api/agenda/citas/${id}/estado`, { estado });
    toast(`Estado actualizado: ${estado}`, 'ok');
    cargarAgenda();
    cerrarPanel();
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

async function iniciarConsulta(id) {
  try {
    const res = await api('POST', `/api/agenda/citas/${id}/iniciar-consulta`);
    toast('Consulta iniciada', 'ok');
    cargarAgenda();
    // Reabrir panel con estado actualizado
    abrirPanel({...citaActual, estado:'en_curso'}, {id: citaActual.agenda_recurso_id, color:'#6366f1'});
    if (res.proximos_slots) renderProximosSlots(res.proximos_slots);
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

async function completarYCobrar(id) {
  try {
    const res = await api('POST', `/api/agenda/citas/${id}/completar`);
    toast('Consulta completada', 'ok');
    cargarAgenda();
    cerrarPanel();
    // Si hay monto sugerido, abrir POS con datos pre-cargados
    if (res.monto_sugerido > 0) {
      toast(`💳 Cobrar $${res.monto_sugerido.toLocaleString('es-CL')} a ${res.paciente_nombre}`, 'ok');
      // Redirigir al POS con parámetros opcionales
      // window.location.href = `/pos?cliente_id=${res.cliente_id}&monto=${res.monto_sugerido}`;
    }
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

async function guardarNotaInterna(id) {
  const nota = document.getElementById('notaInterna')?.value;
  if (nota === undefined) return;
  try {
    await api('PUT', `/api/agenda/citas/${id}`, { notas_internas: nota });
  } catch(e) { /* silencioso */ }
}

async function cargarProximosSlots(recursoId) {
  try {
    const slots = await api('GET', `/api/agenda/sugerencia?recurso_id=${recursoId}`);
    renderProximosSlots(slots);
  } catch(e) {}
}

function renderProximosSlots(slots) {
  const el = document.getElementById('proximosSlots');
  if (!el || !slots.length) return;
  el.innerHTML = `
    <div class="nota-label" style="margin-bottom:6px;">Próximas fechas disponibles</div>
    ${slots.map(s => `
      <div class="slot-sugerido" onclick="agendarProximaCita('${s.fecha}', ${s.primer_slot?.hora_inicio ? `'${s.primer_slot.hora_inicio}'` : 'null'})">
        <span>${s.fecha_fmt}</span>
        <span class="sf">${s.primer_slot?.hora_inicio || ''} · ${s.total_slots} slot${s.total_slots !== 1 ? 's' : ''}</span>
      </div>`).join('')}`;
}

function agendarProximaCita(fecha, hora) {
  cerrarPanel();
  abrirModalNuevaCita(fecha, hora);
}

// ── HISTORIAL PACIENTE ───────────────────────────────────
async function verHistorialPaciente(clienteId, nombre) {
  document.getElementById('panelTitulo').textContent = `📋 Historial · ${nombre}`;
  document.getElementById('panelBody').innerHTML = '<div style="color:#3a3a55;font-size:12px;font-family:\'IBM Plex Mono\',monospace;padding:16px;">Cargando historial...</div>';
  document.getElementById('panelFoot').innerHTML = `<button class="ac-btn full" onclick="abrirPanel(${JSON.stringify(citaActual).replace(/"/g, '&quot;')}, {})">← Volver a la cita</button>`;

  try {
    const historial = await api('GET', `/api/agenda/paciente/${clienteId}/historial`);
    if (!historial.length) {
      document.getElementById('panelBody').innerHTML = '<div style="padding:20px;text-align:center;color:#3a3a55;font-size:12px;">Sin historial previo</div>';
      return;
    }

    const esMedico = ES_PROFESIONAL;
    document.getElementById('panelBody').innerHTML = historial.map(c => `
      <div class="hist-cita">
        <div class="hist-fecha">${new Date(c.fecha).toLocaleDateString('es-CL', {day:'numeric',month:'long',year:'numeric'})} · ${c.hora_inicio} – ${c.hora_fin}</div>
        <div class="hist-titulo">${c.recurso?.nombre || ''} ${c.servicio ? '· '+c.servicio.nombre : ''}</div>
        <span class="cita-badge st-${c.estado}">${c.estado}</span>
        ${c.notas_publicas ? `<div class="hist-nota">📝 ${c.notas_publicas}</div>` : ''}
        ${(esMedico || ['admin','super_admin'].includes(ROL)) && c.notas_internas ? `
          <div style="margin-top:6px;">
            <span class="lock-badge">🔒 NOTAS CLÍNICAS</span>
            <div class="hist-nota" style="color:#e8e8f0;margin-top:4px;">${c.notas_internas}</div>
          </div>` : ''}
      </div>`).join('');
  } catch(e) {
    document.getElementById('panelBody').innerHTML = `<div style="color:#ff3f5b;font-size:12px;padding:16px;">Error: ${e.message}</div>`;
  }
}

// ── MODAL NUEVA CITA ─────────────────────────────────────
function abrirModalNuevaCita(fechaPreset = null, horaPreset = null) {
  // Poblar recursos
  const sel = document.getElementById('mcRecurso');
  sel.innerHTML = TODOS_RECURSOS.map(r => `<option value="${r.id}" data-color="${r.color}">${r.nombre}${r.especialidad ? ' · '+r.especialidad : ''}</option>`).join('');
  if (MI_RECURSO_ID) sel.value = MI_RECURSO_ID;

  // Fecha
  const hoyISO = toISO(fechaActual);
  document.getElementById('mcFecha').value = fechaPreset || hoyISO;

  cargarServiciosModal();
  cargarSlotsModal(horaPreset);

  document.getElementById('mcNombre').value = '';
  document.getElementById('mcRut').value = '';
  document.getElementById('mcTelefono').value = '';
  document.getElementById('mcNotas').value = '';
  document.getElementById('mcClienteId').value = '';
  document.getElementById('mcRutResultado').textContent = '';

  document.getElementById('modalNuevaCita').classList.add('open');
}

function abrirModalNuevaCitaConSlot(recursoId, horaInicio, horaFin) {
  abrirModalNuevaCita(null, horaInicio);
  document.getElementById('mcRecurso').value = recursoId;
  cargarServiciosModal();
}

function cerrarModalNuevaCita() {
  document.getElementById('modalNuevaCita').classList.remove('open');
}

async function cargarServiciosModal() {
  const recursoId = document.getElementById('mcRecurso').value;
  if (!recursoId) return;
  try {
    const recursos = await api('GET', '/api/agenda/recursos');
    const recurso = recursos.find(r => r.id == recursoId);
    const sel = document.getElementById('mcServicio');
    sel.innerHTML = '<option value="">Sin servicio específico</option>';
    (recurso?.servicios || []).forEach(s => {
      sel.innerHTML += `<option value="${s.id}" data-duracion="${s.duracion_min}" data-precio="${s.precio}">${s.nombre} · ${s.duracion_min}min · $${s.precio.toLocaleString('es-CL')}</option>`;
    });
  } catch(e) {}
}

async function cargarSlotsModal(presetHora = null) {
  const recursoId = document.getElementById('mcRecurso').value;
  const fecha     = document.getElementById('mcFecha').value;
  if (!recursoId || !fecha) return;

  try {
    const duracion = parseInt(document.getElementById('mcServicio')?.selectedOptions[0]?.dataset.duracion) || 30;
    const slots = await api('GET', `/api/agenda/slots?recurso_id=${recursoId}&fecha=${fecha}&duracion=${duracion}`);
    const sel = document.getElementById('mcHora');
    if (!slots.length) {
      sel.innerHTML = '<option value="">Sin horarios disponibles</option>';
      return;
    }
    sel.innerHTML = slots.map(s => `<option value="${s.hora_inicio}|${s.hora_fin}">${s.hora_inicio} – ${s.hora_fin}</option>`).join('');
    if (presetHora) {
      const opt = [...sel.options].find(o => o.value.startsWith(presetHora));
      if (opt) sel.value = opt.value;
    }
  } catch(e) {}
}

function calcularHoraFin() { cargarSlotsModal(); }

let buscaTimer = null;
async function buscarPacienteModal() {
  clearTimeout(buscaTimer);
  buscaTimer = setTimeout(async () => {
    const q = document.getElementById('mcRutBusca').value.trim();
    if (!q) return;
    try {
      const res = await api('GET', `/api/clientes?q=${encodeURIComponent(q)}&per_page=5`);
      const lista = res.data || res;
      if (!lista.length) { document.getElementById('mcRutResultado').innerHTML = '<span style="color:#ff3f5b;">No encontrado</span>'; return; }
      document.getElementById('mcRutResultado').innerHTML = lista.slice(0,3).map(c =>
        `<div style="padding:5px 8px;background:#18181e;border-radius:6px;margin-bottom:3px;cursor:pointer;font-size:12px;"
          onclick="selPaciente(${c.id},'${c.nombre}','${c.rut||''}','${c.telefono||''}')">
          ${c.nombre} <span style="color:#7878a0;font-family:'IBM Plex Mono',monospace;">${c.rut||''}</span>
        </div>`).join('');
    } catch(e) {}
  }, 350);
}

function selPaciente(id, nombre, rut, tel) {
  document.getElementById('mcClienteId').value = id;
  document.getElementById('mcNombre').value = nombre;
  document.getElementById('mcRut').value = rut;
  document.getElementById('mcTelefono').value = tel;
  document.getElementById('mcRutResultado').innerHTML = `<span style="color:#00e5a0;">✓ ${nombre}</span>`;
  document.getElementById('mcRutBusca').value = '';
}

async function guardarNuevaCita() {
  const horaVal = document.getElementById('mcHora').value;
  if (!horaVal) { toast('Selecciona un horario disponible', 'err'); return; }
  const [horaInicio, horaFin] = horaVal.split('|');

  const servicioSel = document.getElementById('mcServicio').selectedOptions[0];
  const servicioId  = servicioSel?.value || null;

  const payload = {
    agenda_recurso_id:  parseInt(document.getElementById('mcRecurso').value),
    agenda_servicio_id: servicioId && servicioId !== '' ? parseInt(servicioId) : null,
    cliente_id:         document.getElementById('mcClienteId').value || null,
    fecha:              document.getElementById('mcFecha').value,
    hora_inicio:        horaInicio,
    hora_fin:           horaFin,
    paciente_nombre:    document.getElementById('mcNombre').value,
    paciente_rut:       document.getElementById('mcRut').value || null,
    paciente_telefono:  document.getElementById('mcTelefono').value || null,
    notas_publicas:     document.getElementById('mcNotas').value || null,
    origen:             'admin',
  };

  if (!payload.paciente_nombre) { toast('Nombre requerido', 'err'); return; }

  try {
    await api('POST', '/api/agenda/citas', payload);
    toast('✓ Cita creada', 'ok');
    cerrarModalNuevaCita();
    cargarAgenda();
  } catch(e) { toast(e.message || 'Error al crear cita', 'err'); }
}

// ── INIT ─────────────────────────────────────────────────
cargarAgenda();
</script>
@endsection
```

---

## PARTE 5 — VISTA ADMIN AGENDA

### Archivo a CREAR: `resources/views/admin/agenda/index.blade.php`

```blade
@extends('layouts.app')

@section('content')
<style>
.agenda-admin { padding:20px; max-width:1100px; }
.aa-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:16px; }
@media(max-width:700px){ .aa-grid { grid-template-columns:1fr; } }
.rec-card { background:#111115; border:1px solid #1e1e28; border-radius:12px; padding:16px; }
.rec-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.rec-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.rec-nombre { font-weight:700; font-size:14px; flex:1; }
.rec-esp { font-size:11px; color:#7878a0; }
.horario-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-top:10px; }
.dia-btn { padding:5px 3px; border-radius:6px; text-align:center; font-size:10px; font-weight:700; font-family:'IBM Plex Mono',monospace; cursor:pointer; border:1px solid #2a2a3a; background:#18181e; color:#3a3a55; transition:all .12s; }
.dia-btn.activo { background:rgba(0,229,160,.1); border-color:#00e5a0; color:#00e5a0; }
.servicio-item { display:flex; justify-content:space-between; align-items:center; padding:7px 10px; background:#0d0d11; border-radius:7px; margin-bottom:5px; font-size:12px; }
.s-nombre { font-weight:500; }
.s-meta { font-size:10px; color:#7878a0; font-family:'IBM Plex Mono',monospace; }
.config-card { background:#111115; border:1px solid #1e1e28; border-radius:12px; padding:16px; }
.config-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #1a1a22; font-size:13px; }
.config-row:last-child { border-bottom:none; }
.toggle-wrap { position:relative; width:44px; height:24px; }
.toggle-wrap input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:#2a2a3a; border-radius:24px; transition:.2s; }
.toggle-slider:before { content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#7878a0; border-radius:50%; transition:.2s; }
.toggle-wrap input:checked + .toggle-slider { background:rgba(0,229,160,.25); }
.toggle-wrap input:checked + .toggle-slider:before { transform:translateX(20px); background:#00e5a0; }
.f-input { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Mono',monospace; font-size:13px; padding:8px 10px; outline:none; transition:border-color .15s; }
.f-input:focus { border-color:#00e5a0; }
.btn-add { padding:7px 14px; border-radius:8px; border:none; background:#00e5a0; color:#000; font-size:12px; font-weight:700; cursor:pointer; }
.btn-edit { padding:5px 10px; border-radius:6px; border:1.5px solid #2a2a3a; background:#18181e; color:#7878a0; font-size:11px; cursor:pointer; transition:all .12s; }
.btn-edit:hover { border-color:#00e5a0; color:#00e5a0; }
.modal-aa { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; display:none; align-items:center; justify-content:center; }
.modal-aa.open { display:flex; }
.modal-aa-body { background:#111115; border:1px solid #2a2a3a; border-radius:14px; width:min(420px,94vw); padding:24px; max-height:88vh; overflow-y:auto; }
.maa-titulo { font-weight:700; font-size:14px; font-family:'IBM Plex Mono',monospace; margin-bottom:16px; }
.maa-field { margin-bottom:12px; }
.maa-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#7878a0; margin-bottom:5px; display:block; }
.dias-semana { display:flex; gap:5px; }
.dia-check { display:flex; flex-direction:column; align-items:center; gap:3px; }
.dia-check span { font-size:9px; color:#7878a0; font-weight:700; font-family:'IBM Plex Mono',monospace; }
.dia-check input[type=checkbox] { accent-color:#00e5a0; width:16px; height:16px; }
.landing-link { display:flex; align-items:center; gap:8px; padding:10px 14px; background:rgba(0,229,160,.06); border:1px solid rgba(0,229,160,.15); border-radius:8px; font-size:12px; text-decoration:none; color:#00e5a0; font-family:'IBM Plex Mono',monospace; word-break:break-all; }
.landing-link:hover { background:rgba(0,229,160,.1); }
</style>

<div class="agenda-admin">
  <div class="page-header">
    <div>
      <div class="page-title">Agenda</div>
      <div class="page-sub">Profesionales, horarios, servicios y configuración del landing público</div>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="/agenda" target="_blank" class="btn btn-secondary" style="font-size:12px;">🌐 Ver landing público</a>
      <button class="btn btn-primary" onclick="abrirModalRecurso()">+ Nuevo profesional</button>
    </div>
  </div>

  {{-- Landing link --}}
  <a href="/agenda" target="_blank" class="landing-link" style="margin-bottom:16px;display:flex;">
    🔗 Landing público: <strong style="margin-left:6px;">{{ request()->getSchemeAndHttpHost() }}/agenda</strong>
    <span style="margin-left:auto;font-size:10px;opacity:.6;">abrir →</span>
  </a>

  {{-- Config global --}}
  <div class="config-card" style="margin-bottom:16px;">
    <div style="font-weight:700;font-size:13px;margin-bottom:12px;">Configuración general del módulo</div>
    <div id="configGlobal"><div style="color:#3a3a55;font-size:12px;">Cargando...</div></div>
  </div>

  {{-- Recursos --}}
  <div style="font-weight:700;font-size:14px;margin-bottom:10px;">Profesionales y recursos</div>
  <div class="aa-grid" id="recursosGrid">
    <div style="color:#3a3a55;font-size:12px;padding:20px;">Cargando...</div>
  </div>
</div>

{{-- Modal Recurso --}}
<div class="modal-aa" id="modalRecurso">
  <div class="modal-aa-body">
    <div class="maa-titulo" id="mRecTitulo">Nuevo profesional</div>
    <input type="hidden" id="mRecId">
    <div class="maa-field">
      <label class="maa-label">Nombre *</label>
      <input type="text" class="f-input" id="mRecNombre" placeholder="Dr. Pérez / Sala A / Cancha 1">
    </div>
    <div class="maa-field">
      <label class="maa-label">Tipo</label>
      <select class="f-input" id="mRecTipo">
        <option value="profesional">Profesional (médico, abogado...)</option>
        <option value="recurso_fisico">Recurso físico (sala, cancha...)</option>
      </select>
    </div>
    <div class="maa-field">
      <label class="maa-label">Especialidad</label>
      <input type="text" class="f-input" id="mRecEsp" placeholder="Medicina general, Pádel...">
    </div>
    <div class="maa-field">
      <label class="maa-label">Color en agenda</label>
      <input type="color" class="f-input" id="mRecColor" value="#6366f1" style="height:40px;padding:4px;">
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;">
      <button class="btn-edit" style="flex:1;" onclick="cerrarModalRecurso()">Cancelar</button>
      <button class="btn-add" style="flex:2;" onclick="guardarRecurso()">Guardar profesional →</button>
    </div>
  </div>
</div>

{{-- Modal Horarios --}}
<div class="modal-aa" id="modalHorarios">
  <div class="modal-aa-body">
    <div class="maa-titulo">Configurar horarios</div>
    <div style="font-size:12px;color:#7878a0;margin-bottom:12px;" id="mHorNombre"></div>
    <input type="hidden" id="mHorRecursoId">
    <div class="maa-field">
      <label class="maa-label">Días activos</label>
      <div class="dias-semana">
        ${['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'].map((d,i) =>
          `<label class="dia-check"><input type="checkbox" value="${i}" class="dia-check-input"> <span>${d}</span></label>`
        ).join('')}
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div class="maa-field">
        <label class="maa-label">Inicio</label>
        <input type="time" class="f-input" id="mHorInicio" value="09:00">
      </div>
      <div class="maa-field">
        <label class="maa-label">Fin</label>
        <input type="time" class="f-input" id="mHorFin" value="18:00">
      </div>
    </div>
    <div class="maa-field">
      <label class="maa-label">Duración del slot (minutos)</label>
      <select class="f-input" id="mHorSlot">
        <option value="15">15 minutos</option>
        <option value="20">20 minutos</option>
        <option value="30" selected>30 minutos</option>
        <option value="45">45 minutos</option>
        <option value="60">60 minutos (1 hora)</option>
      </select>
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;">
      <button class="btn-edit" style="flex:1;" onclick="cerrarModalHorarios()">Cancelar</button>
      <button class="btn-add" style="flex:2;" onclick="guardarHorarios()">Guardar horarios →</button>
    </div>
  </div>
</div>

{{-- Modal Servicio --}}
<div class="modal-aa" id="modalServicio">
  <div class="modal-aa-body">
    <div class="maa-titulo" id="mSrvTitulo">Nuevo servicio</div>
    <input type="hidden" id="mSrvRecursoId">
    <div class="maa-field">
      <label class="maa-label">Nombre del servicio *</label>
      <input type="text" class="f-input" id="mSrvNombre" placeholder="Consulta general, Limpieza dental...">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div class="maa-field">
        <label class="maa-label">Duración (min)</label>
        <input type="number" class="f-input" id="mSrvDuracion" value="30">
      </div>
      <div class="maa-field">
        <label class="maa-label">Precio ($)</label>
        <input type="number" class="f-input" id="mSrvPrecio" value="35000">
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;">
      <button class="btn-edit" style="flex:1;" onclick="cerrarModalServicio()">Cancelar</button>
      <button class="btn-add" style="flex:2;" onclick="guardarServicio()">Guardar servicio →</button>
    </div>
  </div>
</div>

<script>
let recursosData = [];

// ── CONFIG GLOBAL ─────────────────────────────────────────
async function cargarConfig() {
  try {
    const cfg = await api('GET', '/api/agenda/config');
    document.getElementById('configGlobal').innerHTML = `
      <div class="config-row">
        <span>Landing público activo</span>
        <label class="toggle-wrap">
          <input type="checkbox" ${cfg.landing_publico_activo ? 'checked' : ''} onchange="toggleConfig('landing_publico_activo', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="config-row">
        <span>Recordatorio automático (${cfg.recordatorio_horas_antes}h antes)</span>
        <label class="toggle-wrap">
          <input type="checkbox" ${cfg.recordatorio_activo ? 'checked' : ''} onchange="toggleConfig('recordatorio_activo', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="config-row" style="flex-wrap:wrap;gap:8px;">
        <span>Título del landing</span>
        <input type="text" class="f-input" style="flex:1;min-width:180px;max-width:280px;" value="${cfg.titulo_landing || 'Agenda tu hora'}"
          onblur="actualizarTitulo(this.value)">
      </div>`;
  } catch(e) {}
}

async function toggleConfig(campo, valor) {
  try { await api('PUT', '/api/agenda/config', { [campo]: valor }); } catch(e) {}
}
async function actualizarTitulo(titulo) {
  try { await api('PUT', '/api/agenda/config', { titulo_landing: titulo }); toast('Guardado', 'ok'); } catch(e) {}
}

// ── RECURSOS ─────────────────────────────────────────────
async function cargarRecursos() {
  try {
    recursosData = await api('GET', '/api/agenda/recursos');
    renderRecursos(recursosData);
  } catch(e) {
    document.getElementById('recursosGrid').innerHTML = '<div style="color:#ff3f5b;font-size:12px;padding:20px;">Error cargando recursos</div>';
  }
}

function renderRecursos(recursos) {
  const grid = document.getElementById('recursosGrid');
  if (!recursos.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;padding:32px;text-align:center;color:#3a3a55;font-size:13px;">Sin profesionales configurados. Crea el primero.</div>';
    return;
  }
  grid.innerHTML = recursos.map(r => `
    <div class="rec-card">
      <div class="rec-header">
        <span class="rec-dot" style="background:${r.color};"></span>
        <div style="flex:1;">
          <div class="rec-nombre">${r.nombre}</div>
          ${r.especialidad ? `<div class="rec-esp">${r.especialidad}</div>` : ''}
        </div>
        <button class="btn-edit" onclick="editarRecurso(${r.id})">✏️</button>
      </div>

      <div style="margin-bottom:10px;">
        <div style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#3a3a55;margin-bottom:6px;">Horarios</div>
        <div class="horario-grid">
          ${['D','L','M','X','J','V','S'].map((dia, i) => {
            const activo = r.horarios?.some(h => h.dia_semana === i);
            return `<div class="dia-btn ${activo ? 'activo' : ''}">${dia}</div>`;
          }).join('')}
        </div>
        ${r.horarios?.length ? `<div style="font-size:10px;color:#7878a0;margin-top:5px;font-family:'IBM Plex Mono',monospace;">
          ${r.horarios[0]?.hora_inicio || ''} – ${r.horarios[0]?.hora_fin || ''} · slots ${r.horarios[0]?.duracion_slot_min || 30}min</div>` : ''}
        <button class="btn-edit" style="margin-top:6px;width:100%;" onclick="abrirModalHorarios(${r.id}, '${r.nombre}')">
          ⏰ Configurar horarios
        </button>
      </div>

      <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#3a3a55;margin-bottom:6px;">
          Servicios (${r.servicios?.length || 0})
        </div>
        ${(r.servicios || []).map(s => `
          <div class="servicio-item">
            <div><div class="s-nombre">${s.nombre}</div><div class="s-meta">${s.duracion_min}min · $${s.precio.toLocaleString('es-CL')}</div></div>
          </div>`).join('')}
        <button class="btn-edit" style="margin-top:6px;width:100%;" onclick="abrirModalServicio(${r.id})">
          + Agregar servicio
        </button>
      </div>
    </div>`).join('');
}

// ── MODAL RECURSO ─────────────────────────────────────────
function abrirModalRecurso() {
  document.getElementById('mRecTitulo').textContent = 'Nuevo profesional';
  document.getElementById('mRecId').value = '';
  document.getElementById('mRecNombre').value = '';
  document.getElementById('mRecEsp').value = '';
  document.getElementById('mRecColor').value = '#6366f1';
  document.getElementById('mRecTipo').value = 'profesional';
  document.getElementById('modalRecurso').classList.add('open');
}
function editarRecurso(id) {
  const r = recursosData.find(x => x.id === id);
  if (!r) return;
  document.getElementById('mRecTitulo').textContent = 'Editar profesional';
  document.getElementById('mRecId').value = r.id;
  document.getElementById('mRecNombre').value = r.nombre;
  document.getElementById('mRecEsp').value = r.especialidad || '';
  document.getElementById('mRecColor').value = r.color || '#6366f1';
  document.getElementById('mRecTipo').value = r.tipo || 'profesional';
  document.getElementById('modalRecurso').classList.add('open');
}
function cerrarModalRecurso() { document.getElementById('modalRecurso').classList.remove('open'); }
async function guardarRecurso() {
  const nombre = document.getElementById('mRecNombre').value.trim();
  if (!nombre) { toast('Nombre requerido', 'err'); return; }
  const payload = {
    nombre,
    tipo:        document.getElementById('mRecTipo').value,
    especialidad:document.getElementById('mRecEsp').value || null,
    color:       document.getElementById('mRecColor').value,
  };
  try {
    await api('POST', '/api/agenda/recursos', payload);
    toast('✓ Profesional guardado', 'ok');
    cerrarModalRecurso();
    cargarRecursos();
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

// ── MODAL HORARIOS ─────────────────────────────────────────
function abrirModalHorarios(recursoId, nombre) {
  document.getElementById('mHorRecursoId').value = recursoId;
  document.getElementById('mHorNombre').textContent = nombre;
  // Marcar Lun-Vie por defecto
  document.querySelectorAll('.dia-check-input').forEach((c, i) => { c.checked = [1,2,3,4,5].includes(i); });
  const r = recursosData.find(x => x.id === recursoId);
  if (r?.horarios?.length) {
    document.getElementById('mHorInicio').value = r.horarios[0].hora_inicio;
    document.getElementById('mHorFin').value    = r.horarios[0].hora_fin;
    document.getElementById('mHorSlot').value   = r.horarios[0].duracion_slot_min;
    const diasActivos = r.horarios.map(h => h.dia_semana);
    document.querySelectorAll('.dia-check-input').forEach(c => { c.checked = diasActivos.includes(parseInt(c.value)); });
  }
  document.getElementById('modalHorarios').classList.add('open');
}
function cerrarModalHorarios() { document.getElementById('modalHorarios').classList.remove('open'); }
async function guardarHorarios() {
  const recursoId = document.getElementById('mHorRecursoId').value;
  const inicio    = document.getElementById('mHorInicio').value;
  const fin       = document.getElementById('mHorFin').value;
  const slot      = parseInt(document.getElementById('mHorSlot').value);
  const dias      = [...document.querySelectorAll('.dia-check-input:checked')].map(c => parseInt(c.value));

  if (!dias.length) { toast('Selecciona al menos un día', 'err'); return; }

  const horarios = dias.map(dia => ({ dia_semana: dia, hora_inicio: inicio, hora_fin: fin, duracion_slot_min: slot }));

  try {
    await api('PUT', `/api/agenda/recursos/${recursoId}/horarios`, { horarios });
    toast('✓ Horarios guardados', 'ok');
    cerrarModalHorarios();
    cargarRecursos();
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

// ── MODAL SERVICIO ─────────────────────────────────────────
function abrirModalServicio(recursoId) {
  document.getElementById('mSrvTitulo').textContent = 'Nuevo servicio';
  document.getElementById('mSrvRecursoId').value = recursoId;
  document.getElementById('mSrvNombre').value = '';
  document.getElementById('mSrvDuracion').value = 30;
  document.getElementById('mSrvPrecio').value = 35000;
  document.getElementById('modalServicio').classList.add('open');
}
function cerrarModalServicio() { document.getElementById('modalServicio').classList.remove('open'); }
async function guardarServicio() {
  const recursoId = document.getElementById('mSrvRecursoId').value;
  const nombre    = document.getElementById('mSrvNombre').value.trim();
  if (!nombre) { toast('Nombre requerido', 'err'); return; }

  // El endpoint de servicios lo maneja directamente via AgendaController crearServicio
  // Por ahora crear via endpoint de recursos (el controller debe agregarlo)
  try {
    await api('POST', `/api/agenda/recursos/${recursoId}/servicios`, {
      nombre,
      duracion_min: parseInt(document.getElementById('mSrvDuracion').value),
      precio:       parseInt(document.getElementById('mSrvPrecio').value),
    });
    toast('✓ Servicio agregado', 'ok');
    cerrarModalServicio();
    cargarRecursos();
  } catch(e) { toast(e.message || 'Error', 'err'); }
}

// ── INIT ─────────────────────────────────────────────────
cargarConfig();
cargarRecursos();
</script>
@endsection
```

---

## PARTE 6 — VISTA LANDING PÚBLICO

### Archivo a CREAR: `resources/views/public/agenda.blade.php`

```blade
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $config->titulo_landing ?? 'Agenda tu hora' }}</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --ac: {{ $config->color_primario ?? '#6366f1' }};
  --ac-dim: color-mix(in srgb, var(--ac) 15%, transparent);
  --bg: #f7f6f3; --s1: #ffffff; --tx: #1a1a18; --t2: #6b6b60; --t3: #b0b0a0;
  --r: 12px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--tx);font-family:'IBM Plex Sans',sans-serif;min-height:100vh}
.nav{position:sticky;top:0;z-index:100;background:rgba(247,246,243,.93);backdrop-filter:blur(14px);border-bottom:1px solid rgba(0,0,0,.07);padding:0 20px;display:flex;align-items:center;height:58px;gap:14px;}
.nav-brand{font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:16px;letter-spacing:-.5px;flex-shrink:0}
.nav-logo{width:30px;height:30px;border-radius:7px;background:var(--ac);display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;font-weight:800;}
.staff-link{margin-left:auto;font-size:11px;color:var(--t3);text-decoration:none;font-weight:500;transition:color .15s;}
.staff-link:hover{color:var(--t2);}
.hero{padding:40px 20px 32px;max-width:960px;margin:0 auto;text-align:center;}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:var(--ac-dim);border:1px solid color-mix(in srgb,var(--ac) 25%,transparent);border-radius:20px;padding:4px 13px;font-size:11px;font-weight:700;color:var(--tx);letter-spacing:.5px;text-transform:uppercase;margin-bottom:14px;}
.hero-title{font-family:'IBM Plex Mono',monospace;font-size:clamp(24px,5vw,42px);font-weight:700;letter-spacing:-1.5px;line-height:1.15;margin-bottom:10px;}
.hero-sub{font-size:14px;color:var(--t2);max-width:460px;margin:0 auto 28px;}
/* WIZARD STEPS */
.steps{display:flex;justify-content:center;gap:0;margin-bottom:24px;}
.step-item{display:flex;align-items:center;gap:0;}
.step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'IBM Plex Mono',monospace;font-size:11px;font-weight:700;border:2px solid #d0d0c0;color:#b0b0a0;background:var(--bg);transition:all .2s;}
.step-label{font-size:10px;font-weight:600;color:#b0b0a0;margin:0 4px;display:none;}
.step-line{width:40px;height:2px;background:#d0d0c0;}
.step-item.done .step-num{background:var(--ac);border-color:var(--ac);color:#fff;}
.step-item.active .step-num{border-color:var(--ac);color:var(--ac);}
@media(min-width:500px){.step-label{display:block;}}
/* PANEL WIZARD */
.wizard{max-width:720px;margin:0 auto;padding:0 16px 60px;}
.wizard-panel{display:none;}
.wizard-panel.show{display:block;}
/* MÉDICOS GRID */
.medicos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;}
.medico-card{background:var(--s1);border:1.5px solid rgba(0,0,0,.08);border-radius:var(--r);padding:16px 14px;cursor:pointer;text-align:center;transition:all .18s;}
.medico-card:hover{border-color:var(--ac);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.medico-card.sel{border-color:var(--ac);background:var(--ac-dim);}
.medico-avatar{width:56px;height:56px;border-radius:50%;background:color-mix(in srgb,var(--ac) 15%,transparent);display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px;border:2px solid color-mix(in srgb,var(--ac) 25%,transparent);}
.medico-nombre{font-weight:700;font-size:14px;margin-bottom:3px;}
.medico-esp{font-size:11px;color:var(--t2);}
/* SERVICIOS */
.servicios-list{display:flex;flex-direction:column;gap:8px;}
.srv-btn{padding:13px 16px;background:var(--s1);border:1.5px solid rgba(0,0,0,.08);border-radius:var(--r);cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:13px;transition:all .15s;text-align:left;}
.srv-btn:hover{border-color:var(--ac);}
.srv-btn.sel{border-color:var(--ac);background:var(--ac-dim);}
.srv-nombre{font-weight:600;}
.srv-meta{font-size:11px;color:var(--t2);margin-top:2px;}
.srv-precio{font-family:'IBM Plex Mono',monospace;font-weight:700;color:var(--ac);font-size:14px;}
/* CALENDARIO */
.cal-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.cal-mes{font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:700;}
.cal-btn{background:none;border:1.5px solid rgba(0,0,0,.1);border-radius:8px;padding:5px 12px;cursor:pointer;font-size:14px;transition:all .12s;}
.cal-btn:hover{border-color:var(--ac);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:16px;}
.cal-dia-lbl{text-align:center;font-size:10px;font-weight:700;color:var(--t2);padding:4px 0;}
.cal-dia{text-align:center;padding:8px 4px;border-radius:8px;font-size:13px;cursor:pointer;border:1.5px solid transparent;transition:all .12s;}
.cal-dia.disponible:hover{border-color:var(--ac);background:var(--ac-dim);}
.cal-dia.sel{background:var(--ac);color:#fff;font-weight:700;}
.cal-dia.pasado,.cal-dia.vacio{color:var(--t3);pointer-events:none;}
.cal-dia.sin-horario{color:var(--t3);pointer-events:none;opacity:.4;}
/* SLOTS */
.slots-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:6px;}
.slot-btn{padding:9px 6px;border-radius:8px;border:1.5px solid rgba(0,0,0,.1);background:var(--s1);font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:600;cursor:pointer;text-align:center;transition:all .12s;}
.slot-btn:hover{border-color:var(--ac);}
.slot-btn.sel{background:var(--ac);border-color:var(--ac);color:#fff;}
/* FORMULARIO */
.form-field{margin-bottom:14px;}
.form-label{font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t2);margin-bottom:5px;display:block;}
.form-input{width:100%;background:var(--s1);border:1.5px solid rgba(0,0,0,.1);border-radius:var(--r);color:var(--tx);font-family:'IBM Plex Sans',sans-serif;font-size:14px;padding:11px 14px;outline:none;transition:border-color .15s;}
.form-input:focus{border-color:var(--ac);}
/* RESUMEN */
.resumen-card{background:var(--s1);border:1.5px solid rgba(0,0,0,.08);border-radius:var(--r);padding:16px;margin-bottom:14px;}
.res-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(0,0,0,.06);font-size:13px;}
.res-row:last-child{border-bottom:none;}
.res-lbl{color:var(--t2);}
.res-val{font-weight:600;}
/* BOTONES NAV */
.wiz-foot{display:flex;gap:10px;margin-top:18px;}
.btn-volver{flex:1;padding:12px;border-radius:var(--r);border:1.5px solid rgba(0,0,0,.1);background:none;color:var(--t2);font-size:13px;font-weight:600;cursor:pointer;transition:all .12s;}
.btn-volver:hover{border-color:var(--ac);color:var(--ac);}
.btn-siguiente{flex:2;padding:12px;border-radius:var(--r);border:none;background:var(--ac);color:#fff;font-size:14px;font-weight:700;cursor:pointer;transition:all .12s;}
.btn-siguiente:hover{background:color-mix(in srgb,var(--ac) 85%,#000);}
.btn-siguiente:disabled{opacity:.35;cursor:not-allowed;}
/* ÉXITO */
.exito-wrap{text-align:center;padding:40px 20px;}
.exito-icon{font-size:56px;margin-bottom:14px;}
.exito-titulo{font-family:'IBM Plex Mono',monospace;font-size:22px;font-weight:700;margin-bottom:8px;}
.exito-sub{font-size:13px;color:var(--t2);line-height:1.7;}
.footer{background:var(--s1);border-top:1px solid rgba(0,0,0,.07);padding:20px;text-align:center;font-size:11px;color:var(--t3);margin-top:40px;}
.footer a{color:var(--t3);text-decoration:none;font-weight:500;}
.footer a:hover{color:var(--t2);}
.loading-msg{text-align:center;padding:24px;font-size:13px;color:var(--t2);}
.error-msg{text-align:center;padding:20px;font-size:13px;color:#e53e3e;}
</style>
</head>
<body>
<header class="nav">
  <div class="nav-logo">{{ substr(tenant('nombre', 'B'), 0, 1) }}</div>
  <div class="nav-brand">{{ tenant('nombre', 'BenderAnd') }}</div>
  <a href="/auth/login/web" class="staff-link">Acceso staff →</a>
</header>

<div class="hero">
  <div class="hero-tag">📅 Reserva en línea</div>
  <h1 class="hero-title">{{ $config->titulo_landing ?? 'Agenda tu hora' }}</h1>
  @if($config->descripcion_landing)
    <p class="hero-sub">{{ $config->descripcion_landing }}</p>
  @else
    <p class="hero-sub">Elige tu profesional, servicio y horario. Rápido, sin llamadas.</p>
  @endif
</div>

<div class="wizard">
  <!-- STEPS INDICATOR -->
  <div class="steps" id="stepsBar">
    <div class="step-item active" id="st1"><div class="step-num">1</div><div class="step-label">Profesional</div></div>
    <div class="step-line"></div>
    <div class="step-item" id="st2"><div class="step-num">2</div><div class="step-label">Servicio</div></div>
    <div class="step-line"></div>
    <div class="step-item" id="st3"><div class="step-num">3</div><div class="step-label">Fecha y hora</div></div>
    <div class="step-line"></div>
    <div class="step-item" id="st4"><div class="step-num">4</div><div class="step-label">Tus datos</div></div>
  </div>

  <!-- PASO 1: PROFESIONAL -->
  <div class="wizard-panel show" id="p1">
    <div style="font-size:16px;font-weight:700;margin-bottom:14px;">¿Con quién quieres atenderte?</div>
    <div class="medicos-grid" id="medicosList">
      <div class="loading-msg">Cargando profesionales...</div>
    </div>
    <div class="wiz-foot" style="margin-top:18px;">
      <button class="btn-siguiente" id="btnP1" disabled onclick="irPaso(2)">Continuar →</button>
    </div>
  </div>

  <!-- PASO 2: SERVICIO -->
  <div class="wizard-panel" id="p2">
    <div style="font-size:16px;font-weight:700;margin-bottom:14px;">¿Qué tipo de atención necesitas?</div>
    <div class="servicios-list" id="serviciosList">
      <div class="loading-msg">Cargando servicios...</div>
    </div>
    <div class="wiz-foot">
      <button class="btn-volver" onclick="irPaso(1)">← Volver</button>
      <button class="btn-siguiente" id="btnP2" disabled onclick="irPaso(3)">Continuar →</button>
    </div>
  </div>

  <!-- PASO 3: FECHA Y HORA -->
  <div class="wizard-panel" id="p3">
    <div style="font-size:16px;font-weight:700;margin-bottom:14px;">Elige fecha y hora</div>
    <!-- Calendario -->
    <div class="cal-nav">
      <button class="cal-btn" onclick="mesAnterior()">‹</button>
      <div class="cal-mes" id="calMes"></div>
      <button class="cal-btn" onclick="mesSiguiente()">›</button>
    </div>
    <div class="cal-grid" id="calGrid"></div>

    <!-- Slots -->
    <div id="slotsContainer" style="display:none;">
      <div style="font-size:13px;font-weight:600;margin-bottom:10px;" id="slotsTitle">Horarios disponibles</div>
      <div class="slots-grid" id="slotsList"></div>
    </div>

    <div class="wiz-foot" style="margin-top:18px;">
      <button class="btn-volver" onclick="irPaso(2)">← Volver</button>
      <button class="btn-siguiente" id="btnP3" disabled onclick="irPaso(4)">Continuar →</button>
    </div>
  </div>

  <!-- PASO 4: DATOS PERSONALES -->
  <div class="wizard-panel" id="p4">
    <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Tus datos de contacto</div>
    <div style="font-size:12px;color:var(--t2);margin-bottom:16px;">Solo para confirmar y enviarte recordatorio.</div>

    <!-- Resumen cita -->
    <div class="resumen-card">
      <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--t2);margin-bottom:8px;">Tu cita</div>
      <div class="res-row"><span class="res-lbl">Profesional</span><span class="res-val" id="resMedico">—</span></div>
      <div class="res-row"><span class="res-lbl">Servicio</span><span class="res-val" id="resSrv">—</span></div>
      <div class="res-row"><span class="res-lbl">Fecha</span><span class="res-val" id="resFecha">—</span></div>
      <div class="res-row"><span class="res-lbl">Hora</span><span class="res-val" id="resHora" style="font-family:'IBM Plex Mono',monospace;">—</span></div>
      <div class="res-row"><span class="res-lbl">Valor</span><span class="res-val" id="resPrecio" style="color:var(--ac);">—</span></div>
    </div>

    <div class="form-field">
      <label class="form-label">Nombre completo *</label>
      <input type="text" class="form-input" id="fNombre" placeholder="Tu nombre completo">
    </div>
    <div class="form-field">
      <label class="form-label">Teléfono *</label>
      <input type="tel" class="form-input" id="fTelefono" placeholder="+56 9 1234 5678">
    </div>
    @if($config->requiere_email)
    <div class="form-field">
      <label class="form-label">Email</label>
      <input type="email" class="form-input" id="fEmail" placeholder="tu@email.cl">
    </div>
    @endif
    <div class="form-field">
      <label class="form-label">Motivo de consulta (opcional)</label>
      <textarea class="form-input" id="fNotas" rows="3" style="resize:none;" placeholder="Brevemente..."></textarea>
    </div>

    <div class="wiz-foot">
      <button class="btn-volver" onclick="irPaso(3)">← Volver</button>
      <button class="btn-siguiente" id="btnP4" onclick="confirmarCita()">✅ Confirmar cita</button>
    </div>
  </div>

  <!-- ÉXITO -->
  <div class="wizard-panel" id="p5">
    <div class="exito-wrap">
      <div class="exito-icon">🎉</div>
      <div class="exito-titulo">¡Cita confirmada!</div>
      <p class="exito-sub" id="exitoMsg">Tu cita ha sido agendada. Te contactaremos para confirmarte.</p>
      <button class="btn-siguiente" style="max-width:280px;margin:20px auto 0;display:block;" onclick="location.reload()">
        Agendar otra cita
      </button>
    </div>
  </div>
</div>

<footer class="footer">
  Powered by <a href="https://benderand.cl" target="_blank">BenderAnd</a> · Reservas en línea
</footer>

<script>
// ── ESTADO ───────────────────────────────────────────────
const RECURSOS_INICIALES = @json($recursos);
let estado = {
  paso: 1,
  recursoId: null, recursoNombre: '',
  servicioId: null, servicioNombre: '', servicioDuracion: 30, servicioPrecio: 0,
  fecha: null, fechaFmt: '',
  horaInicio: null, horaFin: null,
};

let calFecha = new Date(); calFecha.setDate(1);

// ── PASO 1: MÉDICOS ──────────────────────────────────────
function renderMedicos() {
  const el = document.getElementById('medicosList');
  if (!RECURSOS_INICIALES.length) {
    el.innerHTML = '<div class="error-msg">Sin profesionales disponibles. Contacta directamente.</div>';
    return;
  }
  el.innerHTML = RECURSOS_INICIALES.map(r => `
    <div class="medico-card" onclick="selMedico(${r.id}, '${r.nombre}', this)">
      <div class="medico-avatar">${r.nombre.charAt(0).toUpperCase()}</div>
      <div class="medico-nombre">${r.nombre}</div>
      ${r.especialidad ? `<div class="medico-esp">${r.especialidad}</div>` : ''}
    </div>`).join('');
}
function selMedico(id, nombre, el) {
  document.querySelectorAll('.medico-card').forEach(c => c.classList.remove('sel'));
  el.classList.add('sel');
  estado.recursoId = id; estado.recursoNombre = nombre;
  document.getElementById('btnP1').disabled = false;
}

// ── PASO 2: SERVICIOS ─────────────────────────────────────
function renderServicios() {
  const recurso = RECURSOS_INICIALES.find(r => r.id === estado.recursoId);
  const servicios = recurso?.servicios || [];
  const el = document.getElementById('serviciosList');
  if (!servicios.length) {
    el.innerHTML = `<div class="srv-btn sel" onclick="selServicio(null,'Consulta general',30,35000,this)">
      <div><div class="srv-nombre">Consulta general</div><div class="srv-meta">30 min</div></div>
      <div class="srv-precio">$35.000</div>
    </div>`;
    selServicio(null, 'Consulta general', 30, 35000, el.querySelector('.srv-btn'));
    return;
  }
  el.innerHTML = servicios.map(s => `
    <div class="srv-btn" onclick="selServicio(${s.id},'${s.nombre}',${s.duracion_min},${s.precio},this)">
      <div><div class="srv-nombre">${s.nombre}</div><div class="srv-meta">${s.duracion_min} min</div></div>
      <div class="srv-precio">$${s.precio.toLocaleString('es-CL')}</div>
    </div>`).join('');
}
function selServicio(id, nombre, duracion, precio, el) {
  document.querySelectorAll('.srv-btn').forEach(b => b.classList.remove('sel'));
  el.classList.add('sel');
  estado.servicioId = id; estado.servicioNombre = nombre;
  estado.servicioDuracion = duracion; estado.servicioPrecio = precio;
  document.getElementById('btnP2').disabled = false;
}

// ── PASO 3: CALENDARIO ────────────────────────────────────
const DIAS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function renderCalendario() {
  document.getElementById('calMes').textContent = `${MESES[calFecha.getMonth()]} ${calFecha.getFullYear()}`;
  const grid = document.getElementById('calGrid');
  grid.innerHTML = DIAS.map(d => `<div class="cal-dia-lbl">${d}</div>`).join('');

  const hoy = new Date(); hoy.setHours(0,0,0,0);
  const primerDia = new Date(calFecha.getFullYear(), calFecha.getMonth(), 1).getDay();
  const diasEnMes = new Date(calFecha.getFullYear(), calFecha.getMonth()+1, 0).getDate();

  for (let i = 0; i < primerDia; i++) grid.innerHTML += '<div class="cal-dia vacio"></div>';

  for (let d = 1; d <= diasEnMes; d++) {
    const fecha = new Date(calFecha.getFullYear(), calFecha.getMonth(), d);
    const pasado = fecha < hoy;
    const iso = toISO(fecha);
    const sel = estado.fecha === iso;
    grid.innerHTML += `<div class="cal-dia ${pasado ? 'pasado' : 'disponible'} ${sel ? 'sel' : ''}"
      onclick="${pasado ? '' : `selFecha('${iso}', '${fecha.toLocaleDateString('es-CL',{weekday:'long',day:'numeric',month:'long'})}', this)`}"
    >${d}</div>`;
  }
}

function mesAnterior() { calFecha.setMonth(calFecha.getMonth()-1); renderCalendario(); }
function mesSiguiente() { calFecha.setMonth(calFecha.getMonth()+1); renderCalendario(); }

async function selFecha(iso, fmt, el) {
  document.querySelectorAll('.cal-dia.disponible').forEach(d => d.classList.remove('sel'));
  el.classList.add('sel');
  estado.fecha = iso; estado.fechaFmt = fmt;
  estado.horaInicio = null; estado.horaFin = null;
  document.getElementById('btnP3').disabled = true;
  document.getElementById('slotsContainer').style.display = 'none';
  await cargarSlots();
}

async function cargarSlots() {
  if (!estado.recursoId || !estado.fecha) return;
  const cont = document.getElementById('slotsContainer');
  const lista = document.getElementById('slotsList');
  cont.style.display = 'block';
  lista.innerHTML = '<div class="loading-msg">Verificando disponibilidad...</div>';
  document.getElementById('slotsTitle').textContent = `Horarios disponibles — ${estado.fechaFmt}`;

  try {
    const slots = await fetchPublic(`/api/public/agenda/slots?recurso_id=${estado.recursoId}&fecha=${estado.fecha}&duracion=${estado.servicioDuracion}`);
    if (!slots.length) {
      lista.innerHTML = '<div class="error-msg">Sin horarios disponibles para este día. Prueba otro.</div>';
      return;
    }
    lista.innerHTML = slots.map(s => `
      <button class="slot-btn" onclick="selSlot('${s.hora_inicio}','${s.hora_fin}',this)">${s.hora_inicio}</button>`
    ).join('');
  } catch(e) {
    lista.innerHTML = '<div class="error-msg">Error al cargar horarios. Intenta de nuevo.</div>';
  }
}

function selSlot(hi, hf, el) {
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('sel'));
  el.classList.add('sel');
  estado.horaInicio = hi; estado.horaFin = hf;
  document.getElementById('btnP3').disabled = false;
}

// ── PASO 4: DATOS ─────────────────────────────────────────
function llenarResumen() {
  document.getElementById('resMedico').textContent = estado.recursoNombre;
  document.getElementById('resSrv').textContent    = estado.servicioNombre;
  document.getElementById('resFecha').textContent  = estado.fechaFmt;
  document.getElementById('resHora').textContent   = `${estado.horaInicio} – ${estado.horaFin}`;
  document.getElementById('resPrecio').textContent = estado.servicioPrecio ? `$${estado.servicioPrecio.toLocaleString('es-CL')}` : 'Consultar';
}

async function confirmarCita() {
  const nombre = document.getElementById('fNombre').value.trim();
  const tel    = document.getElementById('fTelefono').value.trim();
  if (!nombre || !tel) { alert('Nombre y teléfono son obligatorios.'); return; }

  const btn = document.getElementById('btnP4');
  btn.disabled = true; btn.textContent = 'Confirmando...';

  const payload = {
    agenda_recurso_id:  estado.recursoId,
    agenda_servicio_id: estado.servicioId,
    fecha:              estado.fecha,
    hora_inicio:        estado.horaInicio,
    hora_fin:           estado.horaFin,
    paciente_nombre:    nombre,
    paciente_telefono:  tel,
    paciente_email:     document.getElementById('fEmail')?.value || null,
    notas_publicas:     document.getElementById('fNotas').value || null,
  };

  try {
    await fetchPublic('/api/public/agenda/cita', 'POST', payload);
    document.getElementById('exitoMsg').textContent =
      `Tu cita con ${estado.recursoNombre} el ${estado.fechaFmt} a las ${estado.horaInicio} fue registrada. Te confirmaremos por teléfono.`;
    irPaso(5);
  } catch(e) {
    alert(e.message || 'Error al confirmar. Intenta de nuevo.');
    btn.disabled = false; btn.textContent = '✅ Confirmar cita';
  }
}

// ── NAVEGACIÓN ────────────────────────────────────────────
function irPaso(paso) {
  [1,2,3,4,5].forEach(p => {
    document.getElementById(`p${p}`).classList.toggle('show', p === paso);
    const st = document.getElementById(`st${p}`);
    if (!st) return;
    st.classList.remove('active','done');
    if (p < paso) st.classList.add('done');
    else if (p === paso) st.classList.add('active');
  });
  estado.paso = paso;

  if (paso === 2) renderServicios();
  if (paso === 3) renderCalendario();
  if (paso === 4) llenarResumen();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── UTILS ─────────────────────────────────────────────────
function toISO(d) { return d.toISOString().split('T')[0]; }

async function fetchPublic(url, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' } };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || data.message || `Error ${res.status}`);
  return data;
}

// ── INIT ─────────────────────────────────────────────────
renderMedicos();
</script>
</body>
</html>
```

---

## PARTE 7 — ENDPOINT FALTANTE EN CONTROLADOR: servicios por recurso

Agregar en `AgendaController.php`:

```php
/** POST /api/agenda/recursos/{id}/servicios */
public function crearServicio(Request $r, int $recursoId)
{
    $recurso = AgendaRecurso::findOrFail($recursoId);
    $r->validate([
        'nombre'       => 'required|string|max:100',
        'duracion_min' => 'required|integer|min:5',
        'precio'       => 'required|integer|min:0',
    ]);
    $srv = $recurso->servicios()->create($r->only(['nombre','duracion_min','precio']));
    return response()->json($srv, 201);
}
```

Y agregar la ruta en `routes/tenant.php` dentro del grupo M08:

```php
Route::post('/recursos/{id}/servicios', [AgendaController::class, 'crearServicio']);
```

---

## PARTE 8 — OPERARIO MÉDICO: vista simplificada solo su columna

El comportamiento ya está en la lógica de `pos/agenda.blade.php`:
cuando `ES_PROFESIONAL = true` y `MI_RECURSO_ID` está definido,
la llamada a `/api/agenda/dia` ya filtra `?recurso_id=X`.

Para que el operario médico tenga su `agenda_recurso_id` vinculado,
el admin debe ir a **Admin → Agenda** y editar el profesional asignando
el `usuario_id` correspondiente. Esto se puede hacer via el modal de edición:

Agregar en el modal de recurso (admin/agenda blade) un campo `usuario_id`:

```html
{{-- dentro del modal maa-body para editar recurso --}}
<div class="maa-field">
  <label class="maa-label">Usuario del sistema (opcional — para el acceso del profesional)</label>
  <select class="f-input" id="mRecUsuario">
    <option value="">Sin vincular</option>
    {{-- poblar via JS desde /api/usuarios --}}
  </select>
</div>
```

Y en `guardarRecurso()` JS incluir `usuario_id: parseInt(document.getElementById('mRecUsuario').value) || null`.

---

## PARTE 9 — COMANDOS DE EJECUCIÓN

```sh
# 1. Migrar solo los tenants médico, pádel y legal
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant \
  --tenants=demo-medico,demo-padel,demo-legal"

# 2. Seed de datos demo en cada tenant
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run \
  'db:seed --class=AgendaDemoSeeder' \
  --tenants=demo-medico,demo-padel,demo-legal"

# 3. Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 4. Verificar rutas M08
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=agenda"

# 5. Verificar en demo-medico
# Acceder como cajero:   http://demo-medico.localhost:8000/pos/agenda
# Acceder como admin:    http://demo-medico.localhost:8000/admin/agenda
# Landing público:       http://demo-medico.localhost:8000/agenda
```

---

## RESUMEN DE ARCHIVOS A CREAR O MODIFICAR

| Acción | Archivo |
|---|---|
| MODIFICAR | `resources/views/layouts/app.blade.php` — agregar menú Agenda condicional M08 |
| MODIFICAR | `routes/tenant.php` — rutas M08 completas + servicio endpoint |
| MODIFICAR | `app/Http/Controllers/Tenant/AgendaController.php` — métodos faltantes |
| CREAR | `resources/views/pos/agenda.blade.php` — vista POS completa |
| CREAR | `resources/views/admin/agenda/index.blade.php` — admin config |
| CREAR | `resources/views/public/agenda.blade.php` — landing público |

---

*BenderAnd ERP · H25 · M08 Agenda Completo · 2026-03-26*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade*
