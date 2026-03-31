# SPEC — Fix Landing Público Agenda demo-medico
**Sistema:** BenderAnd ERP · Laravel 11 · stancl/tenancy v3
**Fecha:** 2026-03-26
**Container:** `benderandos_app` · código en `/app`
**Síntoma:** `/agenda` en demo-medico carga pero no muestra médicos ni permite agendar

---

## DIAGNÓSTICO: 4 causas posibles en cadena

```
GET /agenda (landing público)
  └─ AgendaController::landing()
       ├─ 1. ¿Existe AgendaConfig con landing_publico_activo = true?  → si NO → 404
       ├─ 2. ¿Existen AgendaRecurso activos?                         → si NO → grid vacío
       │       └─ Causa: el seed no los creó / nunca se corrió init
       ├─ 3. ¿Los recursos tienen servicios?                         → si NO → paso 2 muestra fallback
       └─ 4. ¿El endpoint /api/public/agenda/slots responde?         → si NO → slots vacíos
```

---

## PASO 1 — DIAGNÓSTICO DESDE TINKER

Ejecutar en orden para identificar cuál de las 4 causas aplica:

```bash
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"
```

```php
// Dentro de tinker:

// Inicializar tenant médico
tenancy()->initialize(App\Models\Central\Tenant::find('demo-medico'));

// 1. ¿Existe AgendaConfig?
$cfg = App\Models\Tenant\AgendaConfig::first();
// Si es null → problema 1
// Si existe → ver si landing_publico_activo es true
$cfg?->landing_publico_activo;   // debe ser true

// 2. ¿Existen recursos activos?
App\Models\Tenant\AgendaRecurso::where('activo', true)->get(['id','nombre','tipo']);
// Si vacío → problema 2 → ir a PASO 2

// 3. ¿Los recursos tienen servicios?
App\Models\Tenant\AgendaRecurso::with('servicios')->first();
// Si servicios = [] → problema 3

// 4. ¿Los recursos tienen horarios?
App\Models\Tenant\AgendaRecurso::with('horarios')->first()?->horarios;
// Si vacío → problema 4

// Limpiar
tenancy()->end();
```

---

## PASO 2 — FIX: Crear recursos, config y horarios desde cero

Si el diagnóstico confirma que faltan recursos, este seeder los crea directamente.

### Opción A: Comando artisan (recomendado)

```bash
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos \
  --tenant=demo-medico"
```

Si el comando no existe aún, ejecutar el seeder manual:

```bash
docker exec benderandos_app sh -c "cd /app && php artisan tinker"
```

```php
tenancy()->initialize(App\Models\Central\Tenant::find('demo-medico'));

use App\Models\Tenant\{AgendaConfig, AgendaRecurso, AgendaHorario, AgendaServicio, Usuario};

// ── 1. Config del landing ──────────────────────────────────────────
AgendaConfig::updateOrCreate([], [
    'titulo_landing'           => 'Agenda tu hora',
    'descripcion_landing'      => 'Reserva tu consulta médica online. Rápido y sin llamadas.',
    'landing_publico_activo'   => true,
    'confirmacion_wa_activa'   => false,
    'recordatorio_activo'      => true,
    'recordatorio_horas_antes' => 24,
    'requiere_telefono'        => true,
    'requiere_email'           => false,
    'color_primario'           => '#3dd9eb',   // accent médico
]);

// ── 2. Crear recursos para cada usuario operario ───────────────────
$colores = ['#3dd9eb', '#00e5a0', '#7c6af7', '#00c4ff', '#f5c518'];
$operarios = Usuario::whereIn('rol', ['operario', 'admin', 'cajero'])
    ->where('activo', true)
    ->get();

foreach ($operarios as $i => $u) {
    $recurso = AgendaRecurso::updateOrCreate(
        ['usuario_id' => $u->id],
        [
            'nombre'                => $u->nombre,
            'tipo'                  => 'profesional',
            'especialidad'          => 'Médico General',
            'color'                 => $colores[$i % count($colores)],
            'orden'                 => $i + 1,
            'auto_creado'           => true,
            'hereda_horario_tenant' => true,
            'activo'                => true,
        ]
    );

    // Horarios L-V 09-18, S 09-13
    $horarios = [
        [1,'09:00','18:00',1,30], [2,'09:00','18:00',1,30],
        [3,'09:00','18:00',1,30], [4,'09:00','18:00',1,30],
        [5,'09:00','18:00',1,30], [6,'09:00','13:00',1,30],
        [7,'09:00','18:00',0,30],
    ];
    foreach ($horarios as [$dia, $ini, $fin, $act, $slot]) {
        AgendaHorario::updateOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $dia],
            ['hora_inicio' => $ini, 'hora_fin' => $fin, 'activo' => $act, 'duracion_slot_min' => $slot]
        );
    }

    // Servicios básicos
    $servicios = [
        ['Consulta general',     30, 45000],
        ['Control médico',       20, 35000],
        ['Urgencia / revisión',  15, 55000],
    ];
    foreach ($servicios as [$nombre, $dur, $precio]) {
        AgendaServicio::firstOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'nombre' => $nombre],
            ['duracion_min' => $dur, 'precio' => $precio, 'activo' => true]
        );
    }

    echo "✓ Recurso creado: {$u->nombre}\n";
}

// ── 3. Si no hay operarios, crear recurso demo ─────────────────────
if ($operarios->isEmpty()) {
    $recurso = AgendaRecurso::firstOrCreate(
        ['nombre' => 'Dr. Demo'],
        [
            'tipo'        => 'profesional',
            'especialidad'=> 'Médico General',
            'color'       => '#3dd9eb',
            'orden'       => 1,
            'activo'      => true,
        ]
    );

    $horarios = [
        [1,'09:00','18:00',1,30],[2,'09:00','18:00',1,30],
        [3,'09:00','18:00',1,30],[4,'09:00','18:00',1,30],
        [5,'09:00','18:00',1,30],[6,'09:00','13:00',1,30],
        [7,'09:00','18:00',0,30],
    ];
    foreach ($horarios as [$dia,$ini,$fin,$act,$slot]) {
        AgendaHorario::updateOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $dia],
            ['hora_inicio'=>$ini,'hora_fin'=>$fin,'activo'=>$act,'duracion_slot_min'=>$slot]
        );
    }
    foreach ([['Consulta general',30,45000],['Control',20,35000]] as [$n,$d,$p]) {
        AgendaServicio::firstOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'nombre' => $n],
            ['duracion_min' => $d, 'precio' => $p, 'activo' => true]
        );
    }
    echo "✓ Recurso demo creado\n";
}

tenancy()->end();
echo "Listo\n";
```

---

## PASO 3 — FIX: Rutas públicas faltantes

Verificar que las rutas públicas del landing existen:

```bash
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/public/agenda"
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=agenda"
```

Si no aparecen, agregar en `routes/tenant.php` **fuera** de cualquier grupo `auth`:

```php
// ── LANDING PÚBLICO AGENDA (sin auth) ─────────────────────────────
Route::get('/agenda', [AgendaController::class, 'landing'])->name('agenda.landing');

Route::prefix('api/public/agenda')->group(function () {
    Route::get('/recursos', [AgendaController::class, 'publicRecursos']);
    Route::get('/slots',    [AgendaController::class, 'publicSlots']);
    Route::post('/cita',    [AgendaController::class, 'publicCrearCita']);
});
```

---

## PASO 4 — FIX: Métodos en AgendaController

Verificar que los tres métodos públicos existen en `AgendaController`.
Si no, agregar:

```php
// app/Http/Controllers/Tenant/AgendaController.php

use App\Models\Tenant\{AgendaRecurso, AgendaConfig, AgendaCita, AgendaHorario, AgendaBloqueo};

/**
 * GET /agenda — Landing público
 */
public function landing()
{
    $config = AgendaConfig::first();

    // Si no existe config o está desactivado → crear una por defecto
    if (!$config) {
        $config = AgendaConfig::create([
            'titulo_landing'         => 'Agenda tu hora',
            'landing_publico_activo' => true,
            'requiere_telefono'      => true,
            'color_primario'         => '#00e5a0',
        ]);
    }

    if (!$config->landing_publico_activo) {
        abort(404, 'El sistema de agenda no está disponible.');
    }

    $recursos = AgendaRecurso::with(['servicios', 'horarios'])
        ->where('activo', true)
        ->orderBy('orden')
        ->get();

    return view('public.agenda', compact('config', 'recursos'));
}

/**
 * GET /api/public/agenda/recursos
 */
public function publicRecursos()
{
    $recursos = AgendaRecurso::with(['servicios', 'horarios'])
        ->where('activo', true)
        ->orderBy('orden')
        ->get()
        ->map(fn($r) => [
            'id'          => $r->id,
            'nombre'      => $r->nombre,
            'especialidad'=> $r->especialidad,
            'color'       => $r->color,
            'tipo'        => $r->tipo,
            'servicios'   => $r->servicios->map(fn($s) => [
                'id'          => $s->id,
                'nombre'      => $s->nombre,
                'duracion_min'=> $s->duracion_min,
                'precio'      => $s->precio,
            ]),
            'horarios'    => $r->horarios->where('activo', true)
                ->pluck('dia_semana')->values(),
        ]);

    return response()->json($recursos);
}

/**
 * GET /api/public/agenda/slots?recurso_id=&fecha=&duracion=
 */
public function publicSlots(\Illuminate\Http\Request $r)
{
    $r->validate([
        'recurso_id' => 'required|integer',
        'fecha'      => 'required|date',
        'duracion'   => 'nullable|integer|min:5',
    ]);

    $recurso  = AgendaRecurso::with(['horarios', 'citas'])->findOrFail($r->recurso_id);
    $fecha    = $r->fecha;
    $duracion = $r->duracion ?? 30;

    // Día de la semana (1=Lun … 7=Dom, PHP: 0=Dom…6=Sáb → convertir)
    $diaSemana = (int) date('N', strtotime($fecha)); // 1=Lun … 7=Dom

    $horario = $recurso->horarios
        ->where('dia_semana', $diaSemana)
        ->where('activo', true)
        ->first();

    if (!$horario) {
        return response()->json([]);
    }

    // Generar todos los slots del día
    $slots   = [];
    $inicio  = strtotime($fecha . ' ' . $horario->hora_inicio);
    $fin     = strtotime($fecha . ' ' . $horario->hora_fin);
    $step    = ($horario->duracion_slot_min ?? 30) * 60;
    $durSeg  = $duracion * 60;

    // Citas existentes ese día en ese recurso
    $citasOcupadas = AgendaCita::where('agenda_recurso_id', $recurso->id)
        ->where('fecha', $fecha)
        ->whereNotIn('estado', ['cancelada'])
        ->get(['hora_inicio', 'hora_fin']);

    // Bloqueos activos ese día
    $bloqueos = AgendaBloqueo::where('agenda_recurso_id', $recurso->id)
        ->where('fecha_inicio', '<=', $fecha)
        ->where('fecha_fin',    '>=', $fecha)
        ->get(['hora_inicio', 'hora_fin']);

    $ahora = time();

    while ($inicio + $durSeg <= $fin) {
        $slotIni = date('H:i', $inicio);
        $slotFin = date('H:i', $inicio + $durSeg);

        // No mostrar slots pasados si es hoy
        if ($fecha === date('Y-m-d') && $inicio <= $ahora) {
            $inicio += $step;
            continue;
        }

        // Verificar conflicto con citas
        $ocupado = $citasOcupadas->first(function ($c) use ($slotIni, $slotFin) {
            return $c->hora_inicio < $slotFin && $c->hora_fin > $slotIni;
        });

        // Verificar conflicto con bloqueos
        $bloqueado = $bloqueos->first(function ($b) use ($slotIni, $slotFin) {
            if (!$b->hora_inicio) return true; // bloqueo todo el día
            return $b->hora_inicio < $slotFin && $b->hora_fin > $slotIni;
        });

        if (!$ocupado && !$bloqueado) {
            $slots[] = [
                'hora_inicio' => $slotIni,
                'hora_fin'    => $slotFin,
            ];
        }

        $inicio += $step;
    }

    return response()->json($slots);
}

/**
 * POST /api/public/agenda/cita
 */
public function publicCrearCita(\Illuminate\Http\Request $r)
{
    $r->validate([
        'agenda_recurso_id'  => 'required|integer',
        'agenda_servicio_id' => 'nullable|integer',
        'fecha'              => 'required|date',
        'hora_inicio'        => 'required|date_format:H:i',
        'hora_fin'           => 'required|date_format:H:i',
        'paciente_nombre'    => 'required|string|max:255',
        'paciente_telefono'  => 'nullable|string|max:30',
        'paciente_email'     => 'nullable|email',
        'notas_publicas'     => 'nullable|string|max:500',
    ]);

    // Verificar disponibilidad antes de crear
    $conflicto = AgendaCita::where('agenda_recurso_id', $r->agenda_recurso_id)
        ->where('fecha', $r->fecha)
        ->whereNotIn('estado', ['cancelada'])
        ->where('hora_inicio', '<', $r->hora_fin)
        ->where('hora_fin',    '>', $r->hora_inicio)
        ->exists();

    if ($conflicto) {
        return response()->json([
            'error' => 'El horario seleccionado ya no está disponible. Por favor elige otro.'
        ], 409);
    }

    $cita = AgendaCita::create([
        'agenda_recurso_id'  => $r->agenda_recurso_id,
        'agenda_servicio_id' => $r->agenda_servicio_id,
        'fecha'              => $r->fecha,
        'hora_inicio'        => $r->hora_inicio,
        'hora_fin'           => $r->hora_fin,
        'paciente_nombre'    => $r->paciente_nombre,
        'paciente_telefono'  => $r->paciente_telefono,
        'paciente_email'     => $r->paciente_email,
        'notas_publicas'     => $r->notas_publicas,
        'estado'             => 'pendiente',
    ]);

    return response()->json([
        'ok'      => true,
        'cita_id' => $cita->id,
        'mensaje' => "Tu cita con {$cita->paciente_nombre} el {$cita->fecha} a las {$cita->hora_inicio} fue registrada.",
    ], 201);
}
```

---

## PASO 5 — FIX: Vista landing — corregir URL de la API

En `resources/views/public/agenda.blade.php`, el JS llama a `/api/public/agenda/slots`.
Verificar que la función `fetchPublic` usa la ruta correcta:

```javascript
// En el JS del landing, buscar la función fetchPublic o la llamada a slots:
// CORRECTO:
const slots = await fetchPublic(`/api/public/agenda/slots?recurso_id=${estado.recursoId}&fecha=${estado.fecha}&duracion=${estado.servicioDuracion}`);

// CORRECTO para crear cita:
await fetchPublic('/api/public/agenda/cita', 'POST', payload);
```

Si usa `/api/public/agenda/cita` pero la ruta es `POST /api/public/agenda/cita`, verificar que coincide. El spec H25 usa `/api/public/agenda/cita` pero en la ruta está como `/api/public/agenda/cita` — deben coincidir exactamente.

---

## PASO 6 — FIX: Modelo AgendaServicio — relación correcta

Verificar que `AgendaServicio` tiene el FK correcto:

```bash
docker exec benderandos_app sh -c "cd /app && php artisan tinker"
```

```php
tenancy()->initialize(App\Models\Central\Tenant::find('demo-medico'));

// ¿La tabla existe y tiene datos?
DB::select("SELECT column_name FROM information_schema.columns
            WHERE table_name = 'agenda_servicios'
            AND table_schema = current_schema()");

// ¿Tiene registros?
App\Models\Tenant\AgendaServicio::count();

// ¿El FK es agenda_recurso_id o recurso_id?
App\Models\Tenant\AgendaServicio::first()?->toArray();

tenancy()->end();
```

Si el FK en la tabla real es `recurso_id` (sin `agenda_`), actualizar el modelo:

```php
// app/Models/Tenant/AgendaServicio.php
public function recurso()
{
    // Usar el nombre real de la columna FK
    return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id'); // o 'recurso_id'
}
```

Y en `AgendaRecurso`:
```php
public function servicios()
{
    return $this->hasMany(AgendaServicio::class, 'agenda_recurso_id') // o 'recurso_id'
                ->where('activo', true);
}
```

---

## PASO 7 — VERIFICACIÓN COMPLETA

```bash
# 1. Verificar que el landing carga con recursos
curl -s "http://demo-medico.localhost:8000/api/public/agenda/recursos" | python3 -m json.tool

# Debe retornar array con al menos un recurso con servicios y horarios:
# [{"id":1,"nombre":"Dr. Demo","especialidad":"Médico General","servicios":[...],"horarios":[1,2,3,4,5]}]

# 2. Verificar slots disponibles (reemplazar YYYY-MM-DD con una fecha futura L-V)
curl -s "http://demo-medico.localhost:8000/api/public/agenda/slots?recurso_id=1&fecha=2026-03-27&duracion=30" \
  | python3 -m json.tool

# Debe retornar array de slots:
# [{"hora_inicio":"09:00","hora_fin":"09:30"},{"hora_inicio":"09:30","hora_fin":"10:00"},...]

# 3. Verificar que la vista del landing carga sin error 404
curl -I "http://demo-medico.localhost:8000/agenda"
# HTTP/1.1 200 OK

# 4. Si algo falla, ver logs
docker exec benderandos_app sh -c "tail -50 /app/storage/logs/laravel.log"
```

---

## PASO 8 — SEEDER PERMANENTE (para no repetir)

Agregar al `AgendaDemoSeeder` para que se ejecute automáticamente en futuros deployments:

```php
// database/seeders/Tenant/AgendaDemoSeeder.php

public function run(): void
{
    // ... código existente del seeder ...

    // ── GARANTIZAR Config pública ──────────────────────────
    \App\Models\Tenant\AgendaConfig::firstOrCreate([], [
        'titulo_landing'         => 'Agenda tu hora',
        'descripcion_landing'    => 'Reserva tu consulta online. Rápido y sin llamadas.',
        'landing_publico_activo' => true,
        'requiere_telefono'      => true,
        'requiere_email'         => false,
        'color_primario'         => '#00e5a0',
        'recordatorio_activo'    => true,
        'recordatorio_horas_antes'=> 24,
    ]);

    // ── GARANTIZAR al menos un recurso ────────────────────
    if (\App\Models\Tenant\AgendaRecurso::count() === 0) {
        $recurso = \App\Models\Tenant\AgendaRecurso::create([
            'nombre'       => 'Profesional Demo',
            'tipo'         => 'profesional',
            'especialidad' => 'Médico General',
            'color'        => '#3dd9eb',
            'orden'        => 1,
            'activo'       => true,
        ]);

        // Horarios L-V 09-18
        foreach (range(1, 5) as $dia) {
            \App\Models\Tenant\AgendaHorario::create([
                'agenda_recurso_id' => $recurso->id,
                'dia_semana'        => $dia,
                'hora_inicio'       => '09:00',
                'hora_fin'          => '18:00',
                'activo'            => true,
                'duracion_slot_min' => 30,
            ]);
        }

        // Servicios
        foreach ([
            ['Consulta general', 30, 45000],
            ['Control médico',   20, 35000],
            ['Revisión urgente', 15, 55000],
        ] as [$n, $d, $p]) {
            \App\Models\Tenant\AgendaServicio::create([
                'agenda_recurso_id' => $recurso->id,
                'nombre'            => $n,
                'duracion_min'      => $d,
                'precio'            => $p,
                'activo'            => true,
            ]);
        }
    }
}
```

Correr:
```bash
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run \
  'db:seed --class=AgendaDemoSeeder' \
  --tenants=demo-medico,demo-padel,demo-legal"
```

---

## ÁRBOL DE DECISIÓN RÁPIDO

```
¿Carga /agenda sin 404?
  NO → landing_publico_activo = false o null → PASO 2 (crear config)
  SÍ ↓

¿El grid de médicos está vacío?
  SÍ → AgendaRecurso vacío → PASO 2 (crear recursos)
  NO ↓

¿El paso 2 (servicios) está vacío o muestra "Consulta general" hardcoded?
  SÍ → Recursos sin servicios → PASO 2 (crear servicios)
  NO ↓

¿El calendario del paso 3 no muestra slots disponibles?
  SÍ → Una de estas:
    A) Recurso sin horarios → PASO 2 (crear horarios)
    B) Endpoint /api/public/agenda/slots retorna 404 → PASO 3 (agregar ruta)
    C) Endpoint retorna 500 → PASO 4 (método publicSlots tiene error) → ver logs
  NO ↓

¿El paso 4 (confirmar cita) falla?
  SÍ → Ruta POST /api/public/agenda/cita faltante o método publicCrearCita → PASO 3+4
```

---

## COMANDOS DE EJECUCIÓN EN ORDEN

```bash
# 1. Migrar por si acaso hay tablas pendientes
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"

# 2. Crear recursos y config en demo-medico vía tinker (PASO 2 arriba)

# 3. Limpiar caché de rutas y vistas
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 4. Verificar API
curl -s "http://demo-medico.localhost:8000/api/public/agenda/recursos" | head -200

# 5. Verificar landing
open "http://demo-medico.localhost:8000/agenda"
```

---

*BenderAnd ERP · Fix Landing Agenda demo-medico · 2026-03-26*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3*
