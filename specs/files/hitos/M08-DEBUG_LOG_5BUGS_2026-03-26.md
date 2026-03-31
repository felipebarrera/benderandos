# DEBUG — 5 Bugs del Log BenderAnd ERP
**Fecha:** 2026-03-26  **Sesión:** demo-medico · /profesional + /pos/agenda + /admin/agenda

---

## MAPA DE ERRORES → BUGS

El log tiene 35 entradas pero son solo **5 bugs distintos**:

| Cantidad | Error | URL | Bug |
|---|---|---|---|
| 18× | `GET /api/profesional/pacientes [500]` | `/profesional` | **Bug A** |
| 1× | `GET /api/agenda/mi/dia [500]` | `/profesional` | **Bug B** |
| 13× | `ReferenceError: buscarPacienteModal is not defined` | `/pos/agenda` | **Bug C** |
| 1× | `GET /api/agenda/slots [404]` | `/pos/agenda` | **Bug D** |
| 2× | `JSON.parse: unexpected character` | `/admin/agenda` | **Bug E** |

---

## BUG A — `GET /api/profesional/pacientes [500]` (18 ocurrencias)

**Causa raíz:** `ProfesionalController::pacientes()` llama `SeguimientoPaciente`
pero la tabla `seguimiento_paciente` todavía no existe en el schema del tenant.
La migración `create_seguimiento_paciente_table` se creó pero no se ejecutó.

También ocurre en `?pendiente_seguimiento=1`: ese branch filtra por
`SeguimientoPaciente::where('usuario_id', ...)` que explota si la tabla no existe.

**Verificar:**
```bash
docker exec benderandos_app sh -c "cd /app && php artisan tinker --execute=\"
tenancy()->initialize(App\\\Models\\\Central\\\Tenant::find('demo-medico'));
echo \\\DB::select(\\\"SELECT to_regclass('seguimiento_paciente') AS t\\\")[0]->t ?? 'NULL';
\""
# Si imprime NULL → tabla no existe → ejecutar la migración
```

**Fix — ejecutar la migración pendiente:**
```bash
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"
```

**Fix adicional — null-safe en el controller mientras la tabla no exista:**

Si por alguna razón la migración falla o se quiere proteger el endpoint,
agregar try/catch en `ProfesionalController::pacientes()`:

```php
public function pacientes(Request $request)
{
    $usuario = auth()->user();
    $q       = $request->query('q');
    $pendiente = $request->boolean('pendiente_seguimiento');

    $recurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', $usuario->id)
        ->where('activo', true)
        ->first();

    if (!$recurso) {
        return response()->json([]);
    }

    // Obtener IDs de clientes con citas previas con este profesional
    $clienteIds = \App\Models\Tenant\AgendaCita::where('agenda_recurso_id', $recurso->id)
        ->whereNotNull('cliente_id')
        ->distinct()
        ->pluck('cliente_id');

    $query = \App\Models\Tenant\Cliente::whereIn('id', $clienteIds);

    if ($q) {
        $query->where(function($sq) use ($q) {
            $sq->where('nombre', 'ilike', "%{$q}%")
               ->orWhere('rut',   'ilike', "%{$q}%");
        });
    }

    // Seguimientos — null-safe en caso de tabla no existente todavía
    $pendienteIds = collect();
    try {
        $pendienteIds = \App\Models\Tenant\SeguimientoPaciente::where('usuario_id', $usuario->id)
            ->where('resuelto', false)
            ->whereNotNull('fecha_seguimiento')
            ->pluck('cliente_id')
            ->unique();
    } catch (\Throwable $e) {
        // Tabla aún no existe — devolver sin seguimientos
    }

    // Si filtrando por pendiente_seguimiento, limitar a esos IDs
    if ($pendiente && $pendienteIds->isNotEmpty()) {
        $query->whereIn('id', $pendienteIds);
    } elseif ($pendiente) {
        // No hay seguimientos pendientes → array vacío
        return response()->json([]);
    }

    $pacientes = $query->orderBy('nombre')->limit(50)->get()
        ->map(fn($p) => array_merge($p->toArray(), [
            'pendiente_seguimiento' => $pendienteIds->contains($p->id)
                ? 'Seguimiento pendiente' : null,
        ]));

    return response()->json($pacientes);
}
```

---

## BUG B — `GET /api/agenda/mi/dia [500]` (1 ocurrencia)

**Causa raíz:** En el spec `SPEC_M08_AGENDA_RECURSOS_AUTO`, el método `miDia()`
llama `getAgendaDia($fecha, [$recurso->id])` — pasando un **array** con el ID.
Pero `AgendaService::getAgendaDia()` espera `?int` en el segundo parámetro.

```php
// SPEC dice esto — genera el 500:
return response()->json($this->agendaService->getAgendaDia($fecha, [$recurso->id]));
//                                                                   ↑ array!

// AgendaService espera:
public function getAgendaDia(string $fecha, ?int $recursoId): array
```

**Fix — `app/Http/Controllers/Tenant/AgendaController.php`, método `miDia()`:**

```php
public function miDia(Request $r)
{
    $usuario = auth()->user();
    $fecha   = $r->query('fecha', today()->toDateString());

    $recurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', $usuario->id)
        ->where('activo', true)
        ->first();

    if (!$recurso) {
        return response()->json([]);
    }

    // Pasar (int) — nunca el array
    return response()->json(
        $this->agendaService->getAgendaDia($fecha, (int) $recurso->id)
    );
}
```

Lo mismo aplica a `miSemana()` — también pasa array en el spec:

```php
public function miSemana(Request $r)
{
    $usuario  = auth()->user();
    $recurso  = \App\Models\Tenant\AgendaRecurso::where('usuario_id', $usuario->id)
        ->where('activo', true)->first();

    if (!$recurso) return response()->json([]);

    $fechaRef = $r->query('fecha', today()->toDateString());
    $inicio   = \Carbon\Carbon::parse($fechaRef)->startOfWeek();
    $dias     = [];

    for ($i = 0; $i < 7; $i++) {
        $f      = $inicio->copy()->addDays($i)->toDateString();
        $dias[] = [
            'fecha' => $f,
            // (int) — no array
            'citas' => $this->agendaService->getAgendaDia($f, (int) $recurso->id),
        ];
    }

    return response()->json($dias);
}
```

---

## BUG C — `ReferenceError: buscarPacienteModal is not defined` (13 ocurrencias)

**URL:** `/pos/agenda`  
**Causa raíz:** La vista `pos/agenda` (la agenda del admin/recepcionista, distinta de `/profesional`)
tiene un input con `oninput="buscarPacienteModal(this.value)"` en el HTML del modal de nueva cita,
pero la función `buscarPacienteModal()` solo existe en la vista `/profesional/index.blade.php`
— no fue incluida en `pos/agenda`.

El modal de nueva cita en `/pos/agenda` usa un input de búsqueda de paciente que llama
a una función que no está definida en ese contexto.

**Fix — en la vista `resources/views/tenant/pos/agenda.blade.php` (o donde está el modal):**

Agregar la función al bloque `<script>` de esa vista. Es la misma función del spec:

```javascript
// Agregar dentro del <script> de pos/agenda.blade.php

async function buscarPacienteModal(q) {
    const el = document.getElementById('mcResultados');
    if (!q || q.length < 2) { el.innerHTML = ''; return; }
    try {
        const data = await api('GET', `/api/clientes?q=${encodeURIComponent(q)}&limit=5`);
        const lista = data.data ?? data;
        el.innerHTML = lista.slice(0, 5).map(p => `
            <div onclick="seleccionarPacienteModal(${p.id}, '${p.nombre.replace(/'/g,"\\'")}', '${p.telefono ?? ''}')"
                 style="padding:8px 10px;background:#18181e;border:1px solid #2a2a3a;border-radius:7px;
                        cursor:pointer;margin-bottom:4px;font-size:12px;color:#e8e8f0;">
                ${p.nombre}
                ${p.rut ? `· <span style="color:#7878a0;font-family:monospace;">${p.rut}</span>` : ''}
            </div>`).join('') || '<div style="font-size:11px;color:#3a3a55;">Sin resultados</div>';
    } catch(e) { console.error(e); }
}

function seleccionarPacienteModal(id, nombre, tel) {
    const elId  = document.getElementById('mcClienteId');
    const elNom = document.getElementById('mcNombre');
    const elTel = document.getElementById('mcTelefono');
    const elRes = document.getElementById('mcResultados');
    const elBus = document.getElementById('mcBusca');
    if (elId)  elId.value  = id;
    if (elNom) elNom.value = nombre;
    if (elTel) elTel.value = tel;
    if (elRes) elRes.innerHTML = '';
    if (elBus) elBus.value = '';
}
```

**Verificar que el input en el modal usa el ID correcto:**

```bash
docker exec benderandos_app sh -c "grep -n 'buscarPacienteModal\|mcBusca\|mcResultados' \
  /app/resources/views/tenant/pos/agenda.blade.php"
```

Si el input tiene `oninput="buscarPacienteModal(this.value)"` pero el input no tiene
`id="mcBusca"`, la función `seleccionarPacienteModal` falla al limpiar el campo.
Asegurarse de que el input tenga ambos: `id="mcBusca"` y `oninput="buscarPacienteModal(this.value)"`.

---

## BUG D — `GET /api/agenda/slots?recurso_id=1&fecha=2026-03-26&duracion=30 [404]`

**URL:** `/pos/agenda`  
**Causa raíz:** El endpoint `GET /api/agenda/slots` no está registrado en `routes/tenant.php`.
La vista de agenda usa este endpoint para mostrar los slots disponibles al crear una cita,
pero la ruta no fue implementada.

**Fix — agregar el endpoint en `routes/tenant.php`:**

```php
// Dentro del grupo check.module:M08
Route::get('/api/agenda/slots', [AgendaController::class, 'getSlots']);
```

**Fix — agregar el método en `AgendaController`:**

```php
/**
 * GET /api/agenda/slots?recurso_id=&fecha=&duracion=
 * Devuelve los slots libres de un recurso para una fecha y duración dada.
 */
public function getSlots(Request $request)
{
    $request->validate([
        'recurso_id' => 'required|integer',
        'fecha'      => 'required|date',
        'duracion'   => 'nullable|integer|min:5|max:480',
    ]);

    $recurso = \App\Models\Tenant\AgendaRecurso::with(['horarios'])
        ->where('id', $request->recurso_id)
        ->where('activo', true)
        ->firstOrFail();

    $fecha    = $request->fecha;
    $duracion = (int) ($request->duracion ?? 30);

    // Obtener horario del día de la semana (1=lunes ... 7=domingo, Carbon)
    $diaSemana = \Carbon\Carbon::parse($fecha)->dayOfWeekIso; // 1-7
    $horario   = $recurso->horarios->firstWhere('dia_semana', $diaSemana);

    if (!$horario || !$horario->activo) {
        return response()->json([]);
    }

    // Obtener citas confirmadas/en_curso del día para este recurso
    $citasOcupadas = \App\Models\Tenant\AgendaCita::where('agenda_recurso_id', $recurso->id)
        ->where('fecha', $fecha)
        ->whereNotIn('estado', ['cancelada'])
        ->get(['hora_inicio', 'hora_fin']);

    // Generar todos los slots del día
    $slots    = [];
    $durSlot  = $horario->duracion_slot_min ?? $duracion;
    $current  = \Carbon\Carbon::createFromFormat('H:i', $horario->hora_inicio);
    $fin      = \Carbon\Carbon::createFromFormat('H:i', $horario->hora_fin);
    $finCita  = $current->copy()->addMinutes($duracion);

    while ($finCita->lessThanOrEqualTo($fin)) {
        $inicio  = $current->format('H:i');
        $finStr  = $finCita->format('H:i');

        // Verificar si el slot choca con alguna cita ocupada
        $ocupado = $citasOcupadas->first(function($cita) use ($inicio, $finStr) {
            return $cita->hora_inicio < $finStr && $cita->hora_fin > $inicio;
        });

        $slots[] = [
            'hora_inicio' => $inicio,
            'hora_fin'    => $finStr,
            'disponible'  => $ocupado === null,
        ];

        $current->addMinutes($durSlot);
        $finCita = $current->copy()->addMinutes($duracion);
    }

    return response()->json($slots);
}
```

---

## BUG E — `JSON.parse: unexpected character at line 1 column 1` (2 ocurrencias)

**URL:** `/admin/agenda`  
**Causa raíz:** Un endpoint de la vista admin de agenda devuelve HTML en vez de JSON
(típicamente la página de error 404 o 500 de Laravel, o una redirección 302 con HTML).
Cuando el JS hace `JSON.parse(response)` sobre ese HTML, explota.

El `unhandledrejection` sin stack indica que el `catch` del Promise no maneja
correctamente la respuesta no-JSON.

**Verificar qué endpoint falla en `/admin/agenda`:**
```bash
docker exec benderandos_app sh -c "tail -50 /app/storage/logs/laravel.log | grep -A3 'admin/agenda'"
```

Los candidatos más probables son `GET /api/agenda/recursos` o `GET /api/agenda/config`
llamados al cargar la vista admin — si alguno devuelve 404 o 500, el JS recibe HTML.

**Fix 1 — en `benderand.js` o en la función `api()` que hace los fetch:**

Verificar que la función `api()` valida `Content-Type` antes de parsear:

```javascript
// En benderand.js — función api() existente, agregar validación de Content-Type
async function api(method, url, data) {
    const opts = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',    // ← CRÍTICO: fuerza JSON en respuesta
            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
    };
    if (data) opts.body = JSON.stringify(data);

    const res = await fetch(url, opts);

    // Verificar que la respuesta es JSON antes de parsear
    const contentType = res.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
        // Respuesta HTML inesperada (error Laravel, redirect, etc.)
        const text = await res.text();
        throw new Error(`Respuesta no-JSON [${res.status}]: ${text.substring(0, 100)}`);
    }

    const json = await res.json();
    if (!res.ok) throw new Error(json.message ?? json.error ?? `Error ${res.status}`);
    return json;
}
```

**Fix 2 — verificar que todos los endpoints de `/admin/agenda` existen:**

```bash
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/agenda 2>&1 | head -30"
```

Si falta algún endpoint que la vista admin llama al cargar, agregar la ruta.
Los más comunes en la vista admin de agenda son:
- `GET /api/agenda/recursos`
- `GET /api/agenda/config`
- `GET /api/agenda/citas?fecha=`

---

## COMANDOS EN ORDEN (ejecutar en secuencia)

```bash
# 1. Ejecutar migraciones pendientes — resuelve Bug A inmediatamente
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"

# 2. Verificar que seguimiento_paciente existe ahora
docker exec benderandos_app sh -c "cd /app && php artisan tinker --execute=\"
tenancy()->initialize(App\\\Models\\\Central\\\Tenant::find('demo-medico'));
echo \\\DB::select(\\\"SELECT to_regclass('seguimiento_paciente') AS t\\\")[0]->t;
\""
# Debe imprimir: seguimiento_paciente

# 3. Ver logs para confirmar Bug B (array vs int)
docker exec benderandos_app sh -c "tail -20 /app/storage/logs/laravel.log | grep -A5 'Argument #2'"

# 4. Verificar qué endpoint falla en admin/agenda (Bug E)
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/agenda 2>&1"

# 5. Limpiar caché tras los fixes
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"
```

---

## RESUMEN PRIORIZADO

| Prioridad | Bug | Fix | Archivo |
|---|---|---|---|
| 🔴 1 | A: `/api/profesional/pacientes` 500 | Ejecutar `tenants:migrate` | Migración pendiente |
| 🔴 2 | B: `/api/agenda/mi/dia` 500 | `(int) $recurso->id` — no array | `AgendaController::miDia()` |
| 🟡 3 | C: `buscarPacienteModal` not defined | Agregar función al JS de `pos/agenda` | `pos/agenda.blade.php` |
| 🟡 4 | D: `/api/agenda/slots` 404 | Agregar ruta + método `getSlots()` | `routes/tenant.php` + `AgendaController` |
| 🟡 5 | E: JSON.parse HTML | `Accept: application/json` en `api()` | `benderand.js` |

---

*BenderAnd ERP · Debug 5 Bugs Log 2026-03-26*
