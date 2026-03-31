# SPEC — Vista Profesional del Operario (Médico / Dentista / Abogado / Técnico)
**Sistema:** BenderAnd ERP · Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade
**Fecha:** 2026-03-26
**Container:** `benderandos_app` · código en `/app`
**Depende de:** M08 DB+Service implementados · AgendaAutoRegistroService (SPEC anterior)

---

## DIAGNÓSTICO

La vista actual en `/operario` muestra "Stock & Ventas" con tres tabs: Vender / Stock / Mis ventas.
Esto es correcto para un operario de bodega, pero completamente incorrecto para un **profesional** vinculado a M08 (médico, dentista, psicólogo, abogado, técnico con agenda).

El problema es que la ruta `/operario` carga siempre la misma vista sin considerar:
1. Si el usuario tiene un `AgendaRecurso` vinculado (es profesional con agenda)
2. Si el rubro del tenant tiene M08 activo

**Solución:** Split de la vista operario en dos rutas según contexto:
- `GET /operario` → si tiene recurso agenda → redirige a `/profesional`
- `GET /profesional` → vista profesional completa (agenda + pacientes + seguimiento)
- `GET /operario` sin recurso → vista actual de stock/ventas (sin cambios)

---

## PARTE 1 — CONTROLADOR: split de rutas

### Modificar `app/Http/Controllers/Tenant/WebPanelController.php`

En el método que maneja `GET /operario`, agregar redirección condicional:

```php
public function operario()
{
    $usuario = auth()->user();

    // Si M08 activo y usuario tiene recurso de agenda → vista profesional
    $rubroConfig = \App\Models\Tenant\RubroConfig::first();
    $tieneM08    = in_array('M08', $rubroConfig?->modulos_activos ?? []);

    if ($tieneM08) {
        $recurso = \App\Models\Tenant\AgendaRecurso::with(['servicios','horarios'])
            ->where('usuario_id', $usuario->id)
            ->where('activo', true)
            ->first();

        if ($recurso) {
            return redirect('/profesional');
        }
    }

    // Sin recurso → vista clásica de stock/ventas
    return view('tenant.operario.index');
}

/**
 * Vista profesional: médico, dentista, psicólogo, abogado, técnico con M08.
 * Muestra agenda personal + pacientes + historial + seguimiento.
 */
public function profesional()
{
    $usuario = auth()->user();

    // Obtener o crear recurso automáticamente
    $recurso = \App\Models\Tenant\AgendaRecurso::with(['servicios','horarios'])
        ->where('usuario_id', $usuario->id)
        ->where('activo', true)
        ->first();

    if (!$recurso) {
        $recurso = app(\App\Services\AgendaAutoRegistroService::class)
            ->registrarOperario($usuario);
        if ($recurso) {
            $recurso->load(['servicios','horarios']);
        }
    }

    $rubroConfig = \App\Models\Tenant\RubroConfig::first();
    $labelCliente = $rubroConfig?->label_cliente ?? 'Paciente';
    $labelOperario = $rubroConfig?->label_operario ?? 'Profesional';

    return view('tenant.profesional.index', compact(
        'recurso', 'usuario', 'rubroConfig', 'labelCliente', 'labelOperario'
    ));
}
```

### Agregar rutas en `routes/tenant.php`

Dentro del grupo `auth:sanctum`:

```php
// Vista profesional (redirigida desde /operario si tiene recurso M08)
Route::get('/profesional', [WebPanelController::class, 'profesional'])->name('profesional');

// API del profesional — sus pacientes y seguimiento
Route::prefix('api/profesional')->middleware('check.module:M08')->group(function () {
    Route::get('/pacientes',                    [ProfesionalController::class, 'pacientes']);
    Route::get('/pacientes/{id}',               [ProfesionalController::class, 'paciente']);
    Route::get('/pacientes/{id}/historial',     [ProfesionalController::class, 'historialPaciente']);
    Route::post('/pacientes/{id}/nota',         [ProfesionalController::class, 'agregarNota']);
    Route::get('/pacientes/{id}/seguimiento',   [ProfesionalController::class, 'seguimientoPaciente']);
    Route::post('/pacientes/{id}/seguimiento',  [ProfesionalController::class, 'crearSeguimiento']);
    Route::put('/seguimiento/{id}',             [ProfesionalController::class, 'actualizarSeguimiento']);
    Route::get('/estadisticas',                 [ProfesionalController::class, 'estadisticas']);
});
```

---

## PARTE 2 — MIGRACIÓN: tabla seguimiento_paciente

Los profesionales necesitan hacer seguimiento entre citas: próximas acciones, derivaciones, alertas.

```bash
docker exec benderandos_app sh -c "cd /app && php artisan make:migration \
  create_seguimiento_paciente_table \
  --path=database/migrations/tenant"
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seguimiento_paciente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');           // FK clientes
            $table->unsignedBigInteger('usuario_id');           // profesional que lo registra
            $table->unsignedBigInteger('agenda_cita_id')->nullable(); // cita asociada

            // Tipo de seguimiento
            $table->enum('tipo', [
                'nota_clinica',      // anotación clínica libre
                'indicacion',        // indicaciones dadas al paciente
                'derivacion',        // derivar a otro especialista
                'examen',            // solicitud de examen
                'alerta',            // alerta de seguimiento activa
                'proxima_accion',    // tarea pendiente
                'llamada',           // llamada de seguimiento realizada
            ])->default('nota_clinica');

            $table->text('contenido');                           // texto del seguimiento
            $table->date('fecha_seguimiento')->nullable();       // fecha para revisar
            $table->boolean('resuelto')->default(false);         // si fue atendido
            $table->boolean('privado')->default(true);           // solo lo ve el profesional
            $table->timestamps();

            $table->index(['cliente_id', 'usuario_id']);
            $table->index(['fecha_seguimiento', 'resuelto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_paciente');
    }
};
```

---

## PARTE 3 — MODELO SeguimientoPaciente

### Crear: `app/Models/Tenant/SeguimientoPaciente.php`

```php
<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SeguimientoPaciente extends Model
{
    protected $table = 'seguimiento_paciente';

    protected $fillable = [
        'cliente_id', 'usuario_id', 'agenda_cita_id',
        'tipo', 'contenido', 'fecha_seguimiento',
        'resuelto', 'privado',
    ];

    protected $casts = [
        'resuelto'          => 'boolean',
        'privado'           => 'boolean',
        'fecha_seguimiento' => 'date',
    ];

    public function cliente()   { return $this->belongsTo(Cliente::class); }
    public function usuario()   { return $this->belongsTo(Usuario::class); }
    public function cita()      { return $this->belongsTo(AgendaCita::class, 'agenda_cita_id'); }

    public function scopePendientes($q)
    {
        return $q->where('resuelto', false)
                 ->whereNotNull('fecha_seguimiento')
                 ->where('fecha_seguimiento', '<=', now()->addDays(7));
    }

    public function scopeDelProfesional($q, int $usuarioId)
    {
        return $q->where('usuario_id', $usuarioId);
    }
}
```

---

## PARTE 4 — CONTROLADOR ProfesionalController

### Crear: `app/Http/Controllers/Tenant/ProfesionalController.php`

```php
<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\{
    AgendaCita, AgendaRecurso, Cliente,
    SeguimientoPaciente
};
use Illuminate\Http\Request;

class ProfesionalController extends Controller
{
    /**
     * GET /api/profesional/pacientes
     * Lista de pacientes que han tenido citas con este profesional.
     * Filtros: ?q=búsqueda &estado=activo|inactivo &pendiente_seguimiento=1
     */
    public function pacientes(Request $r)
    {
        $recurso = $this->miRecurso();
        if (!$recurso) return response()->json([], 200);

        // IDs de clientes con citas en este recurso
        $clienteIds = AgendaCita::where('agenda_recurso_id', $recurso->id)
            ->whereNotNull('cliente_id')
            ->distinct()
            ->pluck('cliente_id');

        $query = Cliente::whereIn('id', $clienteIds)
            ->withCount([
                'agendaCitas as total_citas' => fn($q) =>
                    $q->where('agenda_recurso_id', $recurso->id),
                'agendaCitas as citas_completadas' => fn($q) =>
                    $q->where('agenda_recurso_id', $recurso->id)
                      ->where('estado', 'completada'),
            ])
            ->with([
                'agendaCitas' => fn($q) =>
                    $q->where('agenda_recurso_id', $recurso->id)
                      ->orderByDesc('fecha')
                      ->limit(1)
                      ->select('id','cliente_id','fecha','hora_inicio','estado'),
            ]);

        if ($r->q) {
            $q = '%' . $r->q . '%';
            $query->where(fn($qb) =>
                $qb->where('nombre', 'ilike', $q)
                   ->orWhere('rut', 'ilike', $q)
                   ->orWhere('telefono', 'ilike', $q)
            );
        }

        if ($r->pendiente_seguimiento) {
            $pacientesConSeguimiento = SeguimientoPaciente::where('usuario_id', auth()->id())
                ->where('resuelto', false)
                ->whereNotNull('fecha_seguimiento')
                ->pluck('cliente_id')
                ->unique();
            $query->whereIn('id', $pacientesConSeguimiento);
        }

        $pacientes = $query->orderBy('nombre')->get();

        // Agregar info de seguimiento pendiente
        $pacientes->each(function ($p) {
            $p->seguimiento_pendiente = SeguimientoPaciente::where('cliente_id', $p->id)
                ->where('usuario_id', auth()->id())
                ->where('resuelto', false)
                ->whereNotNull('fecha_seguimiento')
                ->count();
            $p->ultima_cita = $p->agendaCitas->first();
            unset($p->agendaCitas);
        });

        return response()->json($pacientes);
    }

    /**
     * GET /api/profesional/pacientes/{id}
     * Ficha completa del paciente.
     */
    public function paciente(int $id)
    {
        $recurso = $this->miRecurso();

        $paciente = Cliente::findOrFail($id);

        // Resumen de citas con este profesional
        $resumenCitas = AgendaCita::where('cliente_id', $id)
            ->where('agenda_recurso_id', $recurso?->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN estado = \'completada\' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = \'cancelada\' THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN estado = \'no_asistio\' THEN 1 ELSE 0 END) as no_asistio,
                MAX(fecha) as ultima_fecha
            ')
            ->first();

        return response()->json([
            'paciente'     => $paciente,
            'resumen_citas'=> $resumenCitas,
        ]);
    }

    /**
     * GET /api/profesional/pacientes/{id}/historial
     * Historial completo de citas + seguimientos del paciente con este profesional.
     */
    public function historialPaciente(int $id)
    {
        $recurso = $this->miRecurso();

        $citas = AgendaCita::where('cliente_id', $id)
            ->when($recurso, fn($q) => $q->where('agenda_recurso_id', $recurso->id))
            ->with(['servicio:id,nombre,duracion_min','recurso:id,nombre'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->get()
            ->map(function ($c) {
                return [
                    'id'              => $c->id,
                    'fecha'           => $c->fecha,
                    'hora_inicio'     => $c->hora_inicio,
                    'hora_fin'        => $c->hora_fin,
                    'estado'          => $c->estado,
                    'servicio'        => $c->servicio?->nombre,
                    'notas_publicas'  => $c->notas_publicas,
                    'notas_internas'  => $c->notas_internas,  // solo visible al profesional
                    'tipo'            => 'cita',
                ];
            });

        $seguimientos = SeguimientoPaciente::where('cliente_id', $id)
            ->where('usuario_id', auth()->id())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => [
                'id'                => $s->id,
                'fecha'             => $s->created_at->toDateString(),
                'tipo'              => $s->tipo,
                'contenido'         => $s->contenido,
                'fecha_seguimiento' => $s->fecha_seguimiento,
                'resuelto'          => $s->resuelto,
                'origen'            => 'seguimiento',
            ]);

        // Mezclar y ordenar por fecha desc
        $timeline = $citas->concat($seguimientos)
            ->sortByDesc('fecha')
            ->values();

        return response()->json($timeline);
    }

    /**
     * POST /api/profesional/pacientes/{id}/nota
     * Agregar nota clínica o seguimiento al paciente.
     */
    public function agregarNota(Request $r, int $id)
    {
        Cliente::findOrFail($id); // validar que existe

        $r->validate([
            'tipo'              => 'required|in:nota_clinica,indicacion,derivacion,examen,alerta,proxima_accion,llamada',
            'contenido'         => 'required|string|max:2000',
            'fecha_seguimiento' => 'nullable|date',
            'agenda_cita_id'    => 'nullable|integer',
            'privado'           => 'nullable|boolean',
        ]);

        $seg = SeguimientoPaciente::create([
            'cliente_id'        => $id,
            'usuario_id'        => auth()->id(),
            'agenda_cita_id'    => $r->agenda_cita_id,
            'tipo'              => $r->tipo,
            'contenido'         => $r->contenido,
            'fecha_seguimiento' => $r->fecha_seguimiento,
            'resuelto'          => false,
            'privado'           => $r->privado ?? true,
        ]);

        return response()->json($seg, 201);
    }

    /**
     * GET /api/profesional/pacientes/{id}/seguimiento
     * Seguimientos pendientes del paciente.
     */
    public function seguimientoPaciente(int $id)
    {
        $items = SeguimientoPaciente::where('cliente_id', $id)
            ->where('usuario_id', auth()->id())
            ->orderBy('resuelto')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items);
    }

    /**
     * POST /api/profesional/pacientes/{id}/seguimiento
     */
    public function crearSeguimiento(Request $r, int $id)
    {
        return $this->agregarNota($r, $id);
    }

    /**
     * PUT /api/profesional/seguimiento/{id}
     * Marcar seguimiento como resuelto o actualizar.
     */
    public function actualizarSeguimiento(Request $r, int $id)
    {
        $seg = SeguimientoPaciente::where('id', $id)
            ->where('usuario_id', auth()->id())
            ->firstOrFail();

        $seg->update($r->only(['contenido','fecha_seguimiento','resuelto','tipo']));
        return response()->json($seg);
    }

    /**
     * GET /api/profesional/estadisticas
     * KPIs del profesional: citas hoy, semana, pacientes activos, seguimientos pendientes.
     */
    public function estadisticas()
    {
        $recurso = $this->miRecurso();
        $uid     = auth()->id();
        $hoy     = now()->toDateString();
        $semIni  = now()->startOfWeek()->toDateString();
        $semFin  = now()->endOfWeek()->toDateString();

        return response()->json([
            'citas_hoy'            => AgendaCita::where('agenda_recurso_id', $recurso?->id)
                                        ->where('fecha', $hoy)
                                        ->whereNotIn('estado', ['cancelada'])
                                        ->count(),
            'citas_semana'         => AgendaCita::where('agenda_recurso_id', $recurso?->id)
                                        ->whereBetween('fecha', [$semIni, $semFin])
                                        ->whereNotIn('estado', ['cancelada'])
                                        ->count(),
            'pacientes_totales'    => AgendaCita::where('agenda_recurso_id', $recurso?->id)
                                        ->whereNotNull('cliente_id')
                                        ->distinct('cliente_id')
                                        ->count('cliente_id'),
            'seguimientos_pendientes' => SeguimientoPaciente::where('usuario_id', $uid)
                                        ->where('resuelto', false)
                                        ->whereNotNull('fecha_seguimiento')
                                        ->count(),
            'proxima_cita'         => AgendaCita::where('agenda_recurso_id', $recurso?->id)
                                        ->where('fecha', '>=', $hoy)
                                        ->whereNotIn('estado', ['cancelada','completada'])
                                        ->orderBy('fecha')->orderBy('hora_inicio')
                                        ->select('id','fecha','hora_inicio','paciente_nombre','estado')
                                        ->first(),
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────

    private function miRecurso(): ?AgendaRecurso
    {
        return AgendaRecurso::where('usuario_id', auth()->id())
            ->where('activo', true)
            ->first();
    }
}
```

### Agregar relación en `app/Models/Tenant/Cliente.php`

```php
public function agendaCitas()
{
    return $this->hasMany(AgendaCita::class, 'cliente_id');
}
```

---

## PARTE 5 — VISTA PROFESIONAL

### Crear: `resources/views/tenant/profesional/index.blade.php`

La vista tiene **4 secciones en tabs** accesibles desde el sidebar izquierdo:

```
📅  Agenda Hoy     — Timeline del día, citas con detalle en panel lateral
👥  Pacientes      — Lista con buscador, última cita, seguimientos pendientes
🔍  Seguimiento    — Alertas y tareas pendientes de todos los pacientes
⚙️  Mi Perfil      — Horarios, servicios ofrecidos, configuración personal
```

```blade
@extends('tenant.layout')
@section('title', 'Mi Panel — ' . ($labelOperario ?? 'Profesional'))

@section('content')
<style>
/* ══════════════════════════════════════════════════════
   SHELL PROFESIONAL
══════════════════════════════════════════════════════ */
.prof-shell {
    display: flex;
    height: calc(100vh - 56px);
    background: #08080a;
    overflow: hidden;
}

/* Sidebar de navegación interna */
.prof-nav {
    width: 200px;
    min-width: 200px;
    background: #0d0d11;
    border-right: 1px solid #1e1e28;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.prof-nav-top {
    padding: 16px 14px 12px;
    border-bottom: 1px solid #1e1e28;
}
.prof-nombre {
    font-size: 13px;
    font-weight: 700;
    color: #e8e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.prof-esp {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    color: #7878a0;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.prof-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
    flex-shrink: 0;
}
.prof-nav-items { padding: 8px 0; flex: 1; }
.pni {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; font-size: 12px; font-weight: 500;
    color: #7878a0; cursor: pointer; transition: all .12s;
    text-decoration: none; position: relative;
}
.pni:hover { background: #111115; color: #e8e8f0; }
.pni.active {
    background: linear-gradient(90deg, rgba(0,229,160,.12) 0%, transparent 100%);
    border-left: 2px solid #00e5a0;
    color: #00e5a0;
    padding-left: 12px;
}
.pni svg { width: 16px; height: 16px; flex-shrink: 0; }
.pni-badge {
    margin-left: auto;
    background: #ff3f5b;
    color: #fff;
    font-family: 'IBM Plex Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 8px;
    min-width: 16px;
    text-align: center;
}

/* KPIs rápidos en el nav */
.prof-kpis {
    padding: 10px 14px;
    border-top: 1px solid #1e1e28;
}
.kpi-item {
    display: flex; justify-content: space-between;
    padding: 3px 0;
    font-size: 11px;
}
.kpi-lbl { color: #3a3a55; }
.kpi-val { font-family: 'IBM Plex Mono', monospace; font-weight: 700; color: #00e5a0; }

/* ══════════════════════════════════════════════════════
   CONTENIDO PRINCIPAL
══════════════════════════════════════════════════════ */
.prof-main {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* ── PANEL CENTRAL ─────────────────────────────────── */
.prof-center {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}
.prof-topbar {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px;
    background: #111115;
    border-bottom: 1px solid #1e1e28;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.prof-titulo {
    font-family: 'IBM Plex Mono', monospace;
    font-weight: 700; font-size: 13px;
    color: #e8e8f0; letter-spacing: 0.5px;
}
.prof-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

/* ── PANEL DERECHO (detalle paciente) ──────────────── */
.prof-detalle {
    width: 320px;
    min-width: 320px;
    background: #111115;
    border-left: 1px solid #1e1e28;
    display: flex;
    flex-direction: column;
    transition: all .2s;
}
.prof-detalle.cerrado { display: none; }
.det-head {
    padding: 12px 14px;
    border-bottom: 1px solid #1e1e28;
    display: flex; align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.det-head-titulo {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    color: #7878a0;
}
.det-body { flex: 1; overflow-y: auto; }
.det-foot { padding: 12px 14px; border-top: 1px solid #1e1e28; flex-shrink: 0; }

/* ══════════════════════════════════════════════════════
   AGENDA HOY
══════════════════════════════════════════════════════ */
.timeline-wrap { display: flex; flex-direction: column; gap: 4px; }
.cita-row {
    display: flex; align-items: stretch; gap: 8px;
    padding: 10px 12px; border-radius: 10px;
    background: #111115; border: 1px solid #1e1e28;
    cursor: pointer; transition: all .15s;
}
.cita-row:hover { border-color: #2a2a3a; background: #141418; }
.cita-row.activa { border-color: rgba(0,229,160,.35); background: rgba(0,229,160,.04); }
.hora-col {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px; font-weight: 700;
    color: #7878a0; width: 46px;
    flex-shrink: 0; padding-top: 2px;
    text-align: right;
}
.hora-col.en_curso { color: #00c4ff; }
.barra-estado {
    width: 3px; border-radius: 3px; flex-shrink: 0;
    align-self: stretch; min-height: 40px;
}
.cita-info { flex: 1; min-width: 0; }
.ci-nombre { font-size: 13px; font-weight: 600; color: #e8e8f0; margin-bottom: 2px; }
.ci-rut    { font-size: 10px; color: #7878a0; font-family: 'IBM Plex Mono', monospace; }
.ci-srv    { font-size: 11px; color: #7878a0; margin-top: 2px; }
.ci-notas  { font-size: 10px; color: #4a4a60; margin-top: 3px; font-style: italic; }
.estado-badge {
    font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    padding: 2px 7px; border-radius: 4px;
    align-self: flex-start; flex-shrink: 0;
}
.sb-pendiente   { background:rgba(245,197,24,.12);  color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.sb-confirmada  { background:rgba(0,229,160,.1);    color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.sb-en_curso    { background:rgba(0,196,255,.12);   color:#00c4ff; border:1px solid rgba(0,196,255,.2); }
.sb-completada  { background:rgba(136,136,160,.1);  color:#8888a0; border:1px solid rgba(136,136,160,.2); }
.sb-cancelada   { background:rgba(255,63,91,.08);   color:#ff3f5b; border:1px solid rgba(255,63,91,.15); opacity:.6; }

/* ══════════════════════════════════════════════════════
   LISTA PACIENTES
══════════════════════════════════════════════════════ */
.pac-buscador { margin-bottom: 14px; }
.pac-search {
    width: 100%;
    background: #18181e; border: 1.5px solid #2a2a3a;
    border-radius: 9px; color: #e8e8f0;
    font-family: 'IBM Plex Sans', sans-serif; font-size: 13px;
    padding: 9px 12px; outline: none;
    transition: border-color .15s;
}
.pac-search:focus { border-color: #00e5a0; }

.pac-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 10px;
    background: #111115; border: 1px solid #1e1e28;
    cursor: pointer; transition: all .15s; margin-bottom: 6px;
}
.pac-row:hover { border-color: #2a2a3a; }
.pac-row.sel   { border-color: rgba(0,229,160,.35); background: rgba(0,229,160,.04); }
.pac-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}
.pac-nombre  { font-size: 13px; font-weight: 600; color: #e8e8f0; }
.pac-meta    { font-size: 11px; color: #7878a0; margin-top: 1px; }
.pac-chips   { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; }
.chip {
    font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    padding: 1px 6px; border-radius: 4px;
}
.chip-ok    { background:rgba(0,229,160,.1);   color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.chip-warn  { background:rgba(245,197,24,.1);  color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.chip-err   { background:rgba(255,63,91,.08);  color:#ff3f5b; border:1px solid rgba(255,63,91,.15); }
.chip-info  { background:rgba(0,196,255,.08);  color:#00c4ff; border:1px solid rgba(0,196,255,.15); }
.chip-muted { background:rgba(136,136,160,.1); color:#8888a0; border:1px solid rgba(136,136,160,.2); }

/* ══════════════════════════════════════════════════════
   TIMELINE HISTORIAL
══════════════════════════════════════════════════════ */
.timeline-hist { position: relative; padding-left: 20px; }
.timeline-hist::before {
    content: ''; position: absolute;
    left: 7px; top: 0; bottom: 0;
    width: 2px; background: #1e1e28;
}
.th-item {
    position: relative; margin-bottom: 14px;
}
.th-dot {
    position: absolute; left: -17px; top: 3px;
    width: 10px; height: 10px; border-radius: 50%;
    border: 2px solid #0d0d11;
}
.th-fecha {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px; color: #3a3a55; margin-bottom: 4px;
}
.th-card {
    background: #111115; border: 1px solid #1e1e28;
    border-radius: 8px; padding: 10px 12px;
    font-size: 12px;
}
.th-tipo {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; margin-bottom: 5px;
}
.th-texto { color: #e8e8f0; line-height: 1.5; }
.th-notas-privadas {
    margin-top: 6px; padding: 6px 9px;
    background: rgba(255,63,91,.05);
    border: 1px solid rgba(255,63,91,.15);
    border-radius: 6px; font-size: 11px; color: #8888a0;
    font-style: italic;
}
.th-privado-lbl {
    font-size: 9px; color: #ff3f5b; font-family: 'IBM Plex Mono', monospace;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px;
}

/* ══════════════════════════════════════════════════════
   SEGUIMIENTO
══════════════════════════════════════════════════════ */
.seg-item {
    background: #111115; border: 1px solid #1e1e28;
    border-radius: 10px; padding: 12px 14px;
    margin-bottom: 8px; cursor: pointer;
    transition: all .15s;
}
.seg-item:hover { border-color: #2a2a3a; }
.seg-item.urgente { border-color: rgba(255,63,91,.3); }
.seg-item.pronto  { border-color: rgba(245,197,24,.25); }
.seg-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
.seg-pac  { font-size: 13px; font-weight: 600; color: #e8e8f0; }
.seg-fecha { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #7878a0; }
.seg-txt  { font-size: 12px; color: #8888a0; line-height: 1.4; }

/* ══════════════════════════════════════════════════════
   FORMULARIO NOTA / SEGUIMIENTO
══════════════════════════════════════════════════════ */
.nota-form {
    background: #0d0d11; border: 1px solid #1e1e28;
    border-radius: 10px; padding: 14px;
}
.nf-label {
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: #3a3a55; margin-bottom: 5px; display: block;
}
.nf-row { display: flex; gap: 8px; margin-bottom: 10px; }
.nf-select {
    flex: 1; background: #18181e; border: 1.5px solid #2a2a3a;
    border-radius: 8px; color: #e8e8f0;
    font-family: 'IBM Plex Sans', sans-serif; font-size: 12px;
    padding: 7px 10px; outline: none;
}
.nf-input {
    background: #18181e; border: 1.5px solid #2a2a3a;
    border-radius: 8px; color: #e8e8f0;
    font-family: 'IBM Plex Mono', monospace; font-size: 12px;
    padding: 7px 10px; outline: none; width: 100%;
}
.nf-textarea {
    width: 100%; min-height: 70px; resize: none;
    background: #18181e; border: 1.5px solid #2a2a3a;
    border-radius: 8px; color: #e8e8f0;
    font-family: 'IBM Plex Sans', sans-serif; font-size: 12px;
    padding: 8px 10px; outline: none;
    transition: border-color .15s;
}
.nf-textarea:focus { border-color: #00e5a0; }
.nf-privado-txt {
    font-family: 'IBM Plex Mono', monospace; font-size: 10px;
    color: rgba(255,63,91,.6);
}
.btn-agregar {
    background: #00e5a0; color: #000; border: none;
    border-radius: 8px; padding: 8px 18px;
    font-size: 12px; font-weight: 700; cursor: pointer;
    transition: background .15s;
}
.btn-agregar:hover { background: #00b87c; }
.btn-sm-ghost {
    background: #18181e; border: 1px solid #2a2a3a;
    border-radius: 7px; color: #7878a0; font-size: 11px;
    font-weight: 600; padding: 5px 10px; cursor: pointer;
    transition: all .12s;
}
.btn-sm-ghost:hover { border-color: #00e5a0; color: #00e5a0; }
.btn-sm-danger {
    background: rgba(255,63,91,.08); border: 1px solid rgba(255,63,91,.2);
    border-radius: 7px; color: #ff3f5b; font-size: 11px;
    font-weight: 600; padding: 5px 10px; cursor: pointer;
}

/* ══════════════════════════════════════════════════════
   PERFIL PROFESIONAL
══════════════════════════════════════════════════════ */
.dias-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.dia-toggle {
    text-align: center; padding: 8px 4px; border-radius: 8px;
    border: 1px solid #2a2a3a; cursor: pointer; font-size: 11px;
    font-weight: 600; transition: all .12s;
}
.dia-toggle.on {
    background: rgba(0,229,160,.1); border-color: rgba(0,229,160,.35); color: #00e5a0;
}
.srv-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; background: #0d0d11; border: 1px solid #1e1e28;
    border-radius: 8px; margin-bottom: 6px;
}
.srv-nombre { font-size: 12px; font-weight: 600; color: #e8e8f0; }
.srv-meta   { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #7878a0; }

/* ══════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════ */
@media(max-width: 1024px) {
    .prof-detalle { display: none; }
    .prof-detalle.mob-open { display: flex; position: fixed; inset: 0; z-index: 200; width: 100vw; }
}
@media(max-width: 768px) {
    .prof-nav { display: none; }
    .prof-nav.mob-open { display: flex; position: fixed; inset: 0; z-index: 200; width: 240px; }
}
</style>

<div class="prof-shell">

{{-- ══ SIDEBAR NAVEGACIÓN INTERNA ══ --}}
<nav class="prof-nav" id="profNav">
    <div class="prof-nav-top">
        @if($recurso)
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <span class="prof-dot" style="background:{{ $recurso->color ?? '#00e5a0' }};"></span>
            <span class="prof-nombre">{{ $usuario->nombre }}</span>
        </div>
        <div class="prof-esp">{{ $recurso->especialidad ?? ($labelOperario ?? 'Profesional') }}</div>
        @else
        <div class="prof-nombre">{{ $usuario->nombre }}</div>
        <div class="prof-esp" style="color:#f5c518;">Sin recurso de agenda</div>
        @endif
    </div>

    <div class="prof-nav-items">
        <a onclick="cambiarTab('agenda')" id="nav-agenda" class="pni active">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Agenda Hoy
        </a>
        <a onclick="cambiarTab('pacientes')" id="nav-pacientes" class="pni">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ $labelCliente ?? 'Pacientes' }}
        </a>
        <a onclick="cambiarTab('seguimiento')" id="nav-seguimiento" class="pni">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Seguimiento
            <span class="pni-badge" id="badgeSeguimiento" style="display:none;">0</span>
        </a>
        <a onclick="cambiarTab('perfil')" id="nav-perfil" class="pni">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            Mi Configuración
        </a>
    </div>

    {{-- KPIs rápidos --}}
    <div class="prof-kpis" id="kpisNav">
        <div style="font-family:'IBM Plex Mono',monospace;font-size:9px;font-weight:700;
                    letter-spacing:1.5px;text-transform:uppercase;color:#3a3a55;margin-bottom:8px;">
            HOY
        </div>
        <div class="kpi-item">
            <span class="kpi-lbl">Citas</span>
            <span class="kpi-val" id="kpiCitasHoy">—</span>
        </div>
        <div class="kpi-item">
            <span class="kpi-lbl">Esta semana</span>
            <span class="kpi-val" id="kpiCitasSem">—</span>
        </div>
        <div class="kpi-item">
            <span class="kpi-lbl">{{ $labelCliente ?? 'Pacientes' }}</span>
            <span class="kpi-val" id="kpiPacientes">—</span>
        </div>
    </div>
</nav>

{{-- ══ CONTENIDO PRINCIPAL ══ --}}
<div class="prof-main">

    {{-- Centro --}}
    <div class="prof-center">

        {{-- ── TAB AGENDA HOY ─────────────────────────────────── --}}
        <div id="tab-agenda" class="tab-panel">
            <div class="prof-topbar">
                <span class="prof-titulo" id="agendaTituloFecha">Cargando...</span>
                <div style="display:flex;gap:6px;margin-left:auto;">
                    <button onclick="cambiarFechaHoy(-1)" class="btn-sm-ghost">‹</button>
                    <button onclick="goToHoy()" class="btn-sm-ghost">Hoy</button>
                    <button onclick="cambiarFechaHoy(1)" class="btn-sm-ghost">›</button>
                    <button onclick="abrirModalNuevaCita()" class="btn-agregar" style="margin-left:4px;">
                        + Nueva cita
                    </button>
                </div>
            </div>
            <div class="prof-content">
                <div class="timeline-wrap" id="timelineCitas">
                    <div style="text-align:center;padding:40px;color:#3a3a55;font-size:12px;">
                        Cargando agenda...
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB PACIENTES ──────────────────────────────────── --}}
        <div id="tab-pacientes" class="tab-panel" style="display:none;">
            <div class="prof-topbar">
                <span class="prof-titulo">{{ $labelCliente ?? 'Pacientes' }}</span>
                <div style="margin-left:auto;display:flex;gap:6px;">
                    <button onclick="filtrarSeguimientoPendiente()" class="btn-sm-ghost" id="btnFiltroSeg">
                        🚨 Con seguimiento
                    </button>
                </div>
            </div>
            <div class="prof-content">
                <div class="pac-buscador">
                    <input type="text" class="pac-search" id="pacSearch"
                           placeholder="Buscar por nombre o RUT..."
                           oninput="buscarPacientes(this.value)">
                </div>
                <div id="listaPacientes">
                    <div style="text-align:center;padding:40px;color:#3a3a55;font-size:12px;">
                        Cargando {{ strtolower($labelCliente ?? 'pacientes') }}...
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB SEGUIMIENTO ────────────────────────────────── --}}
        <div id="tab-seguimiento" class="tab-panel" style="display:none;">
            <div class="prof-topbar">
                <span class="prof-titulo">Seguimiento Pendiente</span>
            </div>
            <div class="prof-content">
                <div id="listaSeguimiento">
                    <div style="text-align:center;padding:40px;color:#3a3a55;font-size:12px;">
                        Cargando seguimientos...
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB PERFIL ──────────────────────────────────────── --}}
        <div id="tab-perfil" class="tab-panel" style="display:none;">
            <div class="prof-topbar">
                <span class="prof-titulo">Mi Configuración</span>
            </div>
            <div class="prof-content">

                {{-- Horarios --}}
                <div style="margin-bottom:24px;">
                    <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:700;
                                letter-spacing:1.5px;text-transform:uppercase;color:#3a3a55;margin-bottom:12px;">
                        Horario de Atención
                    </div>
                    <div id="perfilHorarios" style="max-width:500px;">
                        <div style="text-align:center;padding:20px;color:#3a3a55;">Cargando...</div>
                    </div>
                    <button onclick="guardarHorariosPerfilFn()"
                        style="margin-top:12px;background:#00e5a0;color:#000;border:none;
                               border-radius:8px;padding:9px 20px;font-size:12px;font-weight:700;cursor:pointer;">
                        Guardar Horario
                    </button>
                </div>

                <div style="height:1px;background:#1e1e28;margin-bottom:24px;"></div>

                {{-- Servicios --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:700;
                                    letter-spacing:1.5px;text-transform:uppercase;color:#3a3a55;">
                            Servicios que Ofrezco
                        </div>
                        <button onclick="toggleFormServicio()" class="btn-sm-ghost">+ Agregar</button>
                    </div>
                    <div id="formServicio" style="display:none;background:#0d0d11;border:1px solid #1e1e28;
                                                  border-radius:10px;padding:14px;margin-bottom:12px;max-width:400px;">
                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:8px;margin-bottom:10px;">
                            <div>
                                <div class="nf-label">Nombre</div>
                                <input type="text" id="srvNombre" class="nf-input" placeholder="Ej: Consulta General">
                            </div>
                            <div>
                                <div class="nf-label">Duración (min)</div>
                                <input type="number" id="srvDur" class="nf-input" value="30" min="5" step="5">
                            </div>
                            <div>
                                <div class="nf-label">Precio ($)</div>
                                <input type="number" id="srvPrecio" class="nf-input" value="0" min="0">
                            </div>
                        </div>
                        <button onclick="guardarServicioPerfil()" class="btn-agregar">Crear Servicio</button>
                    </div>
                    <div id="listaServiciosPerfil" style="max-width:500px;">
                        <div style="text-align:center;padding:20px;color:#3a3a55;">Cargando...</div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- ══ PANEL DERECHO: DETALLE PACIENTE ══ --}}
    <div class="prof-detalle cerrado" id="profDetalle">
        <div class="det-head">
            <span class="det-head-titulo" id="detTitulo">Paciente</span>
            <div style="display:flex;gap:6px;">
                <button onclick="irCitasDesdePaciente()" class="btn-sm-ghost" id="btnVerAgenda"
                        style="display:none;font-size:10px;">📅 Ver en agenda</button>
                <button onclick="cerrarDetalle()" class="btn-sm-ghost">✕</button>
            </div>
        </div>
        <div class="det-body">

            {{-- Ficha --}}
            <div style="padding:14px 14px 0;" id="fichaSection">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <div id="detAvatar" class="pac-avatar" style="width:44px;height:44px;font-size:17px;"></div>
                    <div>
                        <div id="detNombre" style="font-size:14px;font-weight:700;color:#e8e8f0;"></div>
                        <div id="detRut" style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#7878a0;"></div>
                        <div id="detTelefono" style="font-size:11px;color:#7878a0;margin-top:2px;"></div>
                    </div>
                </div>
                {{-- Stats rápidos --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:14px;" id="detStats">
                </div>
            </div>

            {{-- Tabs internos del detalle --}}
            <div style="display:flex;border-bottom:1px solid #1e1e28;padding:0 14px;">
                <button onclick="irDetTab('historial')" id="dt-historial"
                    style="padding:7px 10px;font-size:11px;font-weight:600;color:#00e5a0;
                           border:none;background:transparent;cursor:pointer;
                           border-bottom:2px solid #00e5a0;">
                    Historial
                </button>
                <button onclick="irDetTab('nueva-nota')" id="dt-nueva-nota"
                    style="padding:7px 10px;font-size:11px;font-weight:600;color:#7878a0;
                           border:none;background:transparent;cursor:pointer;
                           border-bottom:2px solid transparent;">
                    + Nota / Seguimiento
                </button>
            </div>

            {{-- Historial timeline --}}
            <div id="dtp-historial" style="padding:12px 14px;">
                <div style="text-align:center;padding:24px;color:#3a3a55;font-size:11px;">
                    Selecciona un {{ strtolower($labelCliente ?? 'paciente') }}
                </div>
            </div>

            {{-- Formulario nueva nota --}}
            <div id="dtp-nueva-nota" style="display:none;padding:12px 14px;">
                <div class="nota-form">
                    <div class="nf-row">
                        <div style="flex:1;">
                            <div class="nf-label">Tipo</div>
                            <select class="nf-select" id="notaTipo">
                                <option value="nota_clinica">📝 Nota clínica</option>
                                <option value="indicacion">💊 Indicación</option>
                                <option value="examen">🔬 Examen solicitado</option>
                                <option value="derivacion">↗️ Derivación</option>
                                <option value="alerta">🚨 Alerta</option>
                                <option value="proxima_accion">📌 Próxima acción</option>
                                <option value="llamada">📞 Llamada</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <div class="nf-label">Seguimiento para</div>
                            <input type="date" id="notaFechaSeg" class="nf-input">
                        </div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <div class="nf-label">Contenido</div>
                        <textarea class="nf-textarea" id="notaContenido"
                                  placeholder="Escribe la nota..."></textarea>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" id="notaPrivado" checked
                                   style="accent-color:#ff3f5b;width:14px;height:14px;">
                            <span class="nf-privado-txt">🔒 Solo visible para mí</span>
                        </label>
                        <button onclick="guardarNotaPaciente()" class="btn-agregar">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>

        </div>
        {{-- Footer con próxima cita --}}
        <div class="det-foot" id="detFoot" style="display:none;">
            <div style="font-family:'IBM Plex Mono',monospace;font-size:9px;
                        color:#3a3a55;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
                Próxima cita
            </div>
            <div id="detProximaCita" style="font-size:12px;color:#7878a0;"></div>
        </div>
    </div>

</div>
</div>

{{-- ══ MODAL NUEVA CITA (desde agenda) ══ --}}
<div id="modalCita" style="display:none;position:fixed;inset:0;z-index:300;
     background:rgba(0,0,0,.75);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#111115;border:1px solid #2a2a3a;border-radius:14px;
                width:min(440px,95vw);max-height:90vh;overflow-y:auto;padding:24px;">
        <div style="font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:14px;
                    margin-bottom:18px;">📅 Nueva cita</div>
        <div style="margin-bottom:12px;">
            <div class="nf-label">Buscar {{ strtolower($labelCliente ?? 'paciente') }} (RUT / nombre)</div>
            <input type="text" id="mcBusca" class="nf-input" placeholder="RUT o nombre..."
                   oninput="buscarPacienteModal(this.value)">
            <div id="mcResultados" style="margin-top:6px;"></div>
        </div>
        <input type="hidden" id="mcClienteId">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
            <div>
                <div class="nf-label">Nombre *</div>
                <input type="text" id="mcNombre" class="nf-input" placeholder="Nombre completo">
            </div>
            <div>
                <div class="nf-label">Teléfono</div>
                <input type="tel" id="mcTelefono" class="nf-input" placeholder="+56 9...">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
            <div>
                <div class="nf-label">Fecha</div>
                <input type="date" id="mcFecha" class="nf-input">
            </div>
            <div>
                <div class="nf-label">Hora inicio</div>
                <input type="time" id="mcHora" class="nf-input">
            </div>
        </div>
        <div style="margin-bottom:16px;">
            <div class="nf-label">Servicio</div>
            <select id="mcServicio" class="nf-select" style="width:100%;"></select>
        </div>
        <div style="margin-bottom:16px;">
            <div class="nf-label">Motivo / notas</div>
            <textarea id="mcMotivo" class="nf-textarea" placeholder="Motivo de consulta..." style="min-height:50px;"></textarea>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="cerrarModalCita()"
                style="flex:1;background:#18181e;border:1.5px solid #2a2a3a;color:#7878a0;
                       padding:10px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button onclick="crearCitaModal()"
                style="flex:2;background:#00e5a0;color:#000;border:none;
                       padding:10px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">
                Crear cita →
            </button>
        </div>
    </div>
</div>

<script>
const RECURSO_ID    = {{ $recurso?->id ?? 'null' }};
const RECURSO_COLOR = '{{ $recurso?->color ?? "#00e5a0" }}';
const LABEL_CLIENTE = '{{ $labelCliente ?? "Paciente" }}';

let fechaAgenda = new Date();
let pacienteActualId = null;
let tabActual = 'agenda';
let filtroSegPendiente = false;

// ══════════════════════════════════════════════════════
// NAVEGACIÓN DE TABS
// ══════════════════════════════════════════════════════
function cambiarTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.pni').forEach(n => n.classList.remove('active'));
    document.getElementById(`tab-${tab}`).style.display = 'flex';
    document.getElementById(`tab-${tab}`).style.flexDirection = 'column';
    document.getElementById(`nav-${tab}`).classList.add('active');
    tabActual = tab;

    if (tab === 'agenda')      cargarAgendaHoy();
    if (tab === 'pacientes')   cargarPacientes();
    if (tab === 'seguimiento') cargarSeguimientosPendientes();
    if (tab === 'perfil')      cargarPerfil();
}

// ══════════════════════════════════════════════════════
// KPIs
// ══════════════════════════════════════════════════════
async function cargarKpis() {
    try {
        const data = await api('GET', '/api/profesional/estadisticas');
        document.getElementById('kpiCitasHoy').textContent = data.citas_hoy ?? '0';
        document.getElementById('kpiCitasSem').textContent = data.citas_semana ?? '0';
        document.getElementById('kpiPacientes').textContent = data.pacientes_totales ?? '0';

        const badge = document.getElementById('badgeSeguimiento');
        if (data.seguimientos_pendientes > 0) {
            badge.textContent = data.seguimientos_pendientes;
            badge.style.display = 'inline';
        }
    } catch(e) { console.error(e); }
}

// ══════════════════════════════════════════════════════
// AGENDA HOY
// ══════════════════════════════════════════════════════
function goToHoy()            { fechaAgenda = new Date(); cargarAgendaHoy(); }
function cambiarFechaHoy(d)   { fechaAgenda.setDate(fechaAgenda.getDate() + d); cargarAgendaHoy(); }

function fmtFecha(d) {
    return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long' });
}
function toISO(d) { return d.toISOString().split('T')[0]; }

async function cargarAgendaHoy() {
    document.getElementById('agendaTituloFecha').textContent = fmtFecha(fechaAgenda);
    const el = document.getElementById('timelineCitas');
    el.innerHTML = '<div style="text-align:center;padding:32px;color:#3a3a55;font-size:12px;">Cargando...</div>';
    if (!RECURSO_ID) {
        el.innerHTML = '<div style="text-align:center;padding:32px;color:#f5c518;font-size:12px;">Sin recurso de agenda configurado.</div>';
        return;
    }
    try {
        const data = await api('GET', `/api/agenda/mi/dia?fecha=${toISO(fechaAgenda)}`);
        const citas = Array.isArray(data) ? data : (data.citas ?? []);

        if (!citas.length) {
            el.innerHTML = `<div style="text-align:center;padding:40px;color:#3a3a55;font-size:13px;">
                Sin citas para este día.<br>
                <button onclick="abrirModalNuevaCita()" style="margin-top:12px;background:#00e5a0;
                color:#000;border:none;border-radius:8px;padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer;">
                + Nueva cita</button></div>`;
            return;
        }
        el.innerHTML = citas.map(c => renderCitaRow(c)).join('');
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;padding:20px;font-size:12px;">${e.message}</div>`;
    }
}

function renderCitaRow(c) {
    const colores = {
        pendiente:'#f5c518', confirmada:'#00e5a0',
        en_curso:'#00c4ff', completada:'#3a3a55', cancelada:'#ff3f5b'
    };
    const color = colores[c.estado] ?? '#3a3a55';
    return `
    <div class="cita-row ${c.estado === 'en_curso' ? 'activa' : ''}"
         onclick="abrirCita(${JSON.stringify(c).replace(/"/g, '&quot;')})">
        <div class="hora-col ${c.estado === 'en_curso' ? 'en_curso' : ''}">
            ${c.hora_inicio}
        </div>
        <div class="barra-estado" style="background:${color};opacity:.7;"></div>
        <div class="cita-info">
            <div class="ci-nombre">${c.paciente_nombre}</div>
            ${c.paciente_rut ? `<div class="ci-rut">${c.paciente_rut}</div>` : ''}
            ${c.servicio ? `<div class="ci-srv">${c.servicio.nombre}</div>` : ''}
            ${c.notas_publicas ? `<div class="ci-notas">${c.notas_publicas}</div>` : ''}
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
            <span class="estado-badge sb-${c.estado}">${c.estado.replace('_',' ')}</span>
            <span style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#3a3a55;">
                ${c.hora_inicio}–${c.hora_fin}
            </span>
        </div>
    </div>`;
}

function abrirCita(cita) {
    // Si el paciente tiene cliente_id, abrirlo en el panel
    if (cita.cliente_id) {
        abrirDetallePaciente(cita.cliente_id, cita.paciente_nombre);
    } else {
        // Mostrar panel con acciones de la cita directamente
        mostrarPanelCita(cita);
    }
}

function mostrarPanelCita(cita) {
    document.getElementById('detTitulo').textContent = cita.paciente_nombre;
    document.getElementById('detNombre').textContent = cita.paciente_nombre;
    document.getElementById('detRut').textContent = cita.paciente_rut ?? '—';
    document.getElementById('detTelefono').textContent = cita.paciente_telefono ?? '';

    const color = ['#00e5a0','#7c6af7','#00c4ff','#ff6b35','#f5c518'][
        cita.paciente_nombre.charCodeAt(0) % 5
    ];
    document.getElementById('detAvatar').style.background = `${color}20`;
    document.getElementById('detAvatar').style.color = color;
    document.getElementById('detAvatar').style.border = `1px solid ${color}40`;
    document.getElementById('detAvatar').textContent = cita.paciente_nombre[0].toUpperCase();

    // Stats vacíos (sin cliente_id no tenemos historial)
    document.getElementById('detStats').innerHTML = `
        <div style="background:#111115;border:1px solid #1e1e28;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:700;color:#00e5a0;">
                ${cita.hora_inicio}
            </div>
            <div style="font-size:9px;color:#3a3a55;text-transform:uppercase;letter-spacing:.5px;">Hora</div>
        </div>
        <div style="background:#111115;border:1px solid #1e1e28;border-radius:8px;padding:8px;text-align:center;">
            <span class="estado-badge sb-${cita.estado}" style="margin:0;">${cita.estado.replace('_',' ')}</span>
        </div>`;

    // Acciones rápidas según estado
    const acciones = {
        pendiente:  [['✅ Confirmar','confirmada'],['✗ Cancelar','cancelada']],
        confirmada: [['▶ Iniciar','en_curso'],['✗ Cancelar','cancelada']],
        en_curso:   [['✔ Completar','completada'],['No asistió','no_asistio']],
    };
    const bts = acciones[cita.estado] ?? [];
    const btnsHtml = bts.map(([lbl, est]) =>
        `<button onclick="cambiarEstadoCitaProf(${cita.id},'${est}')" class="btn-sm-ghost" style="flex:1;">${lbl}</button>`
    ).join('');

    document.getElementById('dtp-historial').innerHTML = `
        <div style="background:#0d0d11;border:1px solid #1e1e28;border-radius:8px;padding:12px;margin-bottom:12px;">
            <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#3a3a55;margin-bottom:6px;text-transform:uppercase;">
                Esta cita
            </div>
            <div style="font-size:12px;color:#7878a0;">
                ${cita.servicio ? cita.servicio.nombre + '<br>' : ''}
                ${cita.hora_inicio} – ${cita.hora_fin}
                ${cita.notas_publicas ? '<br><em style="color:#4a4a60;">' + cita.notas_publicas + '</em>' : ''}
            </div>
        </div>
        ${btnsHtml ? `<div style="display:flex;gap:6px;margin-bottom:12px;">${btnsHtml}</div>` : ''}
        <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#3a3a55;
                    text-align:center;padding:20px;">
            Sin historial previo (paciente sin cuenta)
        </div>`;

    document.getElementById('profDetalle').classList.remove('cerrado');
    document.getElementById('btnVerAgenda').style.display = 'none';
}

async function cambiarEstadoCitaProf(id, estado) {
    try {
        await api('PUT', `/api/agenda/mi/citas/${id}/estado`, { estado });
        window.toast && toast(`Estado actualizado: ${estado}`, 'ok', 1500);
        cargarAgendaHoy();
    } catch(e) { alert(e.message); }
}

// ══════════════════════════════════════════════════════
// PACIENTES
// ══════════════════════════════════════════════════════
let todoPacientes = [];

async function cargarPacientes(q = '') {
    const el = document.getElementById('listaPacientes');
    try {
        let url = '/api/profesional/pacientes';
        if (q) url += '?q=' + encodeURIComponent(q);
        if (filtroSegPendiente) url += (q ? '&' : '?') + 'pendiente_seguimiento=1';

        const data = await api('GET', url);
        todoPacientes = data;
        renderPacientes(data);
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;">${e.message}</div>`;
    }
}

function renderPacientes(lista) {
    const el = document.getElementById('listaPacientes');
    if (!lista.length) {
        el.innerHTML = `<div style="text-align:center;padding:40px;color:#3a3a55;font-size:12px;">
            Sin ${LABEL_CLIENTE.toLowerCase()}s registrados aún</div>`;
        return;
    }
    const palette = ['#00e5a0','#7c6af7','#00c4ff','#ff6b35','#3dd9eb','#f5c518','#e040fb','#ff3f5b'];

    el.innerHTML = lista.map((p, i) => {
        const color = palette[p.nombre.charCodeAt(0) % palette.length];
        const ult   = p.ultima_cita;
        const chips = [];

        if (p.citas_completadas > 0)   chips.push(`<span class="chip chip-ok">${p.citas_completadas} visitas</span>`);
        if (p.seguimiento_pendiente > 0) chips.push(`<span class="chip chip-err">🚨 ${p.seguimiento_pendiente} seguim.</span>`);
        if (ult) chips.push(`<span class="chip chip-muted">Última: ${ult.fecha}</span>`);

        return `
        <div class="pac-row ${pacienteActualId === p.id ? 'sel' : ''}"
             onclick="abrirDetallePaciente(${p.id}, '${p.nombre.replace(/'/g,'\\\'')}}')">
            <div class="pac-avatar" style="background:${color}20;color:${color};border:1px solid ${color}40;">
                ${p.nombre[0].toUpperCase()}
            </div>
            <div style="flex:1;min-width:0;">
                <div class="pac-nombre">${p.nombre}</div>
                <div class="pac-meta">${p.rut ? p.rut + ' · ' : ''}${p.telefono ?? ''}</div>
                ${chips.length ? `<div class="pac-chips">${chips.join('')}</div>` : ''}
            </div>
            <svg style="color:#3a3a55;flex-shrink:0;" width="14" height="14" fill="none" stroke="currentColor"
                 stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </div>`;
    }).join('');
}

function buscarPacientes(q) {
    clearTimeout(window._busqTimer);
    window._busqTimer = setTimeout(() => cargarPacientes(q), 300);
}

function filtrarSeguimientoPendiente() {
    filtroSegPendiente = !filtroSegPendiente;
    const btn = document.getElementById('btnFiltroSeg');
    btn.style.borderColor = filtroSegPendiente ? '#ff3f5b' : '#2a2a3a';
    btn.style.color       = filtroSegPendiente ? '#ff3f5b' : '#7878a0';
    cargarPacientes(document.getElementById('pacSearch').value);
}

async function abrirDetallePaciente(id, nombre) {
    pacienteActualId = id;
    document.getElementById('detTitulo').textContent = nombre;

    const palette = ['#00e5a0','#7c6af7','#00c4ff','#ff6b35','#3dd9eb','#f5c518','#e040fb'];
    const color = palette[nombre.charCodeAt(0) % palette.length];
    document.getElementById('detAvatar').style.background = `${color}20`;
    document.getElementById('detAvatar').style.color = color;
    document.getElementById('detAvatar').style.border = `1px solid ${color}40`;
    document.getElementById('detAvatar').textContent = nombre[0].toUpperCase();
    document.getElementById('btnVerAgenda').style.display = 'inline-block';

    document.getElementById('profDetalle').classList.remove('cerrado');
    irDetTab('historial');

    try {
        const [ficha, historial] = await Promise.all([
            api('GET', `/api/profesional/pacientes/${id}`),
            api('GET', `/api/profesional/pacientes/${id}/historial`),
        ]);

        const p = ficha.paciente;
        document.getElementById('detNombre').textContent   = p.nombre;
        document.getElementById('detRut').textContent      = p.rut ?? '—';
        document.getElementById('detTelefono').textContent = p.telefono ?? '';

        const rc = ficha.resumen_citas;
        document.getElementById('detStats').innerHTML = `
            <div style="background:#111115;border:1px solid #1e1e28;border-radius:8px;padding:8px;text-align:center;">
                <div style="font-family:'IBM Plex Mono',monospace;font-size:18px;font-weight:700;color:#00e5a0;">${rc?.total ?? 0}</div>
                <div style="font-size:9px;color:#3a3a55;text-transform:uppercase;letter-spacing:.5px;">Total citas</div>
            </div>
            <div style="background:#111115;border:1px solid #1e1e28;border-radius:8px;padding:8px;text-align:center;">
                <div style="font-family:'IBM Plex Mono',monospace;font-size:18px;font-weight:700;color:#7878a0;">${rc?.completadas ?? 0}</div>
                <div style="font-size:9px;color:#3a3a55;text-transform:uppercase;letter-spacing:.5px;">Completas</div>
            </div>
            <div style="background:#111115;border:1px solid #1e1e28;border-radius:8px;padding:8px;text-align:center;">
                <div style="font-family:'IBM Plex Mono',monospace;font-size:18px;font-weight:700;color:#ff3f5b;">${rc?.no_asistio ?? 0}</div>
                <div style="font-size:9px;color:#3a3a55;text-transform:uppercase;letter-spacing:.5px;">No asistió</div>
            </div>`;

        renderHistorial(historial);

        // Actualizar lista de pacientes visualmente
        if (tabActual === 'pacientes') renderPacientes(todoPacientes);

    } catch(e) {
        document.getElementById('dtp-historial').innerHTML = `<div style="color:#ff3f5b;padding:16px;">${e.message}</div>`;
    }
}

function renderHistorial(items) {
    const el = document.getElementById('dtp-historial');
    if (!items.length) {
        el.innerHTML = `<div style="text-align:center;padding:32px;color:#3a3a55;font-size:12px;">Sin historial registrado</div>`;
        return;
    }

    const colores = {
        cita:          { dot:'#00e5a0', etiqueta:'CITA',          cls:'chip-ok' },
        nota_clinica:  { dot:'#7c6af7', etiqueta:'NOTA',          cls:'chip-info' },
        indicacion:    { dot:'#f5c518', etiqueta:'INDICACIÓN',     cls:'chip-warn' },
        derivacion:    { dot:'#00c4ff', etiqueta:'DERIVACIÓN',     cls:'chip-info' },
        examen:        { dot:'#e040fb', etiqueta:'EXAMEN',         cls:'chip-info' },
        alerta:        { dot:'#ff3f5b', etiqueta:'ALERTA',         cls:'chip-err' },
        proxima_accion:{ dot:'#ff6b35', etiqueta:'PRÓX. ACCIÓN',   cls:'chip-warn' },
        llamada:       { dot:'#8888a0', etiqueta:'LLAMADA',        cls:'chip-muted' },
    };

    el.innerHTML = `<div class="timeline-hist">` + items.map(item => {
        const t = item.tipo === 'cita' ? 'cita' : item.tipo;
        const c = colores[t] ?? { dot:'#3a3a55', etiqueta: t.toUpperCase(), cls:'chip-muted' };

        const body = item.origen === 'seguimiento'
            ? `<div class="th-tipo chip ${c.cls}" style="display:inline-block;margin-bottom:6px;">${c.etiqueta}</div>
               <div class="th-texto">${item.contenido}</div>
               ${item.fecha_seguimiento && !item.resuelto
                   ? `<div style="margin-top:6px;font-size:10px;color:#f5c518;">📌 Seguimiento: ${item.fecha_seguimiento}</div>` : ''}
               ${item.resuelto ? `<div style="margin-top:4px;font-size:10px;color:#8888a0;">✓ Resuelto</div>` : ''}`
            : `<div class="th-tipo chip ${c.cls}" style="display:inline-block;margin-bottom:6px;">${c.etiqueta}</div>
               <div class="th-texto">
                   ${item.hora_inicio} – ${item.hora_fin}
                   ${item.servicio ? ' · ' + item.servicio : ''}
                   · <span class="estado-badge sb-${item.estado}" style="font-size:9px;">${item.estado}</span>
               </div>
               ${item.notas_publicas ? `<div class="ci-notas" style="margin-top:4px;">${item.notas_publicas}</div>` : ''}
               ${item.notas_internas ? `<div class="th-notas-privadas"><div class="th-privado-lbl">🔒 Nota privada</div>${item.notas_internas}</div>` : ''}`;

        return `
        <div class="th-item">
            <div class="th-dot" style="background:${c.dot};border-color:#0d0d11;"></div>
            <div class="th-fecha">${item.fecha}</div>
            <div class="th-card">${body}</div>
        </div>`;
    }).join('') + '</div>';
}

function irCitasDesdePaciente() {
    cambiarTab('agenda');
}

function irDetTab(tab) {
    ['historial','nueva-nota'].forEach(t => {
        document.getElementById(`dtp-${t}`).style.display = t === tab ? 'block' : 'none';
        const btn = document.getElementById(`dt-${t}`);
        btn.style.color = t === tab ? '#00e5a0' : '#7878a0';
        btn.style.borderBottomColor = t === tab ? '#00e5a0' : 'transparent';
    });
}

async function guardarNotaPaciente() {
    if (!pacienteActualId) return;
    const cont = document.getElementById('notaContenido').value.trim();
    if (!cont) { alert('Escribe el contenido de la nota'); return; }

    try {
        await api('POST', `/api/profesional/pacientes/${pacienteActualId}/nota`, {
            tipo:              document.getElementById('notaTipo').value,
            contenido:         cont,
            fecha_seguimiento: document.getElementById('notaFechaSeg').value || null,
            privado:           document.getElementById('notaPrivado').checked,
        });
        document.getElementById('notaContenido').value = '';
        document.getElementById('notaFechaSeg').value = '';
        window.toast && toast('Nota guardada', 'ok', 1500);
        // Recargar historial
        const hist = await api('GET', `/api/profesional/pacientes/${pacienteActualId}/historial`);
        renderHistorial(hist);
        cargarKpis(); // actualizar badge seguimiento
        irDetTab('historial');
    } catch(e) { alert(e.message); }
}

function cerrarDetalle() {
    document.getElementById('profDetalle').classList.add('cerrado');
    pacienteActualId = null;
}

// ══════════════════════════════════════════════════════
// SEGUIMIENTO PENDIENTE
// ══════════════════════════════════════════════════════
async function cargarSeguimientosPendientes() {
    const el = document.getElementById('listaSeguimiento');
    try {
        // Traer todos los pacientes con seguimiento pendiente
        const pacs = await api('GET', '/api/profesional/pacientes?pendiente_seguimiento=1');

        if (!pacs.length) {
            el.innerHTML = `<div style="text-align:center;padding:40px;color:#3a3a55;font-size:13px;">
                Sin seguimientos pendientes 🎉</div>`;
            return;
        }

        // Para cada paciente con seguimiento, traer los items
        const items = await Promise.all(pacs.map(p =>
            api('GET', `/api/profesional/pacientes/${p.id}/seguimiento`)
                .then(segs => segs.filter(s => !s.resuelto && s.fecha_seguimiento)
                    .map(s => ({ ...s, paciente_nombre: p.nombre, paciente_id: p.id }))
                )
        ));

        const flat = items.flat()
            .sort((a, b) => new Date(a.fecha_seguimiento) - new Date(b.fecha_seguimiento));

        const hoy = toISO(new Date());
        const colTipo = {
            alerta:'#ff3f5b', proxima_accion:'#ff6b35',
            derivacion:'#00c4ff', examen:'#e040fb',
            indicacion:'#f5c518', llamada:'#8888a0', nota_clinica:'#7878a0'
        };

        el.innerHTML = flat.map(s => {
            const urgente = s.fecha_seguimiento <= hoy;
            const color   = colTipo[s.tipo] ?? '#8888a0';
            return `
            <div class="seg-item ${urgente ? 'urgente' : ''}"
                 onclick="abrirDetallePaciente(${s.paciente_id}, '${s.paciente_nombre.replace(/'/g,'\\\'')})">
                <div class="seg-head">
                    <div>
                        <div class="seg-pac">${s.paciente_nombre}</div>
                        <span class="chip" style="background:${color}15;color:${color};border:1px solid ${color}30;margin-top:3px;display:inline-block;">
                            ${s.tipo.replace('_',' ')}
                        </span>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                        <span class="seg-fecha ${urgente ? '' : ''}"
                              style="color:${urgente ? '#ff3f5b' : '#f5c518'};">
                            📌 ${s.fecha_seguimiento}
                        </span>
                        <button onclick="event.stopPropagation(); resolverSeguimiento(${s.id})"
                            class="btn-sm-ghost" style="font-size:10px;">✓ Resolver</button>
                    </div>
                </div>
                <div class="seg-txt">${s.contenido.substring(0, 120)}${s.contenido.length > 120 ? '...' : ''}</div>
            </div>`;
        }).join('');
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;">${e.message}</div>`;
    }
}

async function resolverSeguimiento(id) {
    try {
        await api('PUT', `/api/profesional/seguimiento/${id}`, { resuelto: true });
        window.toast && toast('Seguimiento resuelto', 'ok', 1500);
        cargarSeguimientosPendientes();
        cargarKpis();
    } catch(e) { alert(e.message); }
}

// ══════════════════════════════════════════════════════
// PERFIL
// ══════════════════════════════════════════════════════
const DIAS = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

async function cargarPerfil() {
    try {
        const r = await api('GET', '/api/agenda/mi/recurso');
        renderHorariosPerfil(r.horarios ?? []);
        renderServiciosPerfil(r.servicios ?? []);
    } catch(e) {
        document.getElementById('perfilHorarios').innerHTML =
            `<div style="color:#ff3f5b;">${e.message}</div>`;
    }
}

function renderHorariosPerfil(hs) {
    const map = {};
    hs.forEach(h => map[h.dia_semana] = h);
    document.getElementById('perfilHorarios').innerHTML = DIAS.map((d, i) => {
        const dNum = i + 1;
        const h = map[dNum] || { hora_inicio:'09:00', hora_fin:'18:00', activo:0, duracion_slot_min:30 };
        return `
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;
                    background:#111115;border:1px solid #1e1e28;border-radius:8px;margin-bottom:5px;">
            <input type="checkbox" id="ph-${dNum}" ${h.activo ? 'checked' : ''}
                   style="width:15px;height:15px;accent-color:#00e5a0;cursor:pointer;flex-shrink:0;">
            <span style="font-size:12px;font-weight:500;color:#e8e8f0;min-width:70px;">${d}</span>
            <input type="time" id="pih-${dNum}" value="${h.hora_inicio}"
                   style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                          padding:4px 8px;color:#e8e8f0;font-size:11px;font-family:'IBM Plex Mono',monospace;">
            <span style="color:#3a3a55;font-size:11px;">–</span>
            <input type="time" id="pfh-${dNum}" value="${h.hora_fin}"
                   style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                          padding:4px 8px;color:#e8e8f0;font-size:11px;font-family:'IBM Plex Mono',monospace;">
            <div style="display:flex;align-items:center;gap:4px;margin-left:auto;">
                <input type="number" id="psh-${dNum}" value="${h.duracion_slot_min}" min="5" max="120" step="5"
                       style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                              padding:4px 6px;color:#e8e8f0;font-size:11px;width:44px;text-align:center;">
                <span style="font-size:10px;color:#3a3a55;">min</span>
            </div>
        </div>`;
    }).join('');
}

async function guardarHorariosPerfilFn() {
    const horarios = [];
    for (let d = 1; d <= 7; d++) {
        horarios.push({
            dia_semana:       d,
            activo:           document.getElementById(`ph-${d}`).checked ? 1 : 0,
            hora_inicio:      document.getElementById(`pih-${d}`).value,
            hora_fin:         document.getElementById(`pfh-${d}`).value,
            duracion_slot_min: parseInt(document.getElementById(`psh-${d}`).value) || 30,
        });
    }
    try {
        await api('PUT', '/api/agenda/mi/horarios', { horarios });
        window.toast && toast('Horarios guardados', 'ok', 1500);
    } catch(e) { alert(e.message); }
}

function renderServiciosPerfil(srvs) {
    document.getElementById('listaServiciosPerfil').innerHTML = srvs.length
        ? srvs.map(s => `
            <div class="srv-item">
                <div>
                    <div class="srv-nombre">${s.nombre}</div>
                    <div class="srv-meta">${s.duracion_min} min · $${s.precio?.toLocaleString('es-CL')}</div>
                </div>
                <button onclick="eliminarServicio(${s.id})" class="btn-sm-danger">✕</button>
            </div>`).join('')
        : `<div style="text-align:center;padding:20px;color:#3a3a55;font-size:12px;">Sin servicios configurados</div>`;
}

function toggleFormServicio() {
    const f = document.getElementById('formServicio');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function guardarServicioPerfil() {
    if (!RECURSO_ID) { alert('Sin recurso de agenda'); return; }
    const data = {
        nombre:       document.getElementById('srvNombre').value,
        duracion_min: parseInt(document.getElementById('srvDur').value),
        precio:       parseInt(document.getElementById('srvPrecio').value) || 0,
    };
    if (!data.nombre) { alert('El nombre es obligatorio'); return; }
    try {
        await api('POST', `/api/agenda/recursos/${RECURSO_ID}/servicios`, data);
        window.toast && toast('Servicio creado', 'ok', 1500);
        document.getElementById('srvNombre').value = '';
        document.getElementById('formServicio').style.display = 'none';
        cargarPerfil();
    } catch(e) { alert(e.message); }
}

async function eliminarServicio(id) {
    if (!confirm('¿Eliminar este servicio?')) return;
    try {
        await api('DELETE', `/api/agenda/servicios/${id}`);
        cargarPerfil();
    } catch(e) { alert(e.message); }
}

// ══════════════════════════════════════════════════════
// MODAL NUEVA CITA
// ══════════════════════════════════════════════════════
async function abrirModalNuevaCita() {
    const m = document.getElementById('modalCita');
    m.style.display = 'flex';
    document.getElementById('mcFecha').value = toISO(fechaAgenda);

    // Cargar servicios del recurso
    const sel = document.getElementById('mcServicio');
    sel.innerHTML = '<option value="">Sin servicio específico</option>';
    if (RECURSO_ID) {
        try {
            const r = await api('GET', '/api/agenda/mi/recurso');
            (r.servicios || []).forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = `${s.nombre} (${s.duracion_min}min)`;
                sel.appendChild(opt);
            });
        } catch {}
    }
}

function cerrarModalCita() {
    document.getElementById('modalCita').style.display = 'none';
}

async function buscarPacienteModal(q) {
    const el = document.getElementById('mcResultados');
    if (!q || q.length < 2) { el.innerHTML = ''; return; }
    try {
        const data = await api('GET', `/api/clientes?q=${encodeURIComponent(q)}&limit=5`);
        const lista = data.data ?? data;
        el.innerHTML = lista.slice(0,5).map(p => `
            <div onclick="seleccionarPacienteModal(${p.id}, '${p.nombre.replace(/'/g,'\\\'')}}', '${p.telefono ?? ''}')"
                style="padding:8px 10px;background:#18181e;border:1px solid #2a2a3a;border-radius:7px;
                       cursor:pointer;margin-bottom:4px;font-size:12px;color:#e8e8f0;">
                ${p.nombre} ${p.rut ? '· <span style="color:#7878a0;font-family:monospace;">' + p.rut + '</span>' : ''}
            </div>`).join('') || '<div style="font-size:11px;color:#3a3a55;">Sin resultados</div>';
    } catch {}
}

function seleccionarPacienteModal(id, nombre, tel) {
    document.getElementById('mcClienteId').value  = id;
    document.getElementById('mcNombre').value     = nombre;
    document.getElementById('mcTelefono').value   = tel;
    document.getElementById('mcResultados').innerHTML = '';
    document.getElementById('mcBusca').value      = '';
}

async function crearCitaModal() {
    const nombre = document.getElementById('mcNombre').value.trim();
    const fecha  = document.getElementById('mcFecha').value;
    const hora   = document.getElementById('mcHora').value;
    if (!nombre || !fecha || !hora) {
        alert('Nombre, fecha y hora son obligatorios');
        return;
    }

    // Calcular hora_fin desde duración del servicio (30 min default)
    const [h, m] = hora.split(':').map(Number);
    const end = new Date(0, 0, 0, h, m + 30);
    const horaFin = `${String(end.getHours()).padStart(2,'0')}:${String(end.getMinutes()).padStart(2,'0')}`;

    const payload = {
        agenda_recurso_id:  RECURSO_ID,
        agenda_servicio_id: parseInt(document.getElementById('mcServicio').value) || null,
        fecha,
        hora_inicio: hora,
        hora_fin:    horaFin,
        paciente_nombre:   nombre,
        paciente_telefono: document.getElementById('mcTelefono').value,
        notas_publicas:    document.getElementById('mcMotivo').value || null,
        estado:            'confirmada',
        cliente_id:        parseInt(document.getElementById('mcClienteId').value) || null,
    };

    try {
        await api('POST', '/api/agenda/citas', payload);
        cerrarModalCita();
        cargarAgendaHoy();
        window.toast && toast('Cita creada', 'ok', 1500);
        ['mcNombre','mcTelefono','mcMotivo','mcClienteId'].forEach(id => {
            document.getElementById(id).value = '';
        });
    } catch(e) { alert(e.message); }
}

// ══════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════
cargarKpis();
cambiarTab('agenda');
</script>
@endsection
```

---

## PARTE 6 — LAYOUT: redirigir operario al menú correcto

En `resources/views/tenant/layout.blade.php`, modificar el ítem del sidebar para el operario.
Actualmente enlaza a `/operario`. Cambiar a una lógica condicional:

```blade
{{-- En la sección de nav del operario --}}
@php
    $urlOperario = '/operario';
    if(in_array('M08', $modulosActivos ?? [])) {
        $tieneRecurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())
                         ->where('activo', true)->exists();
        if ($tieneRecurso) $urlOperario = '/profesional';
    }
@endphp

<a href="{{ $urlOperario }}" class="nav-link-item {{ request()->is('profesional*') || request()->is('operario*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        @if(isset($tieneRecurso) && $tieneRecurso)
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        @else
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        @endif
    </svg>
    {{ isset($tieneRecurso) && $tieneRecurso ? 'Mi Agenda' : 'Stock & Ventas' }}
</a>
```

---

## PARTE 7 — AGREGAR RUTA EN ROUTES/TENANT.PHP

```php
use App\Http\Controllers\Tenant\ProfesionalController;
use App\Http\Controllers\Tenant\WebPanelController;

// Vista profesional
Route::get('/profesional', [WebPanelController::class, 'profesional'])->name('profesional');

// API profesional
Route::prefix('api/profesional')->middleware('check.module:M08')->group(function () {
    Route::get('/pacientes',                    [ProfesionalController::class, 'pacientes']);
    Route::get('/pacientes/{id}',               [ProfesionalController::class, 'paciente']);
    Route::get('/pacientes/{id}/historial',     [ProfesionalController::class, 'historialPaciente']);
    Route::post('/pacientes/{id}/nota',         [ProfesionalController::class, 'agregarNota']);
    Route::get('/pacientes/{id}/seguimiento',   [ProfesionalController::class, 'seguimientoPaciente']);
    Route::post('/pacientes/{id}/seguimiento',  [ProfesionalController::class, 'crearSeguimiento']);
    Route::put('/seguimiento/{id}',             [ProfesionalController::class, 'actualizarSeguimiento']);
    Route::get('/estadisticas',                 [ProfesionalController::class, 'estadisticas']);
});
```

---

## PARTE 8 — COMANDOS DE EJECUCIÓN

```bash
# 1. Ejecutar migración
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"

# 2. Crear directorio de vistas
docker exec benderandos_app sh -c "mkdir -p /app/resources/views/tenant/profesional"

# 3. Limpiar caché
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 4. Verificar rutas nuevas
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=profesional"
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/profesional"

# 5. Probar acceso
# Como Dr. Demo (operario en demo-medico) → /operario → redirige a /profesional
# http://demo-medico.localhost:8000/operario
```

---

## RESUMEN DE ARCHIVOS

| Acción | Archivo |
|---|---|
| NUEVA MIGRACIÓN | `database/migrations/tenant/XXXX_create_seguimiento_paciente_table.php` |
| NUEVO MODELO | `app/Models/Tenant/SeguimientoPaciente.php` |
| NUEVO CONTROLADOR | `app/Http/Controllers/Tenant/ProfesionalController.php` |
| MODIFICAR | `app/Http/Controllers/Tenant/WebPanelController.php` — métodos `operario()` + `profesional()` |
| MODIFICAR | `routes/tenant.php` — rutas `/profesional` + `/api/profesional/*` |
| CREAR VISTA | `resources/views/tenant/profesional/index.blade.php` |
| MODIFICAR | `resources/views/tenant/layout.blade.php` — enlace condicional operario/profesional |
| MODIFICAR | `app/Models/Tenant/Cliente.php` — relación `agendaCitas()` |

---

## DIFERENCIAS RESPECTO A LA VISTA ANTERIOR

| Aspecto | Vista anterior (`/operario`) | Vista nueva (`/profesional`) |
|---|---|---|
| Tab principal | Vender / Stock / Mis Ventas | Agenda Hoy / Pacientes / Seguimiento / Configuración |
| Foco | Inventario y caja | Citas y pacientes |
| KPIs | Ninguno | Citas hoy · semana · total pacientes · seguimientos pendientes |
| Lista pacientes | ✗ No existe | ✅ Con última visita, historial, seguimientos pendientes |
| Historial clínico | ✗ No existe | ✅ Timeline citas + notas privadas |
| Notas internas | ✗ No existe | ✅ Por paciente, cifradas, solo visibles para el profesional |
| Seguimiento | ✗ No existe | ✅ Alertas, derivaciones, próximas acciones con fecha |
| Cambio de estado cita | ✗ No existe | ✅ Confirmar / Iniciar / Completar / Cancelar desde la vista |
| Horarios propios | ✗ No existe | ✅ Editar disponibilidad por día |
| Servicios ofrecidos | ✗ No existe | ✅ CRUD de servicios propios |
| Redirección automática | ✗ Siempre stock/ventas | ✅ Si tiene recurso M08 → vista profesional |
| Aplica a rubros | Solo bodega/almacén | Médico · Dentista · Psicólogo · Abogado · Taller · Spa · Padel |

---

*BenderAnd ERP · SPEC Vista Profesional Operario · 2026-03-26 · Antigravity*
*Aplica a: demo-medico · demo-padel · demo-legal · cualquier tenant con M08 + operarios vinculados*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade*
