# M08 AGENDA — PLAN DE IMPLEMENTACIÓN COMPLETO
## Para Antigravity (Google Project IDX)
*Fecha: 2026-03-26 · Stack: Laravel 11 + PostgreSQL 16 + Vanilla JS/Blade*

---

## CONTEXTO DEL PROBLEMA

La implementación anterior del M08 dejó el módulo vacío. El POS del médico
(`demo-medico`) muestra solo "Stock & Ventas" sin ninguna funcionalidad clínica.
La recepcionista (cajero) no tiene vista de agenda. El landing público no muestra
disponibilidad de profesionales.

**Rubros afectados por M08:** médico, dentista, legal, pádel, spa, veterinaria,
inmobiliaria, gimnasio. Todos con módulo M08 activo.

---

## PARTE 1 — BASE DE DATOS (MIGRACIONES TENANT)

Ejecutar en orden. Path: `database/migrations/tenant/`

### 1.1 `2026_03_26_000001_create_agenda_recursos_table.php`

```php
Schema::create('agenda_recursos', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');           // "Dr. Pérez", "Cancha 1", "Sala A"
    $table->string('tipo');             // 'profesional' | 'recurso_fisico'
    $table->string('especialidad')->nullable();  // "Cardiología", "Pádel"
    $table->string('color', 7)->default('#6366f1'); // hex para calendario
    $table->boolean('activo')->default(true);
    $table->integer('orden')->default(0);
    // FK a usuarios si es profesional que también usa el sistema
    $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### 1.2 `2026_03_26_000002_create_agenda_horarios_table.php`

```php
// Horarios semanales por recurso
Schema::create('agenda_horarios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agenda_recurso_id')->constrained('agenda_recursos')->cascadeOnDelete();
    $table->tinyInteger('dia_semana'); // 0=Dom, 1=Lun ... 6=Sab
    $table->time('hora_inicio');
    $table->time('hora_fin');
    $table->integer('duracion_slot_min')->default(30); // slots de 30 min
    $table->timestamps();
});
```

### 1.3 `2026_03_26_000003_create_agenda_bloqueos_table.php`

```php
// Vacaciones, feriados, ausencias puntuales
Schema::create('agenda_bloqueos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agenda_recurso_id')->constrained('agenda_recursos')->cascadeOnDelete();
    $table->date('fecha_inicio');
    $table->date('fecha_fin');
    $table->time('hora_inicio')->nullable(); // null = día completo
    $table->time('hora_fin')->nullable();
    $table->string('motivo')->nullable();    // "Vacaciones", "Feriado"
    $table->timestamps();
});
```

### 1.4 `2026_03_26_000004_create_agenda_servicios_table.php`

```php
// Tipos de servicio que puede ofrecer cada recurso
Schema::create('agenda_servicios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agenda_recurso_id')->constrained('agenda_recursos')->cascadeOnDelete();
    $table->string('nombre');           // "Consulta general", "Limpieza dental"
    $table->integer('duracion_min');    // duración del servicio
    $table->bigInteger('precio')->default(0);
    $table->boolean('activo')->default(true);
    // FK opcional a productos para integrar con carrito de venta
    $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
    $table->timestamps();
});
```

### 1.5 `2026_03_26_000005_create_agenda_citas_table.php`

```php
Schema::create('agenda_citas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agenda_recurso_id')->constrained('agenda_recursos');
    $table->foreignId('agenda_servicio_id')->nullable()->constrained('agenda_servicios')->nullOnDelete();
    $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();

    // Datos básicos de la cita
    $table->string('paciente_nombre');
    $table->string('paciente_rut')->nullable();
    $table->string('paciente_telefono')->nullable();
    $table->string('paciente_email')->nullable();

    $table->date('fecha');
    $table->time('hora_inicio');
    $table->time('hora_fin');

    // Estado machine
    $table->string('estado')->default('pendiente');
    // pendiente | confirmada | en_curso | completada | cancelada | no_asistio

    $table->text('notas_publicas')->nullable();   // visible al paciente
    $table->text('notas_internas')->nullable();   // solo staff

    // Venta generada al completar
    $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();

    // Origen de la cita
    $table->string('origen')->default('admin'); // admin | bot_wa | landing_publico
    $table->string('confirmada_via')->nullable(); // wa | email | telefono

    $table->timestamp('recordatorio_enviado_at')->nullable();

    $table->timestamps();

    $table->index(['fecha', 'agenda_recurso_id']);
    $table->index(['estado', 'fecha']);
});
```

### 1.6 `2026_03_26_000006_create_agenda_config_table.php`

```php
Schema::create('agenda_config', function (Blueprint $table) {
    $table->id();
    // Configuración global del módulo agenda del tenant
    $table->string('titulo_landing')->default('Agenda tu hora');
    $table->text('descripcion_landing')->nullable();
    $table->boolean('landing_publico_activo')->default(true);
    $table->boolean('confirmacion_wa_activa')->default(false);
    $table->boolean('recordatorio_activo')->default(true);
    $table->integer('recordatorio_horas_antes')->default(24);
    $table->boolean('requiere_telefono')->default(true);
    $table->boolean('requiere_email')->default(false);
    $table->string('color_primario', 7)->default('#6366f1');
    $table->string('logo_url')->nullable();
    $table->timestamps();
});
```

---

## PARTE 2 — MODELOS ELOQUENT

Path: `app/Models/Tenant/`

### 2.1 `AgendaRecurso.php`

```php
<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class AgendaRecurso extends Model
{
    protected $table = 'agenda_recursos';
    protected $fillable = ['nombre','tipo','especialidad','color','activo','orden','usuario_id'];

    public function horarios() { return $this->hasMany(AgendaHorario::class); }
    public function bloqueos() { return $this->hasMany(AgendaBloqueo::class); }
    public function servicios() { return $this->hasMany(AgendaServicio::class)->where('activo', true); }
    public function citas()     { return $this->hasMany(AgendaCita::class); }
    public function usuario()   { return $this->belongsTo(Usuario::class); }
}
```

### 2.2 `AgendaCita.php`

```php
<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class AgendaCita extends Model
{
    protected $table = 'agenda_citas';
    protected $fillable = [
        'agenda_recurso_id','agenda_servicio_id','cliente_id',
        'paciente_nombre','paciente_rut','paciente_telefono','paciente_email',
        'fecha','hora_inicio','hora_fin',
        'estado','notas_publicas','notas_internas',
        'venta_id','origen','confirmada_via','recordatorio_enviado_at'
    ];

    protected $casts = ['fecha' => 'date'];

    public function recurso()  { return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id'); }
    public function servicio() { return $this->belongsTo(AgendaServicio::class, 'agenda_servicio_id'); }
    public function cliente()  { return $this->belongsTo(Cliente::class); }
    public function venta()    { return $this->belongsTo(Venta::class); }

    public function scopeDelDia($q, $fecha = null) {
        return $q->whereDate('fecha', $fecha ?? today());
    }
    public function scopeActivas($q) {
        return $q->whereIn('estado', ['pendiente','confirmada','en_curso']);
    }
}
```

### 2.3 `AgendaHorario.php`, `AgendaBloqueo.php`, `AgendaServicio.php`, `AgendaConfig.php`

Modelos simples con `$fillable` de todos los campos de cada tabla y `belongsTo(AgendaRecurso::class)` según corresponda.

---

## PARTE 3 — SERVICE PRINCIPAL

Path: `app/Services/AgendaService.php`

```php
<?php
namespace App\Services;

use App\Models\Tenant\{AgendaRecurso, AgendaCita, AgendaServicio};
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AgendaService
{
    /**
     * Retorna slots disponibles para un recurso en una fecha dada.
     * Un slot es un intervalo de duracion_slot_min minutos dentro del horario laboral
     * que no está ocupado por ninguna cita confirmada/pendiente.
     */
    public function getSlotsDisponibles(AgendaRecurso $recurso, Carbon $fecha, ?int $duracionMin = null): array
    {
        $diaSemana = $fecha->dayOfWeek; // 0=Dom ... 6=Sab

        $horario = $recurso->horarios()
            ->where('dia_semana', $diaSemana)
            ->first();

        if (!$horario) return [];

        $duracion = $duracionMin ?? $horario->duracion_slot_min;

        // Verificar bloqueos del día
        $bloqueado = $recurso->bloqueos()
            ->where('fecha_inicio', '<=', $fecha->toDateString())
            ->where('fecha_fin', '>=', $fecha->toDateString())
            ->whereNull('hora_inicio') // bloqueo de día completo
            ->exists();

        if ($bloqueado) return [];

        // Citas ya ocupadas
        $citasDelDia = $recurso->citas()
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['pendiente','confirmada','en_curso'])
            ->get(['hora_inicio','hora_fin']);

        $ocupados = $citasDelDia->map(fn($c) => [
            'inicio' => Carbon::parse($c->hora_inicio),
            'fin'    => Carbon::parse($c->hora_fin),
        ]);

        // Generar slots
        $slots = [];
        $cursor = Carbon::parse($horario->hora_inicio);
        $fin    = Carbon::parse($horario->hora_fin);

        while ($cursor->copy()->addMinutes($duracion)->lte($fin)) {
            $slotFin = $cursor->copy()->addMinutes($duracion);

            $libre = $ocupados->every(fn($o) =>
                $slotFin->lte($o['inicio']) || $cursor->gte($o['fin'])
            );

            if ($libre) {
                $slots[] = [
                    'hora_inicio' => $cursor->format('H:i'),
                    'hora_fin'    => $slotFin->format('H:i'),
                    'disponible'  => true,
                ];
            }

            $cursor->addMinutes($duracion);
        }

        return $slots;
    }

    /**
     * Retorna la agenda completa de un día para todos los recursos activos.
     * Incluye citas y slots libres intercalados.
     */
    public function getAgendaDia(Carbon $fecha, ?int $recursoId = null): array
    {
        $query = AgendaRecurso::where('activo', true)->orderBy('orden');
        if ($recursoId) $query->where('id', $recursoId);

        $recursos = $query->with([
            'citas' => fn($q) => $q->whereDate('fecha', $fecha)->with(['cliente','servicio']),
            'horarios',
            'servicios',
        ])->get();

        return $recursos->map(function ($r) use ($fecha) {
            return [
                'recurso' => [
                    'id'          => $r->id,
                    'nombre'      => $r->nombre,
                    'especialidad'=> $r->especialidad,
                    'color'       => $r->color,
                ],
                'tiene_horario' => $r->horarios->contains('dia_semana', $fecha->dayOfWeek),
                'citas' => $r->citas->sortBy('hora_inicio')->values()->map(fn($c) => [
                    'id'              => $c->id,
                    'hora_inicio'     => $c->hora_inicio,
                    'hora_fin'        => $c->hora_fin,
                    'paciente_nombre' => $c->paciente_nombre,
                    'paciente_rut'    => $c->paciente_rut,
                    'estado'          => $c->estado,
                    'servicio'        => $c->servicio?->nombre,
                    'notas_publicas'  => $c->notas_publicas,
                    'cliente_id'      => $c->cliente_id,
                ])->all(),
                'slots_libres' => $this->getSlotsDisponibles($r, $fecha),
            ];
        })->all();
    }

    /**
     * Crea una cita validando conflictos.
     */
    public function crearCita(array $data): AgendaCita
    {
        // Verificar que el slot está libre
        $conflicto = AgendaCita::where('agenda_recurso_id', $data['agenda_recurso_id'])
            ->whereDate('fecha', $data['fecha'])
            ->whereIn('estado', ['pendiente','confirmada','en_curso'])
            ->where(fn($q) =>
                $q->whereBetween('hora_inicio', [$data['hora_inicio'], $data['hora_fin']])
                  ->orWhereBetween('hora_fin', [$data['hora_inicio'], $data['hora_fin']])
            )->exists();

        if ($conflicto) {
            throw new \Exception('El horario seleccionado ya está ocupado.');
        }

        return AgendaCita::create($data);
    }

    /**
     * Cambia estado de una cita. Solo transiciones válidas.
     */
    public function cambiarEstado(AgendaCita $cita, string $nuevoEstado): AgendaCita
    {
        $transiciones = [
            'pendiente'   => ['confirmada','cancelada','no_asistio'],
            'confirmada'  => ['en_curso','cancelada','no_asistio'],
            'en_curso'    => ['completada'],
            'completada'  => [],
            'cancelada'   => ['pendiente'], // re-agendar
            'no_asistio'  => [],
        ];

        if (!in_array($nuevoEstado, $transiciones[$cita->estado] ?? [])) {
            throw new \Exception("Transición inválida: {$cita->estado} → {$nuevoEstado}");
        }

        $cita->update(['estado' => $nuevoEstado]);
        return $cita;
    }

    /**
     * Próximos slots disponibles en los siguientes N días (para sugerir próxima cita).
     */
    public function proximosSlotsDisponibles(AgendaRecurso $recurso, int $diasAdelante = 14): array
    {
        $sugerencias = [];
        $fecha = today()->addDay();

        while (count($sugerencias) < 5 && $diasAdelante-- > 0) {
            $slots = $this->getSlotsDisponibles($recurso, $fecha);
            if (!empty($slots)) {
                $sugerencias[] = [
                    'fecha'      => $fecha->toDateString(),
                    'fecha_fmt'  => $fecha->isoFormat('dddd D [de] MMMM'),
                    'primer_slot'=> $slots[0],
                    'total_slots'=> count($slots),
                ];
            }
            $fecha->addDay();
        }

        return $sugerencias;
    }
}
```

---

## PARTE 4 — CONTROLADOR

Path: `app/Http/Controllers/Tenant/AgendaController.php`

```php
<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\{AgendaRecurso, AgendaCita, AgendaConfig};
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AgendaController extends Controller
{
    public function __construct(private AgendaService $agendaService) {}

    // ── VISTAS BLADE ──────────────────────────────────────────────

    /** GET /pos/agenda — Vista POS (cajero ve todos los recursos, médico ve solo el suyo) */
    public function posIndex()
    {
        $usuario = auth()->user();
        $recurso = AgendaRecurso::where('usuario_id', $usuario->id)->first();
        $recursos = AgendaRecurso::where('activo', true)->orderBy('orden')->get();
        return view('pos.agenda', compact('recursos', 'recurso'));
    }

    /** GET /admin/agenda — Vista admin */
    public function adminIndex()
    {
        $recursos = AgendaRecurso::with('horarios','servicios')->where('activo', true)->get();
        return view('admin.agenda.index', compact('recursos'));
    }

    /** GET /agenda/{slug} — Landing público */
    public function landing(string $slug)
    {
        $config = AgendaConfig::first();
        $recursos = AgendaRecurso::where('activo', true)
            ->with('servicios','horarios')
            ->orderBy('orden')->get();
        return view('public.agenda', compact('config', 'recursos', 'slug'));
    }

    // ── API ────────────────────────────────────────────────────────

    /** GET /api/agenda/dia?fecha=2026-03-26&recurso_id= */
    public function getDia(Request $r)
    {
        $fecha     = Carbon::parse($r->input('fecha', today()));
        $recursoId = $r->input('recurso_id');
        $data      = $this->agendaService->getAgendaDia($fecha, $recursoId);
        return response()->json($data);
    }

    /** GET /api/agenda/slots?recurso_id=1&fecha=2026-03-26&duracion=30 */
    public function getSlots(Request $r)
    {
        $recurso = AgendaRecurso::findOrFail($r->recurso_id);
        $fecha   = Carbon::parse($r->fecha);
        $slots   = $this->agendaService->getSlotsDisponibles($recurso, $fecha, $r->duracion);
        return response()->json($slots);
    }

    /** POST /api/agenda/citas — Crear cita */
    public function crearCita(Request $r)
    {
        $r->validate([
            'agenda_recurso_id' => 'required|exists:agenda_recursos,id',
            'fecha'             => 'required|date|after_or_equal:today',
            'hora_inicio'       => 'required|date_format:H:i',
            'hora_fin'          => 'required|date_format:H:i|after:hora_inicio',
            'paciente_nombre'   => 'required|string|max:255',
            'paciente_rut'      => 'nullable|string',
            'paciente_telefono' => 'nullable|string',
        ]);

        try {
            $cita = $this->agendaService->crearCita(array_merge(
                $r->only(['agenda_recurso_id','agenda_servicio_id','cliente_id',
                          'paciente_nombre','paciente_rut','paciente_telefono',
                          'paciente_email','fecha','hora_inicio','hora_fin',
                          'notas_publicas','notas_internas']),
                ['origen' => $r->input('origen', 'admin')]
            ));

            // Dispatch recordatorio si corresponde
            // dispatch(new RecordatorioCitaJob($cita))->delay(...);

            return response()->json($cita->load('recurso','servicio'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** PUT /api/agenda/citas/{id}/estado — Cambiar estado */
    public function cambiarEstado(Request $r, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $r->validate(['estado' => 'required|string']);
        try {
            $cita = $this->agendaService->cambiarEstado($cita, $r->estado);
            return response()->json($cita);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** PUT /api/agenda/citas/{id} — Editar notas internas / externas */
    public function actualizarCita(Request $r, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $cita->update($r->only(['notas_internas','notas_publicas','venta_id']));
        return response()->json($cita);
    }

    /** DELETE /api/agenda/citas/{id} — Cancelar */
    public function cancelarCita(int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $this->agendaService->cambiarEstado($cita, 'cancelada');
        return response()->json(['ok' => true]);
    }

    /** GET /api/agenda/sugerencia?recurso_id= — Próximos slots disponibles */
    public function sugerencia(Request $r)
    {
        $recurso = AgendaRecurso::findOrFail($r->recurso_id);
        return response()->json($this->agendaService->proximosSlotsDisponibles($recurso));
    }

    /** POST /api/agenda/citas/{id}/iniciar-consulta — Pasar a en_curso + sugerir siguiente */
    public function iniciarConsulta(int $id)
    {
        $cita = AgendaCita::with('recurso')->findOrFail($id);
        $this->agendaService->cambiarEstado($cita, 'en_curso');
        $sugerencias = $this->agendaService->proximosSlotsDisponibles($cita->recurso);
        return response()->json(['cita' => $cita, 'proximos_slots' => $sugerencias]);
    }

    /** POST /api/agenda/citas/{id}/completar — Completar + generar venta */
    public function completarCita(Request $r, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $this->agendaService->cambiarEstado($cita, 'completada');
        // Si vienen servicios, crear venta (ver lógica VentaController)
        // Retornar datos para que POS abra modal de cobro con cliente pre-cargado
        return response()->json([
            'cita'            => $cita->load('cliente','servicio'),
            'sugerir_cobro'   => true,
            'cliente_id'      => $cita->cliente_id,
            'paciente_nombre' => $cita->paciente_nombre,
            'paciente_rut'    => $cita->paciente_rut,
            'monto_sugerido'  => $cita->servicio?->precio ?? 0,
        ]);
    }

    // ── RECURSOS CRUD (admin) ─────────────────────────────────────

    public function getRecursos()
    {
        return response()->json(
            AgendaRecurso::with('horarios','servicios')->where('activo',true)->orderBy('orden')->get()
        );
    }

    public function crearRecurso(Request $r)
    {
        $r->validate([
            'nombre'    => 'required|string|max:100',
            'tipo'      => 'required|in:profesional,recurso_fisico',
            'color'     => 'nullable|string|size:7',
        ]);
        $recurso = AgendaRecurso::create($r->only(['nombre','tipo','especialidad','color','orden','usuario_id']));
        return response()->json($recurso, 201);
    }

    public function actualizarHorarios(Request $r, int $recursoId)
    {
        $recurso = AgendaRecurso::findOrFail($recursoId);
        $recurso->horarios()->delete();
        foreach ($r->horarios as $h) {
            $recurso->horarios()->create($h);
        }
        return response()->json(['ok' => true]);
    }

    // ── LANDING PÚBLICO (sin auth) ────────────────────────────────

    /** GET /api/public/agenda/slots?recurso_id=&fecha=&duracion= */
    public function publicSlots(Request $r)
    {
        $recurso = AgendaRecurso::findOrFail($r->recurso_id);
        $fecha   = Carbon::parse($r->fecha);
        $slots   = $this->agendaService->getSlotsDisponibles($recurso, $fecha, $r->duracion);
        return response()->json($slots);
    }

    /** POST /api/public/agenda/cita — Agendar desde landing público */
    public function publicCrearCita(Request $r)
    {
        $r->validate([
            'agenda_recurso_id' => 'required|exists:agenda_recursos,id',
            'fecha'             => 'required|date|after_or_equal:today',
            'hora_inicio'       => 'required|date_format:H:i',
            'hora_fin'          => 'required|date_format:H:i',
            'paciente_nombre'   => 'required|string|max:255',
            'paciente_telefono' => 'required|string',
        ]);

        try {
            $cita = $this->agendaService->crearCita(array_merge(
                $r->only(['agenda_recurso_id','agenda_servicio_id',
                          'paciente_nombre','paciente_telefono','paciente_email',
                          'fecha','hora_inicio','hora_fin','notas_publicas']),
                ['origen' => 'landing_publico', 'estado' => 'pendiente']
            ));
            return response()->json(['ok' => true, 'cita_id' => $cita->id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

---

## PARTE 5 — RUTAS

### En `routes/tenant.php` — agregar dentro del grupo `auth:sanctum`:

```php
// ── M08 AGENDA ──────────────────────────────────────────────────
Route::middleware(['check.module:M08'])->group(function () {

    // Vistas Blade
    Route::get('/pos/agenda', [AgendaController::class, 'posIndex'])->name('pos.agenda');
    Route::get('/admin/agenda', [AgendaController::class, 'adminIndex'])->name('admin.agenda');

    // API autenticada
    Route::prefix('api/agenda')->group(function () {
        Route::get('/dia', [AgendaController::class, 'getDia']);
        Route::get('/slots', [AgendaController::class, 'getSlots']);
        Route::get('/sugerencia', [AgendaController::class, 'sugerencia']);
        Route::post('/citas', [AgendaController::class, 'crearCita']);
        Route::put('/citas/{id}', [AgendaController::class, 'actualizarCita']);
        Route::put('/citas/{id}/estado', [AgendaController::class, 'cambiarEstado']);
        Route::delete('/citas/{id}', [AgendaController::class, 'cancelarCita']);
        Route::post('/citas/{id}/iniciar-consulta', [AgendaController::class, 'iniciarConsulta']);
        Route::post('/citas/{id}/completar', [AgendaController::class, 'completarCita']);
        Route::get('/recursos', [AgendaController::class, 'getRecursos']);
        Route::post('/recursos', [AgendaController::class, 'crearRecurso']);
        Route::put('/recursos/{id}/horarios', [AgendaController::class, 'actualizarHorarios']);
    });
});

// ── LANDING PÚBLICO (sin auth, solo requiere tenant activo) ─────
Route::get('/agenda', [AgendaController::class, 'landing'])->name('agenda.landing');
Route::prefix('api/public/agenda')->group(function () {
    Route::get('/slots', [AgendaController::class, 'publicSlots']);
    Route::post('/cita', [AgendaController::class, 'publicCrearCita']);
});
```

---

## PARTE 6 — VISTA POS AGENDA (`resources/views/pos/agenda.blade.php`)

Esta es la vista **más importante**. Reemplaza completamente la lógica actual del POS para
usuarios con M08. Contiene dos layouts:

### Layout CAJERO/RECEPCIONISTA:
- Columnas por doctor con sus citas del día
- Botón "Confirmar llegada", "Cancelar", "Nueva cita"
- Al completar cita → modal de cobro con RUT pre-cargado
- Estado semáforo por columna

### Layout MÉDICO/PROFESIONAL (operario con `usuario_id` en `agenda_recursos`):
- Solo SU columna del día
- Timeline vertical con hora actual marcada
- Cita activa expandida: notas internas, diagnóstico, "Emitir receta" (link a producción futura)
- Botón "Siguiente cita" y "Recomendar hora en 7 días"

```blade
@extends('layouts.app')

@section('content')
<div id="agendaApp" class="agenda-shell">

  {{-- ── TOPBAR AGENDA ── --}}
  <div class="agenda-topbar">
    <div class="agenda-fecha-nav">
      <button onclick="cambiarFecha(-1)">‹</button>
      <span id="fechaLabel" class="mono">Hoy</span>
      <button onclick="cambiarFecha(1)">›</button>
      <button onclick="irHoy()" class="btn-hoy">Hoy</button>
    </div>

    {{-- Selector de recurso (cajero ve todos, médico ve solo el suyo) --}}
    @if($recurso)
      {{-- Modo médico: recurso fijo --}}
      <input type="hidden" id="recursoFijo" value="{{ $recurso->id }}">
      <span class="rubro-badge">{{ $recurso->nombre }}</span>
    @else
      {{-- Modo cajero: puede filtrar --}}
      <select id="filtroRecurso" onchange="loadAgenda()">
        <option value="">Todos los profesionales</option>
        @foreach($recursos as $r)
          <option value="{{ $r->id }}">{{ $r->nombre }}</option>
        @endforeach
      </select>
    @endif

    <button class="btn btn-primary btn-sm" onclick="abrirModalNuevaCita()">
      + Nueva cita
    </button>
  </div>

  {{-- ── COLUMNAS AGENDA ── --}}
  <div id="agendaColumnas" class="agenda-columnas">
    <div class="agenda-loading">Cargando agenda...</div>
  </div>

  {{-- ── PANEL LATERAL: CITA ACTIVA ── --}}
  <div id="panelCitaActiva" class="panel-cita-activa" style="display:none">
    <div class="panel-cita-header">
      <span id="panelCitaPaciente" class="panel-paciente-nombre"></span>
      <button onclick="cerrarPanelCita()">✕</button>
    </div>
    <div class="panel-cita-body">
      <div id="panelCitaInfo"></div>

      {{-- Notas internas (solo médico/profesional) --}}
      @if($recurso)
      <div class="field" style="margin-top:12px;">
        <label class="label">Notas clínicas internas 🔒</label>
        <textarea id="notasInternas" class="textarea" rows="4"
          placeholder="Diagnóstico, observaciones, anamnesis..."></textarea>
        <button class="btn btn-sm btn-secondary" style="margin-top:6px;"
          onclick="guardarNotasInternas()">Guardar notas</button>
      </div>
      @endif

      {{-- Sugerencia próxima cita --}}
      <div id="sugerenciaProxima" style="display:none; margin-top:16px;">
        <div class="label">Sugerir próxima cita</div>
        <div id="listaSugerencias"></div>
      </div>

      <div class="panel-cita-acciones">
        <button id="btnIniciarConsulta" class="btn btn-primary"
          onclick="iniciarConsulta()">▶ Iniciar consulta</button>
        <button id="btnCompletarCita" class="btn btn-success"
          onclick="completarCitaYCobrar()" style="display:none">
          ✓ Completar y cobrar
        </button>
        <button id="btnNoAsistio" class="btn btn-secondary btn-sm"
          onclick="marcarNoAsistio()">No asistió</button>
        <button id="btnCancelar" class="btn btn-danger btn-sm"
          onclick="cancelarCitaActiva()">Cancelar cita</button>
      </div>
    </div>
  </div>

</div>

{{-- Modal Nueva Cita --}}
<div class="modal-overlay" id="modalNuevaCita">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <span class="modal-title">Nueva Cita</span>
      <button class="modal-close" onclick="cerrarModalNuevaCita()">✕</button>
    </div>
    <div class="modal-body">
      <div class="field">
        <label class="label">Profesional</label>
        <select id="nuevaCitaRecurso" onchange="cargarSlotsNuevaCita()">
          @foreach($recursos as $r)
            <option value="{{ $r->id }}"
              @if($recurso && $r->id == $recurso->id) selected @endif>
              {{ $r->nombre }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label">Servicio</label>
        <select id="nuevaCitaServicio" onchange="actualizarDuracion()">
          <option value="">Seleccionar servicio</option>
        </select>
      </div>
      <div class="field">
        <label class="label">Fecha</label>
        <input type="date" id="nuevaCitaFecha"
          min="{{ today()->toDateString() }}"
          value="{{ today()->toDateString() }}"
          oninput="cargarSlotsNuevaCita()">
      </div>
      <div class="field">
        <label class="label">Horario disponible</label>
        <div id="slotsDisponibles" class="slots-grid"></div>
      </div>
      <div class="divider"></div>
      <div class="field">
        <label class="label">Nombre paciente</label>
        <input type="text" id="nuevaCitaPaciente" placeholder="Nombre completo">
      </div>
      <div class="field">
        <label class="label">RUT</label>
        <input type="text" id="nuevaCitaRut" placeholder="12.345.678-9"
          oninput="fmtRut(this)" onblur="buscarClientePorRut()">
        <span id="rutStatus" style="font-size:11px;color:var(--t2);"></span>
      </div>
      <div class="field">
        <label class="label">Teléfono</label>
        <input type="text" id="nuevaCitaTelefono" placeholder="+56 9 xxxx xxxx">
      </div>
      <div class="field">
        <label class="label">Notas para el profesional</label>
        <textarea id="nuevaCitaNotas" rows="2" placeholder="Motivo de consulta..."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="cerrarModalNuevaCita()">Cancelar</button>
      <button class="btn btn-primary" onclick="confirmarNuevaCita()">Agendar cita</button>
    </div>
  </div>
</div>

{{-- Modal cobro post-consulta --}}
<div class="modal-overlay" id="modalCobro">
  <div class="modal" style="max-width:400px;">
    <div class="modal-head">
      <span class="modal-title">💳 Cobrar consulta</span>
      <button class="modal-close" onclick="closeModal('modalCobro')">✕</button>
    </div>
    <div class="modal-body">
      <div id="cobrarResumen"></div>
      <div class="field">
        <label class="label">Método de pago</label>
        <div class="pay-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:6px;">
          <button class="pbtn on" onclick="selPay(this,'efectivo')">Efectivo</button>
          <button class="pbtn" onclick="selPay(this,'debito')">Débito</button>
          <button class="pbtn" onclick="selPay(this,'credito')">Crédito</button>
          <button class="pbtn" onclick="selPay(this,'transferencia')">Transfer</button>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-primary btn-full" onclick="procesarCobro()">
        Confirmar cobro
      </button>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
.agenda-shell {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 56px);
  overflow: hidden;
  background: var(--bg);
}
.agenda-topbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  background: var(--s1);
  border-bottom: 1px solid var(--b1);
  flex-shrink: 0;
}
.agenda-fecha-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}
.agenda-fecha-nav button {
  background: var(--s2);
  border: 1px solid var(--b2);
  color: var(--tx);
  padding: 4px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
}
.btn-hoy {
  font-size: 11px !important;
  padding: 4px 10px !important;
  font-family: var(--mono) !important;
  font-weight: 700 !important;
  color: var(--ac) !important;
  border-color: rgba(0,229,160,.3) !important;
}
.agenda-columnas {
  display: flex;
  flex: 1;
  overflow-x: auto;
  overflow-y: hidden;
  gap: 0;
}
.agenda-col {
  min-width: 220px;
  max-width: 280px;
  flex: 1;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--b1);
}
.agenda-col-header {
  padding: 10px 12px;
  background: var(--s2);
  border-bottom: 1px solid var(--b1);
  flex-shrink: 0;
}
.agenda-col-nombre {
  font-size: 13px;
  font-weight: 700;
  color: var(--tx);
}
.agenda-col-especialidad {
  font-size: 10px;
  color: var(--t2);
  font-family: var(--mono);
}
.agenda-col-body {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
/* Tarjeta de cita */
.cita-card {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--b2);
  background: var(--s2);
  cursor: pointer;
  transition: all .12s;
  border-left: 3px solid var(--ac);
}
.cita-card:hover { border-color: var(--ac); background: var(--s3); }
.cita-card.en_curso { border-left-color: var(--warn); background: rgba(245,197,24,.04); }
.cita-card.completada { opacity:.5; border-left-color: var(--t3); cursor: default; }
.cita-card.cancelada { opacity:.3; cursor: default; }
.cita-card.no_asistio { opacity:.3; cursor: default; }
.cita-hora { font-family: var(--mono); font-size: 11px; color: var(--t2); }
.cita-paciente { font-size: 13px; font-weight: 600; margin-top: 2px; }
.cita-servicio { font-size: 11px; color: var(--t2); margin-top: 2px; }
.cita-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 9px;
  font-weight: 700;
  font-family: var(--mono);
  text-transform: uppercase;
}
.badge-pendiente   { background: rgba(68,136,255,.15); color: #4488ff; }
.badge-confirmada  { background: rgba(0,229,160,.15);  color: var(--ac); }
.badge-en_curso    { background: rgba(245,197,24,.15); color: var(--warn); }
.badge-completada  { background: rgba(100,100,120,.15);color: var(--t2); }
.badge-cancelada   { background: rgba(255,63,91,.1);   color: var(--err); }
.badge-no_asistio  { background: rgba(255,100,50,.1);  color: #ff6432; }

/* Slot libre */
.slot-libre {
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px dashed var(--b2);
  font-size: 11px;
  font-family: var(--mono);
  color: var(--t3);
  cursor: pointer;
  transition: all .12s;
}
.slot-libre:hover { border-color: var(--ac); color: var(--ac); background: rgba(0,229,160,.04); }

/* Panel cita activa */
.panel-cita-activa {
  position: fixed;
  right: 0; top: 56px; bottom: 0;
  width: 340px;
  background: var(--s1);
  border-left: 1px solid var(--b2);
  display: flex;
  flex-direction: column;
  z-index: 200;
  animation: slideInRight .2s ease;
}
@keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
.panel-cita-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--b1);
  flex-shrink: 0;
}
.panel-paciente-nombre { font-size: 16px; font-weight: 700; }
.panel-cita-body { flex: 1; overflow-y: auto; padding: 16px; }
.panel-cita-acciones {
  padding: 16px;
  border-top: 1px solid var(--b1);
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}

/* Slots grid en modal */
.slots-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 6px;
}
.slot-btn {
  padding: 6px 12px;
  background: var(--s2);
  border: 1.5px solid var(--b2);
  border-radius: 7px;
  color: var(--t2);
  font-family: var(--mono);
  font-size: 12px;
  cursor: pointer;
  transition: all .1s;
}
.slot-btn:hover, .slot-btn.selected {
  background: rgba(0,229,160,.1);
  border-color: var(--ac);
  color: var(--ac);
}
.agenda-loading { padding: 60px; text-align: center; color: var(--t2); font-family: var(--mono); }
@media (max-width: 767px) {
  .agenda-col { min-width: 85vw; }
  .panel-cita-activa { width: 100%; top: auto; bottom: 0; height: 70vh; border-left: none; border-top: 1px solid var(--b2); animation: slideInUp .2s ease; }
  @keyframes slideInUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
}
</style>
@endpush

@push('scripts')
<script>
const RECURSO_FIJO = document.getElementById('recursoFijo')?.value || null;
let fechaActual = new Date();
let citaActivaId = null;
let slotSeleccionado = null;
let payMetodo = 'efectivo';
let pendingCobro = null;

function fechaStr(d) { return d.toISOString().split('T')[0]; }
function formatFecha(str) {
  const d = new Date(str + 'T12:00:00');
  return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long' });
}

// ── CARGA AGENDA ────────────────────────────────────────────────
async function loadAgenda() {
  const recursoId = RECURSO_FIJO || document.getElementById('filtroRecurso')?.value || '';
  const fecha = fechaStr(fechaActual);
  document.getElementById('fechaLabel').textContent = formatFecha(fecha);

  const container = document.getElementById('agendaColumnas');
  container.innerHTML = '<div class="agenda-loading"><span class="spinner"></span> Cargando...</div>';

  try {
    const params = new URLSearchParams({ fecha });
    if (recursoId) params.set('recurso_id', recursoId);
    const data = await api('GET', `/api/agenda/dia?${params}`);
    renderColumnas(data);
  } catch(e) {
    container.innerHTML = `<div class="agenda-loading" style="color:var(--err);">Error: ${e.message}</div>`;
  }
}

function renderColumnas(data) {
  const c = document.getElementById('agendaColumnas');
  if (!data.length) {
    c.innerHTML = '<div class="agenda-loading">Sin profesionales configurados</div>';
    return;
  }
  c.innerHTML = data.map(col => {
    const r = col.recurso;
    const citas = col.citas;
    const slots = col.slots_libres;

    // Mezclar citas y slots en timeline
    const items = [];
    citas.forEach(cita => {
      items.push({ tipo: 'cita', hora: cita.hora_inicio, data: cita });
    });
    slots.forEach(slot => {
      const ocupado = citas.some(c =>
        c.hora_inicio <= slot.hora_inicio && c.hora_fin > slot.hora_inicio
      );
      if (!ocupado) items.push({ tipo: 'slot', hora: slot.hora_inicio, data: slot });
    });
    items.sort((a,b) => a.hora.localeCompare(b.hora));

    const indicador = col.tiene_horario ? '' : '<span style="font-size:10px;color:var(--warn);">Sin horario hoy</span>';

    return `
    <div class="agenda-col">
      <div class="agenda-col-header" style="border-top:3px solid ${r.color}">
        <div class="agenda-col-nombre">${r.nombre}</div>
        <div class="agenda-col-especialidad">${r.especialidad || ''}</div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
          <span style="font-family:var(--mono);font-size:10px;color:var(--ac);">${citas.filter(c=>['pendiente','confirmada','en_curso'].includes(c.estado)).length} citas activas</span>
          ${indicador}
        </div>
      </div>
      <div class="agenda-col-body">
        ${items.map(item => {
          if (item.tipo === 'cita') {
            const c = item.data;
            return `
            <div class="cita-card ${c.estado}" onclick="abrirCita(${c.id}, ${r.id})">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span class="cita-hora">${c.hora_inicio} → ${c.hora_fin}</span>
                <span class="cita-badge badge-${c.estado}">${estadoLabel(c.estado)}</span>
              </div>
              <div class="cita-paciente">${c.paciente_nombre}</div>
              ${c.servicio ? `<div class="cita-servicio">${c.servicio}</div>` : ''}
            </div>`;
          } else {
            const s = item.data;
            return `
            <div class="slot-libre" onclick="abrirNuevaCitaEnSlot(${r.id},'${fechaStr(fechaActual)}','${s.hora_inicio}','${s.hora_fin}')">
              ${s.hora_inicio} — libre
            </div>`;
          }
        }).join('')}
        ${items.length === 0 ? '<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px;">Sin actividad</div>' : ''}
      </div>
    </div>`;
  }).join('');
}

function estadoLabel(s) {
  const m = { pendiente:'Pend.', confirmada:'Conf.', en_curso:'En curso', completada:'Lista', cancelada:'Cancel.', no_asistio:'No asistió' };
  return m[s] || s;
}

// ── CITA ACTIVA PANEL ───────────────────────────────────────────
let citaActivaData = null;
let recursoActivoId = null;

async function abrirCita(citaId, recursoId) {
  citaActivaId = citaId;
  recursoActivoId = recursoId;
  const panel = document.getElementById('panelCitaActiva');
  panel.style.display = 'flex';
  document.getElementById('panelCitaInfo').innerHTML = '<div class="spinner"></div>';

  try {
    // Obtener datos frescos de la agenda para encontrar la cita
    const fecha = fechaStr(fechaActual);
    const data = await api('GET', `/api/agenda/dia?fecha=${fecha}&recurso_id=${recursoId}`);
    const col = data[0];
    const cita = col?.citas.find(c => c.id == citaId);
    if (!cita) return;
    citaActivaData = cita;

    document.getElementById('panelCitaPaciente').textContent = cita.paciente_nombre;

    const estadosCompletados = ['completada','cancelada','no_asistio'];
    const completada = estadosCompletados.includes(cita.estado);

    document.getElementById('panelCitaInfo').innerHTML = `
      <div class="cita-badge badge-${cita.estado}" style="margin-bottom:12px;">${estadoLabel(cita.estado)}</div>
      <div style="display:grid;gap:6px;font-size:13px;">
        <div><span style="color:var(--t2)">Hora:</span> ${cita.hora_inicio} → ${cita.hora_fin}</div>
        ${cita.servicio ? `<div><span style="color:var(--t2)">Servicio:</span> ${cita.servicio}</div>` : ''}
        ${cita.paciente_rut ? `<div><span style="color:var(--t2)">RUT:</span> <span class="mono">${cita.paciente_rut}</span></div>` : ''}
        ${cita.notas_publicas ? `<div><span style="color:var(--t2)">Notas:</span> ${cita.notas_publicas}</div>` : ''}
      </div>
    `;

    // Botones según estado
    document.getElementById('btnIniciarConsulta').style.display =
      cita.estado === 'confirmada' || cita.estado === 'pendiente' ? 'block' : 'none';
    document.getElementById('btnCompletarCita').style.display =
      cita.estado === 'en_curso' ? 'block' : 'none';
    document.getElementById('btnNoAsistio').style.display =
      !completada ? 'block' : 'none';
    document.getElementById('btnCancelar').style.display =
      !completada ? 'block' : 'none';

    // Notas internas (si es el médico)
    const notasField = document.getElementById('notasInternas');
    if (notasField) notasField.value = '';

    // Sugerencia próxima cita
    document.getElementById('sugerenciaProxima').style.display = 'none';

  } catch(e) { toast('Error cargando cita', 'err'); }
}

function cerrarPanelCita() {
  document.getElementById('panelCitaActiva').style.display = 'none';
  citaActivaId = null;
}

async function iniciarConsulta() {
  if (!citaActivaId) return;
  try {
    const res = await api('POST', `/api/agenda/citas/${citaActivaId}/iniciar-consulta`);
    toast('Consulta iniciada', 'ok');

    // Mostrar sugerencias próxima cita
    if (res.proximos_slots?.length) {
      const div = document.getElementById('sugerenciaProxima');
      const lista = document.getElementById('listaSugerencias');
      lista.innerHTML = res.proximos_slots.map(s => `
        <div class="slot-btn" style="width:100%;text-align:left;margin-bottom:4px;"
          onclick="agendarSiguienteCita('${s.fecha}','${s.primer_slot.hora_inicio}','${s.primer_slot.hora_fin}')">
          📅 ${s.fecha_fmt} — ${s.primer_slot.hora_inicio}
        </div>
      `).join('');
      div.style.display = 'block';
    }

    document.getElementById('btnIniciarConsulta').style.display = 'none';
    document.getElementById('btnCompletarCita').style.display = 'block';
    loadAgenda();
  } catch(e) { toast(e.message, 'err'); }
}

async function completarCitaYCobrar() {
  if (!citaActivaId) return;

  // Guardar notas si hay
  const notasEl = document.getElementById('notasInternas');
  if (notasEl?.value) {
    await api('PUT', `/api/agenda/citas/${citaActivaId}`, { notas_internas: notasEl.value });
  }

  try {
    const res = await api('POST', `/api/agenda/citas/${citaActivaId}/completar`);
    toast('Cita completada', 'ok');
    cerrarPanelCita();
    loadAgenda();

    // Abrir modal de cobro con datos pre-cargados
    if (res.sugerir_cobro) {
      pendingCobro = res;
      document.getElementById('cobrarResumen').innerHTML = `
        <div style="padding:12px;background:var(--s2);border-radius:8px;margin-bottom:12px;">
          <div style="font-size:15px;font-weight:700;">${res.paciente_nombre}</div>
          <div class="mono" style="font-size:12px;color:var(--t2);">${res.paciente_rut || ''}</div>
          ${res.monto_sugerido ? `<div class="mono" style="font-size:22px;font-weight:700;color:var(--ac);margin-top:8px;">$${res.monto_sugerido.toLocaleString('es-CL')}</div>` : ''}
        </div>
      `;
      openModal('modalCobro');
    }
  } catch(e) { toast(e.message, 'err'); }
}

async function procesarCobro() {
  if (!pendingCobro) return;
  try {
    // Crear venta directa con el cliente de la cita
    const venta = await api('POST', '/api/ventas', {
      cliente_id: pendingCobro.cliente_id || null,
    });
    if (pendingCobro.monto_sugerido) {
      // Agregar servicio como ítem si tiene producto asociado
      // (simplificado — agregar ítem libre si no hay producto_id)
    }
    await api('POST', `/api/ventas/${venta.id}/confirmar`, {
      tipo_pago_id: { efectivo:1, debito:2, credito:3, transferencia:4 }[payMetodo] || 1,
    });
    // Vincular venta a la cita
    await api('PUT', `/api/agenda/citas/${pendingCobro.cita?.id || citaActivaId}`, {
      venta_id: venta.id
    });
    toast('✓ Cobro registrado', 'ok');
    closeModal('modalCobro');
    pendingCobro = null;
  } catch(e) { toast(e.message || 'Error en cobro', 'err'); }
}

async function marcarNoAsistio() {
  if (!citaActivaId) return;
  await api('PUT', `/api/agenda/citas/${citaActivaId}/estado`, { estado: 'no_asistio' });
  toast('Marcado como no asistió', 'warn');
  cerrarPanelCita();
  loadAgenda();
}
async function cancelarCitaActiva() {
  if (!citaActivaId) return;
  if (!confirm('¿Cancelar esta cita?')) return;
  await api('DELETE', `/api/agenda/citas/${citaActivaId}`);
  toast('Cita cancelada', 'warn');
  cerrarPanelCita();
  loadAgenda();
}
async function guardarNotasInternas() {
  if (!citaActivaId) return;
  const notas = document.getElementById('notasInternas').value;
  await api('PUT', `/api/agenda/citas/${citaActivaId}`, { notas_internas: notas });
  toast('Notas guardadas', 'ok');
}

// ── NUEVA CITA MODAL ────────────────────────────────────────────
function abrirModalNuevaCita() {
  slotSeleccionado = null;
  cargarServiciosSelector();
  cargarSlotsNuevaCita();
  openModal('modalNuevaCita');
}

function abrirNuevaCitaEnSlot(recursoId, fecha, horaInicio, horaFin) {
  slotSeleccionado = { recursoId, fecha, horaInicio, horaFin };
  document.getElementById('nuevaCitaFecha').value = fecha;
  document.getElementById('nuevaCitaRecurso').value = recursoId;
  cargarServiciosSelector();
  // Marcar slot como seleccionado
  cargarSlotsNuevaCita().then(() => {
    document.querySelectorAll('.slot-btn').forEach(b => {
      if (b.dataset.inicio === horaInicio) b.classList.add('selected');
    });
  });
  openModal('modalNuevaCita');
}

function cerrarModalNuevaCita() { closeModal('modalNuevaCita'); slotSeleccionado = null; }

async function cargarServiciosSelector() {
  const recursoId = document.getElementById('nuevaCitaRecurso').value;
  if (!recursoId) return;
  try {
    const data = await api('GET', '/api/agenda/recursos');
    const recurso = data.find(r => r.id == recursoId);
    const sel = document.getElementById('nuevaCitaServicio');
    sel.innerHTML = '<option value="">Seleccionar servicio</option>' +
      (recurso?.servicios || []).map(s =>
        `<option value="${s.id}" data-duracion="${s.duracion_min}">${s.nombre} (${s.duracion_min}min)</option>`
      ).join('');
  } catch {}
}

async function cargarSlotsNuevaCita() {
  const recursoId = document.getElementById('nuevaCitaRecurso')?.value;
  const fecha = document.getElementById('nuevaCitaFecha')?.value;
  if (!recursoId || !fecha) return;

  const servSel = document.getElementById('nuevaCitaServicio');
  const duracion = servSel?.selectedOptions[0]?.dataset.duracion || '';

  const params = new URLSearchParams({ recurso_id: recursoId, fecha });
  if (duracion) params.set('duracion', duracion);

  try {
    const slots = await api('GET', `/api/agenda/slots?${params}`);
    const grid = document.getElementById('slotsDisponibles');
    if (!slots.length) {
      grid.innerHTML = '<span style="color:var(--t2);font-size:12px;">Sin slots disponibles</span>';
      return;
    }
    grid.innerHTML = slots.map(s => `
      <button class="slot-btn" data-inicio="${s.hora_inicio}" data-fin="${s.hora_fin}"
        onclick="seleccionarSlot(this,'${s.hora_inicio}','${s.hora_fin}')">
        ${s.hora_inicio}
      </button>
    `).join('');
  } catch {}
}

function seleccionarSlot(btn, inicio, fin) {
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  slotSeleccionado = {
    recursoId: document.getElementById('nuevaCitaRecurso').value,
    fecha: document.getElementById('nuevaCitaFecha').value,
    horaInicio: inicio,
    horaFin: fin,
  };
}

function actualizarDuracion() { cargarSlotsNuevaCita(); }

async function buscarClientePorRut() {
  const rut = document.getElementById('nuevaCitaRut').value.trim();
  if (!rut) return;
  try {
    const data = await api('GET', `/api/clientes?q=${encodeURIComponent(rut)}&per_page=1`);
    const cliente = data.data?.[0] || data[0];
    if (cliente) {
      document.getElementById('nuevaCitaPaciente').value = cliente.nombre;
      if (cliente.telefono) document.getElementById('nuevaCitaTelefono').value = cliente.telefono;
      document.getElementById('rutStatus').textContent = '✓ Cliente encontrado';
      document.getElementById('rutStatus').style.color = 'var(--ac)';
    }
  } catch {}
}

async function agendarSiguienteCita(fecha, inicio, fin) {
  // Pre-llenar modal con datos del paciente actual
  if (citaActivaData) {
    document.getElementById('nuevaCitaPaciente').value = citaActivaData.paciente_nombre;
    document.getElementById('nuevaCitaRut').value = citaActivaData.paciente_rut || '';
    document.getElementById('nuevaCitaTelefono').value = citaActivaData.paciente_telefono || '';
  }
  document.getElementById('nuevaCitaFecha').value = fecha;
  document.getElementById('nuevaCitaRecurso').value = recursoActivoId || '';
  await cargarSlotsNuevaCita();
  // Seleccionar el slot sugerido
  setTimeout(() => {
    document.querySelectorAll('.slot-btn').forEach(b => {
      if (b.dataset.inicio === inicio) { b.click(); }
    });
  }, 200);
  openModal('modalNuevaCita');
}

async function confirmarNuevaCita() {
  if (!slotSeleccionado) { toast('Selecciona un horario', 'warn'); return; }
  const nombre = document.getElementById('nuevaCitaPaciente').value.trim();
  if (!nombre) { toast('Ingresa el nombre del paciente', 'warn'); return; }

  const servSel = document.getElementById('nuevaCitaServicio');
  const servId = servSel?.value || null;
  const duracion = servSel?.selectedOptions[0]?.dataset.duracion;

  const horaFin = slotSeleccionado.horaFin || (() => {
    // Calcular hora fin basada en duración
    const [h, m] = slotSeleccionado.horaInicio.split(':').map(Number);
    const total = h * 60 + m + (parseInt(duracion) || 30);
    return `${String(Math.floor(total/60)).padStart(2,'0')}:${String(total%60).padStart(2,'0')}`;
  })();

  try {
    await api('POST', '/api/agenda/citas', {
      agenda_recurso_id: slotSeleccionado.recursoId,
      agenda_servicio_id: servId || null,
      fecha: slotSeleccionado.fecha,
      hora_inicio: slotSeleccionado.horaInicio,
      hora_fin: horaFin,
      paciente_nombre: nombre,
      paciente_rut: document.getElementById('nuevaCitaRut').value,
      paciente_telefono: document.getElementById('nuevaCitaTelefono').value,
      notas_publicas: document.getElementById('nuevaCitaNotas').value,
      origen: 'admin',
    });
    toast('✓ Cita agendada', 'ok');
    cerrarModalNuevaCita();
    loadAgenda();
  } catch(e) { toast(e.message || 'Error agendando', 'err'); }
}

// ── NAVEGACIÓN FECHA ────────────────────────────────────────────
function cambiarFecha(d) { fechaActual.setDate(fechaActual.getDate() + d); loadAgenda(); }
function irHoy() { fechaActual = new Date(); loadAgenda(); }

function selPay(btn, m) {
  document.querySelectorAll('#modalCobro .pbtn').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  payMetodo = m;
}

// ── INIT ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => loadAgenda());
// Auto-refresh cada 60 segundos
setInterval(loadAgenda, 60000);
</script>
@endpush
```

---

## PARTE 7 — LANDING PÚBLICO (`resources/views/public/agenda.blade.php`)

```blade
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $config->titulo_landing ?? 'Agenda tu hora' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Variables del tenant (inyectadas desde config) */
    :root {
      --ac: {{ $config->color_primario ?? '#6366f1' }};
      --bg: #08080a; --s1: #111115; --s2: #18181e; --b1: #1e1e28; --b2: #2a2a3a;
      --tx: #e8e8f0; --t2: #7878a0; --t3: #3a3a55; --err: #ff3f5b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'IBM Plex Sans', sans-serif; background: var(--bg); color: var(--tx); min-height: 100vh; }
    .container { max-width: 560px; margin: 0 auto; padding: 24px 16px; }
    h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
    .desc { color: var(--t2); font-size: 14px; margin-bottom: 24px; }
    .step { display: none; }
    .step.active { display: block; }
    .step-indicator {
      display: flex; gap: 8px; margin-bottom: 24px;
    }
    .step-dot {
      width: 8px; height: 8px; border-radius: 50%; background: var(--b2);
    }
    .step-dot.active { background: var(--ac); }
    .card { background: var(--s2); border: 1px solid var(--b1); border-radius: 12px; padding: 16px; margin-bottom: 12px; cursor: pointer; transition: border-color .12s; }
    .card:hover, .card.selected { border-color: var(--ac); }
    .card.selected { background: rgba(99,102,241,.06); }
    .card-nombre { font-size: 15px; font-weight: 600; }
    .card-det { font-size: 12px; color: var(--t2); margin-top: 3px; }
    .servicios-grid { display: flex; flex-direction: column; gap: 8px; }
    .srv-card { padding: 12px; background: var(--s1); border: 1.5px solid var(--b2); border-radius: 8px; cursor: pointer; transition: border-color .12s; }
    .srv-card.selected { border-color: var(--ac); background: rgba(99,102,241,.05); }
    .srv-nombre { font-size: 14px; font-weight: 600; }
    .srv-det { font-size: 12px; color: var(--t2); margin-top: 2px; }
    label { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--t3); display: block; margin-bottom: 6px; font-family: 'IBM Plex Mono', monospace; }
    input, textarea, select {
      width: 100%; background: var(--s2); border: 1.5px solid var(--b2); border-radius: 8px;
      color: var(--tx); font-family: 'IBM Plex Sans', sans-serif; font-size: 15px;
      padding: 10px 12px; outline: none; transition: border-color .15s; margin-bottom: 12px;
      -webkit-appearance: none;
    }
    input:focus, textarea:focus { border-color: var(--ac); }
    .field { margin-bottom: 12px; }
    .slots-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .slot { padding: 8px 14px; background: var(--s1); border: 1.5px solid var(--b2); border-radius: 8px; font-family: 'IBM Plex Mono', monospace; font-size: 13px; cursor: pointer; transition: all .12s; }
    .slot:hover, .slot.selected { border-color: var(--ac); color: var(--ac); background: rgba(99,102,241,.08); }
    .cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .cal-nav button { background: var(--s2); border: 1px solid var(--b2); color: var(--tx); padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 16px; }
    #fechaLabel { font-family: 'IBM Plex Mono', monospace; font-size: 14px; font-weight: 600; }
    .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: filter .12s; margin-bottom: 8px; }
    .btn-primary { background: var(--ac); color: #000; }
    .btn-secondary { background: var(--s2); color: var(--tx); border: 1.5px solid var(--b2); }
    .btn:active { filter: brightness(.88); }
    .err-msg { color: var(--err); font-size: 12px; margin-top: 4px; }
    .success-icon { font-size: 60px; text-align: center; margin: 20px 0; }
  </style>
</head>
<body>
<div class="container">

  @if($config?->logo_url)
    <img src="{{ $config->logo_url }}" alt="Logo" style="height:48px;margin-bottom:16px;border-radius:8px;">
  @endif
  <h1>{{ $config->titulo_landing ?? 'Agenda tu hora' }}</h1>
  <p class="desc">{{ $config->descripcion_landing ?? 'Reserva tu hora en línea de forma simple y rápida.' }}</p>

  <div class="step-indicator">
    <div class="step-dot active" id="dot1"></div>
    <div class="step-dot" id="dot2"></div>
    <div class="step-dot" id="dot3"></div>
    <div class="step-dot" id="dot4"></div>
  </div>

  {{-- PASO 1: Elegir profesional --}}
  <div class="step active" id="step1">
    <label>Selecciona un profesional</label>
    @foreach($recursos as $r)
    <div class="card" onclick="elegirRecurso({{ $r->id }}, '{{ $r->nombre }}', this)">
      <div class="card-nombre">{{ $r->nombre }}</div>
      <div class="card-det">{{ $r->especialidad }}</div>
    </div>
    @endforeach
  </div>

  {{-- PASO 2: Elegir servicio --}}
  <div class="step" id="step2">
    <label>¿Qué tipo de consulta necesitas?</label>
    <div class="servicios-grid" id="serviciosGrid"></div>
    <button class="btn btn-secondary" onclick="goStep(1)">← Volver</button>
  </div>

  {{-- PASO 3: Elegir fecha y hora --}}
  <div class="step" id="step3">
    <label>Elige fecha y hora</label>
    <div class="cal-nav">
      <button onclick="cambiarFechaLanding(-1)">‹</button>
      <span id="fechaLabel">Hoy</span>
      <button onclick="cambiarFechaLanding(1)">›</button>
    </div>
    <div id="slotsLanding" class="slots-grid">
      <span style="color:var(--t2);font-size:13px;">Cargando slots...</span>
    </div>
    <button class="btn btn-secondary" onclick="goStep(2)">← Volver</button>
  </div>

  {{-- PASO 4: Datos del paciente --}}
  <div class="step" id="step4">
    <label>Tus datos</label>
    <div class="field">
      <label>Nombre completo *</label>
      <input type="text" id="lNombre" placeholder="Juan Pérez">
    </div>
    <div class="field">
      <label>RUT (opcional)</label>
      <input type="text" id="lRut" placeholder="12.345.678-9" oninput="fmtRutLanding(this)">
    </div>
    <div class="field">
      <label>Teléfono *</label>
      <input type="tel" id="lTelefono" placeholder="+56 9 xxxx xxxx">
    </div>
    <div class="field">
      <label>Email (opcional)</label>
      <input type="email" id="lEmail" placeholder="juan@ejemplo.cl">
    </div>
    <div class="field">
      <label>Motivo de la consulta</label>
      <textarea id="lNotas" rows="2" placeholder="Cuéntanos brevemente el motivo..."></textarea>
    </div>
    <div id="landingError" class="err-msg" style="display:none;margin-bottom:12px;"></div>
    <button class="btn btn-primary" onclick="confirmarCitaLanding()">
      Confirmar cita →
    </button>
    <button class="btn btn-secondary" onclick="goStep(3)">← Volver</button>
  </div>

  {{-- PASO 5: Confirmación --}}
  <div class="step" id="step5">
    <div class="success-icon">✅</div>
    <h2 style="text-align:center;margin-bottom:8px;">¡Cita confirmada!</h2>
    <p style="text-align:center;color:var(--t2);margin-bottom:24px;">
      Te esperamos. Si necesitas cancelar o reagendar, contáctanos.
    </p>
    <div class="card" style="cursor:default;" id="resumenCita"></div>
  </div>

</div>

<script>
let recursoId = null, recursoNombre = null;
let servicioId = null, servicioDuracion = 30;
let fechaLanding = new Date();
let slotSel = null;

function goStep(n) {
  document.querySelectorAll('.step').forEach((s,i) => s.classList.toggle('active', i === n-1));
  document.querySelectorAll('.step-dot').forEach((d,i) => d.classList.toggle('active', i < n));
  if (n === 3) cargarSlotsLanding();
}

function elegirRecurso(id, nombre, el) {
  document.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  recursoId = id; recursoNombre = nombre;
  cargarServicios();
  setTimeout(() => goStep(2), 150);
}

async function cargarServicios() {
  const data = await fetch(`/api/public/agenda/slots?recurso_id=${recursoId}&fecha=${fechaStr(fechaLanding)}&duracion=0`).then(()=>null).catch(()=>null);
  // Obtener servicios del recurso
  const res = await fetch(`/api/agenda/recursos`).then(r=>r.json()).catch(()=>[]);
  const recurso = res.find(r => r.id == recursoId);
  const servicios = recurso?.servicios || [];
  const grid = document.getElementById('serviciosGrid');
  if (!servicios.length) {
    grid.innerHTML = '<div class="srv-card selected"><div class="srv-nombre">Consulta general</div></div>';
    goStep(3);
    return;
  }
  grid.innerHTML = servicios.map(s => `
    <div class="srv-card" onclick="elegirServicio(${s.id}, ${s.duracion_min}, this)">
      <div class="srv-nombre">${s.nombre}</div>
      <div class="srv-det">${s.duracion_min} min${s.precio ? ' · $'+s.precio.toLocaleString('es-CL') : ''}</div>
    </div>
  `).join('');
}

function elegirServicio(id, dur, el) {
  document.querySelectorAll('.srv-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  servicioId = id; servicioDuracion = dur;
  setTimeout(() => goStep(3), 150);
}

function fechaStr(d) { return d.toISOString().split('T')[0]; }
function formatFechaCorta(d) {
  return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long' });
}
function cambiarFechaLanding(d) { fechaLanding.setDate(fechaLanding.getDate() + d); cargarSlotsLanding(); }

async function cargarSlotsLanding() {
  document.getElementById('fechaLabel').textContent = formatFechaCorta(fechaLanding);
  const container = document.getElementById('slotsLanding');
  container.innerHTML = '<span style="color:var(--t2);font-size:13px;">Cargando...</span>';
  try {
    const params = new URLSearchParams({ recurso_id: recursoId, fecha: fechaStr(fechaLanding), duracion: servicioDuracion });
    const slots = await fetch(`/api/public/agenda/slots?${params}`).then(r=>r.json());
    if (!slots.length) {
      container.innerHTML = '<span style="color:var(--t2);font-size:13px;">Sin horarios disponibles este día</span>';
      return;
    }
    container.innerHTML = slots.map(s => `
      <div class="slot" data-inicio="${s.hora_inicio}" data-fin="${s.hora_fin}"
        onclick="elegirSlot('${s.hora_inicio}','${s.hora_fin}',this)">
        ${s.hora_inicio}
      </div>
    `).join('');
  } catch(e) {
    container.innerHTML = '<span style="color:var(--err);font-size:13px;">Error cargando slots</span>';
  }
}

function elegirSlot(inicio, fin, el) {
  document.querySelectorAll('.slot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  slotSel = { inicio, fin };
  setTimeout(() => goStep(4), 150);
}

async function confirmarCitaLanding() {
  const nombre = document.getElementById('lNombre').value.trim();
  const telefono = document.getElementById('lTelefono').value.trim();
  const errEl = document.getElementById('landingError');

  if (!nombre || !telefono) {
    errEl.textContent = 'Nombre y teléfono son obligatorios';
    errEl.style.display = 'block'; return;
  }
  errEl.style.display = 'none';

  try {
    await fetch('/api/public/agenda/cita', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      body: JSON.stringify({
        agenda_recurso_id: recursoId,
        agenda_servicio_id: servicioId,
        fecha: fechaStr(fechaLanding),
        hora_inicio: slotSel.inicio,
        hora_fin: slotSel.fin,
        paciente_nombre: nombre,
        paciente_rut: document.getElementById('lRut').value,
        paciente_telefono: telefono,
        paciente_email: document.getElementById('lEmail').value,
        notas_publicas: document.getElementById('lNotas').value,
      })
    });

    // Mostrar confirmación
    document.getElementById('resumenCita').innerHTML = `
      <div style="display:grid;gap:8px;font-size:14px;">
        <div>👤 <strong>${nombre}</strong></div>
        <div>🏥 <strong>${recursoNombre}</strong></div>
        <div>📅 <strong>${formatFechaCorta(fechaLanding)}</strong></div>
        <div>⏰ <strong>${slotSel.inicio}</strong></div>
        <div>📱 ${telefono}</div>
      </div>
    `;
    goStep(5);
  } catch(e) {
    errEl.textContent = 'Ocurrió un error. Por favor intenta nuevamente.';
    errEl.style.display = 'block';
  }
}

function fmtRutLanding(inp) {
  let v = inp.value.replace(/[^0-9kK]/g,'');
  if (v.length > 1) inp.value = v.slice(0,-1).replace(/\B(?=(\d{3})+(?!\d))/g,'.') + '-' + v.slice(-1).toUpperCase();
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  fechaLanding = new Date();
});
</script>
</body>
</html>
```

---

## PARTE 8 — SEEDER DEMO

Path: `database/seeders/AgendaDemoSeeder.php`

```php
<?php
namespace Database\Seeders;

use App\Models\Tenant\{AgendaRecurso, AgendaHorario, AgendaServicio, AgendaConfig, AgendaCita};
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AgendaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMedico();
        $this->seedPadel();
        $this->seedLegal();
    }

    private function seedMedico(): void
    {
        // Solo si estamos en tenant demo-medico
        if (!str_contains(tenant('id') ?? '', 'medico')) return;

        AgendaConfig::create([
            'titulo_landing' => 'Agenda tu hora médica',
            'descripcion_landing' => 'Reserva tu consulta en línea de forma rápida y segura.',
            'color_primario' => '#3dd9eb',
            'recordatorio_activo' => true,
        ]);

        $dr = AgendaRecurso::create([
            'nombre' => 'Dr. Demo', 'tipo' => 'profesional',
            'especialidad' => 'Medicina General', 'color' => '#3dd9eb', 'orden' => 1,
            'usuario_id' => \App\Models\Tenant\Usuario::where('rol','operario')->first()?->id,
        ]);
        $this->crearHorarios($dr);
        AgendaServicio::create(['agenda_recurso_id' => $dr->id, 'nombre' => 'Consulta general', 'duracion_min' => 30, 'precio' => 45000]);
        AgendaServicio::create(['agenda_recurso_id' => $dr->id, 'nombre' => 'Control', 'duracion_min' => 20, 'precio' => 25000]);

        // Citas demo para hoy
        $this->crearCitasDemo($dr->id);
    }

    private function seedPadel(): void
    {
        if (!str_contains(tenant('id') ?? '', 'padel')) return;

        AgendaConfig::create(['titulo_landing' => 'Reserva tu cancha', 'color_primario' => '#00c4ff']);

        foreach (['Cancha 1', 'Cancha 2', 'Cancha 3'] as $i => $nombre) {
            $r = AgendaRecurso::create([
                'nombre' => $nombre, 'tipo' => 'recurso_fisico',
                'especialidad' => 'Pádel', 'color' => '#00c4ff', 'orden' => $i + 1,
            ]);
            $this->crearHorarios($r, '08:00', '22:00', 60);
            AgendaServicio::create(['agenda_recurso_id' => $r->id, 'nombre' => 'Cancha 1 hora', 'duracion_min' => 60, 'precio' => 18000]);
        }
    }

    private function seedLegal(): void
    {
        if (!str_contains(tenant('id') ?? '', 'legal')) return;

        AgendaConfig::create(['titulo_landing' => 'Agenda tu consulta legal', 'color_primario' => '#7c6af7']);

        $ab = AgendaRecurso::create([
            'nombre' => 'Abg. Demo', 'tipo' => 'profesional',
            'especialidad' => 'Derecho Civil', 'color' => '#7c6af7', 'orden' => 1,
        ]);
        $this->crearHorarios($ab);
        AgendaServicio::create(['agenda_recurso_id' => $ab->id, 'nombre' => 'Consulta inicial', 'duracion_min' => 60, 'precio' => 80000]);
    }

    private function crearHorarios(AgendaRecurso $r, string $inicio = '09:00', string $fin = '18:00', int $slot = 30): void
    {
        foreach ([1,2,3,4,5] as $dia) { // Lun-Vie
            AgendaHorario::create([
                'agenda_recurso_id' => $r->id,
                'dia_semana' => $dia,
                'hora_inicio' => $inicio,
                'hora_fin'    => $fin,
                'duracion_slot_min' => $slot,
            ]);
        }
    }

    private function crearCitasDemo(int $recursoId): void
    {
        $hoy = today()->toDateString();
        $citas = [
            ['09:00','09:30','Paciente Demo 1','19.111.111-1','confirmada'],
            ['10:00','10:30','Paciente Demo 2','19.222.222-2','pendiente'],
            ['11:30','12:00','Paciente Demo 3','19.333.333-3','en_curso'],
            ['15:00','15:30','Paciente Demo 4','19.444.444-4','pendiente'],
        ];
        foreach ($citas as [$hi, $hf, $nombre, $rut, $estado]) {
            AgendaCita::create([
                'agenda_recurso_id' => $recursoId,
                'fecha' => $hoy,
                'hora_inicio' => $hi, 'hora_fin' => $hf,
                'paciente_nombre' => $nombre, 'paciente_rut' => $rut,
                'estado' => $estado, 'origen' => 'admin',
            ]);
        }
    }
}
```

---

## PARTE 9 — MODIFICACIONES REQUERIDAS EN ARCHIVOS EXISTENTES

### 9.1 `app/Services/ConfigRubroService.php`

Agregar en el método que se dispara al activar M08:

```php
// Cuando se activa M08, crear config agenda por defecto si no existe
if ($moduloId === 'M08') {
    \App\Models\Tenant\AgendaConfig::firstOrCreate([], [
        'titulo_landing' => 'Agenda tu hora',
        'landing_publico_activo' => true,
        'recordatorio_activo' => true,
        'color_primario' => config('tenancy.current_tenant.rubro_config.accent_color', '#6366f1'),
    ]);
}
```

### 9.2 `resources/views/layouts/app.blade.php` (o equivalente)

Agregar enlace en el nav del POS para cajeros en tenants con M08:

```blade
@if(in_array('M08', $modulosActivos ?? []))
<a href="/pos/agenda" class="nav-link-item {{ request()->is('pos/agenda*') ? 'nav-active' : '' }}">
  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
  </svg>
  Agenda
</a>
@endif
```

También agregar en mobile bottom nav del POS.

### 9.3 `resources/views/admin/agenda/index.blade.php`

Vista admin simplificada para configurar recursos y horarios. Incluir:
- Lista de recursos (profesionales/canchas)
- CRUD de horarios por recurso
- CRUD de servicios por recurso
- Config del landing público (toggle, título, descripción)
- Link al landing público: `/agenda`
- Vista de agenda del día (iframe o enlace al `/pos/agenda`)

---

## PARTE 10 — JOBS (NOTIFICACIONES)

### `app/Jobs/RecordatorioCitaJob.php`

```php
<?php
namespace App\Jobs;

use App\Models\Tenant\AgendaCita;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RecordatorioCitaJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public AgendaCita $cita) {}

    public function handle(): void
    {
        if ($this->cita->recordatorio_enviado_at) return;
        if (!in_array($this->cita->estado, ['pendiente','confirmada'])) return;

        // TODO: Integrar con WhatsAppNotifier cuando M17 esté disponible
        // app(WhatsAppNotifier::class)->sendRecordatorio($this->cita);

        $this->cita->update(['recordatorio_enviado_at' => now()]);
    }
}
```

### En `bootstrap/app.php` — registrar schedule:

```php
// Recordatorios de citas (cada hora)
Schedule::call(function () {
    tenancy()->runForMultiple(null, function () {
        \App\Models\Tenant\AgendaCita::query()
            ->whereIn('estado', ['pendiente','confirmada'])
            ->whereDate('fecha', today())
            ->whereNull('recordatorio_enviado_at')
            ->each(fn($cita) => RecordatorioCitaJob::dispatch($cita));
    });
})->hourly();
```

---

## PARTE 11 — ORDEN DE EJECUCIÓN EN ANTIGRAVITY

Ejecutar exactamente en este orden:

```sh
# 1. Migraciones (en todos los tenants con M08)
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate --path=database/migrations/tenant --tenants=demo-medico,demo-padel,demo-legal"

# 2. Seed de datos demo
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run db:seed --option=class=AgendaDemoSeeder --tenants=demo-medico,demo-padel,demo-legal"

# 3. Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear && php artisan route:cache"

# 4. Verificar rutas
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=agenda"
```

---

## PARTE 12 — CHECKLIST DE VERIFICACIÓN

### Funcionalidades a probar en `demo-medico`:

**Como operario (Dr. Demo):**
- [ ] Ir a `/pos/agenda` → ver su columna del día con citas demo
- [ ] Click en cita → panel lateral con datos del paciente
- [ ] Botón "Iniciar consulta" → estado cambia a `en_curso`
- [ ] Campo notas internas → guardar y persistir
- [ ] "Próxima cita sugerida" aparece con 5 opciones
- [ ] "Completar y cobrar" → modal de cobro con monto pre-cargado
- [ ] Pago procesado → venta creada en backend

**Como cajero (Recepcionista Demo):**
- [ ] Ir a `/pos/agenda` → ver TODAS las columnas de profesionales
- [ ] Slots libres visibles como "09:00 — libre"
- [ ] Click en slot libre → modal nueva cita pre-cargado con hora
- [ ] Buscar RUT en nueva cita → pre-llena nombre si existe en clientes
- [ ] Crear cita → aparece en la columna
- [ ] Marcar "Confirmada llegada" → badge cambia
- [ ] "No asistió" → cita griseada

**Landing público `/agenda`:**
- [ ] Acceder SIN login → página carga
- [ ] Flujo 4 pasos: profesional → servicio → fecha/hora → datos
- [ ] Slots reales según horarios configurados
- [ ] Enviar formulario → cita creada con estado `pendiente`
- [ ] Aparece en admin en el mismo día

**Admin `/admin/agenda`:**
- [ ] Ver recursos configurados
- [ ] Crear nuevo profesional
- [ ] Configurar horarios (Lun-Vie 09-18)
- [ ] Agregar servicios con precio y duración
- [ ] Toggle landing público activo/inactivo

---

## RESUMEN DE ARCHIVOS A CREAR/MODIFICAR

| Acción | Archivo |
|---|---|
| CREAR | `database/migrations/tenant/2026_03_26_000001_create_agenda_recursos_table.php` |
| CREAR | `database/migrations/tenant/2026_03_26_000002_create_agenda_horarios_table.php` |
| CREAR | `database/migrations/tenant/2026_03_26_000003_create_agenda_bloqueos_table.php` |
| CREAR | `database/migrations/tenant/2026_03_26_000004_create_agenda_config_table.php` |
| CREAR | `database/migrations/tenant/2026_03_26_000005_create_agenda_servicios_table.php` |
| CREAR | `database/migrations/tenant/2026_03_26_000006_create_agenda_citas_table.php` |
| CREAR | `app/Models/Tenant/AgendaRecurso.php` |
| CREAR | `app/Models/Tenant/AgendaHorario.php` |
| CREAR | `app/Models/Tenant/AgendaBloqueo.php` |
| CREAR | `app/Models/Tenant/AgendaConfig.php` |
| CREAR | `app/Models/Tenant/AgendaServicio.php` |
| CREAR | `app/Models/Tenant/AgendaCita.php` |
| CREAR | `app/Services/AgendaService.php` |
| CREAR | `app/Http/Controllers/Tenant/AgendaController.php` |
| CREAR | `app/Jobs/RecordatorioCitaJob.php` |
| CREAR | `resources/views/pos/agenda.blade.php` |
| CREAR | `resources/views/admin/agenda/index.blade.php` |
| CREAR | `resources/views/public/agenda.blade.php` |
| CREAR | `database/seeders/AgendaDemoSeeder.php` |
| MODIFICAR | `routes/tenant.php` — agregar rutas M08 |
| MODIFICAR | `bootstrap/app.php` — agregar schedule recordatorios |
| MODIFICAR | `app/Services/ConfigRubroService.php` — init config M08 |
| MODIFICAR | `resources/views/layouts/app.blade.php` — nav link agenda |

---

*Plan generado 2026-03-26 · BenderAnd ERP · M08 Agenda*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade*
