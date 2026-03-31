# BENDERAND — H26: FIXES CLÍNICA — AGENDA MÉDICO + RECEPCIONISTA + RELACIONES DEMO
**Antigravity · 2026-03-30 · Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3**

---

## DIAGNÓSTICO: QUÉ ESTÁ ROTO

### Problema 1 — La agenda del médico no muestra citas reales

`clinica_medica.html` y `clinica_onboarding.html` son el mismo archivo Blade renderizado en
`demo-medico.localhost:8000/profesional`. El JS llama a `GET /api/agenda/mi/dia?fecha=...`
pero la vista llega vacía. Causas probables en orden de probabilidad:

1. El tenant `demo-medico` no tiene citas sembradas (`citas` table vacía)
2. El modelo `Cita` no tiene la columna `medico_id` (migración no aplicada en ese tenant)
3. El `AgendaController::miDia()` no existe o no está registrado en `routes/tenant.php`
4. El usuario `Ana Martinez` (rol: operario/medico) no tiene `ProfesionalConfig` creada,
   por lo que `RECURSO_ID = null` en el Blade y el JS hace early-return mostrando el bloque
   amarillo "Agenda no vinculada" — la agenda nunca llama al endpoint

### Problema 2 — La recepcionista no tiene su propia vista

No existe una vista `/recepcionista` o `/admin/agenda` diferente a la del médico.
La recepcionista ve lo mismo que el médico (o peor: no ve nada porque el rol no accede a `/profesional`).
La recepcionista necesita: agenda de TODOS los profesionales, botón "+ Nueva cita", check-in de pacientes, y cobrar.

### Problema 3 — Relaciones rotas en demo-medico

El seeder `TenantDemoDataSeeder` para `demo-medico` probablemente creó:
- Usuario admin sin `ProfesionalConfig`
- Sin citas de ejemplo
- Sin servicios de tipo `honorarios` (solo hay productos genéricos DEMO-001/002/003)
- Sin tabla `notas_clinicas`, `profesionales_config`, `onboarding_progress` (si las migraciones
  del spec M26 no corrieron en ese tenant)

---

## PARTE 1: VERIFICAR Y COMPLETAR MIGRACIONES EN DEMO-MEDICO

```bash
# Verificar qué tablas existen en el schema del tenant demo-medico
docker exec benderandos_app sh -c "cd /app && php artisan tinker --no-interaction << 'EOF'
tenancy()->initialize('demo-medico');
\$tables = DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname = current_schema() ORDER BY tablename\");
echo implode(', ', array_column(\$tables, 'tablename'));
EOF"

# Si faltan tablas del spec M26, correr las migraciones solo en ese tenant
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate --tenants=demo-medico"

# Verificar columnas de tabla citas
docker exec benderandos_app sh -c "cd /app && php artisan tinker --no-interaction << 'EOF'
tenancy()->initialize('demo-medico');
\$cols = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'citas' ORDER BY ordinal_position\");
echo implode(', ', array_column(\$cols, 'column_name'));
EOF"
```

Si `medico_id`, `estado` (nuevo enum) no están en `citas`, las migraciones del spec M26
no corrieron. Correrlas y continuar.

---

## PARTE 2: SEEDER DE DATOS CLÍNICOS REALES PARA DEMO-MEDICO

Crear `database/seeders/ClinicaDemoDataSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClinicaDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Asegurar que los usuarios médicos tienen ProfesionalConfig ─────
        $medicos = DB::table('users')
            ->whereIn('rol', ['medico', 'operario'])
            ->get();

        foreach ($medicos as $m) {
            $existe = DB::table('profesionales_config')
                ->where('usuario_id', $m->id)->exists();
            if (!$existe) {
                DB::table('profesionales_config')->insert([
                    'usuario_id'        => $m->id,
                    'especialidad'      => 'Medicina General',
                    'titulo'            => 'Dra.',
                    'color_agenda'      => '#3dd9eb',
                    'duracion_cita_min' => 30,
                    'intervalo_min'     => 30,
                    'max_citas_dia'     => 16,
                    'visible_portal'    => true,
                    'puede_ver_agenda_global' => false,
                    'horario'           => json_encode([
                        'lunes'    => ['inicio' => '09:00', 'fin' => '18:00', 'activo' => true],
                        'martes'   => ['inicio' => '09:00', 'fin' => '18:00', 'activo' => true],
                        'miercoles'=> ['inicio' => '09:00', 'fin' => '18:00', 'activo' => true],
                        'jueves'   => ['inicio' => '09:00', 'fin' => '18:00', 'activo' => true],
                        'viernes'  => ['inicio' => '09:00', 'fin' => '14:00', 'activo' => true],
                        'sabado'   => ['inicio' => '09:00', 'fin' => '12:00', 'activo' => false],
                        'domingo'  => ['activo' => false],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 2. Asegurar que hay servicios clínicos (tipo=honorarios/servicio) ──
        $serviciosExistentes = DB::table('productos')
            ->whereIn('tipo_producto', ['honorarios', 'servicio'])->count();

        if ($serviciosExistentes === 0) {
            DB::table('productos')->insert([
                [
                    'codigo'        => 'SRV-CONSULTA',
                    'nombre'        => 'Consulta médica general',
                    'tipo_producto' => 'honorarios',
                    'valor_venta'   => 35000,
                    'costo'         => 0,
                    'cantidad'      => 0,
                    'estado'        => 'activo',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'codigo'        => 'SRV-CONTROL',
                    'nombre'        => 'Control médico',
                    'tipo_producto' => 'honorarios',
                    'valor_venta'   => 25000,
                    'costo'         => 0,
                    'cantidad'      => 0,
                    'estado'        => 'activo',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'codigo'        => 'SRV-URGENCIA',
                    'nombre'        => 'Atención de urgencia',
                    'tipo_producto' => 'honorarios',
                    'valor_venta'   => 55000,
                    'costo'         => 0,
                    'cantidad'      => 0,
                    'estado'        => 'activo',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        // ── 3. Asegurar que hay clientes/pacientes ───────────────────────────
        $pacientes = DB::table('clientes')->count();
        if ($pacientes < 3) {
            DB::table('clientes')->insert([
                ['nombre' => 'María González', 'rut' => '15.234.567-8', 'email' => 'maria@demo.cl', 'telefono' => '+56912345678', 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Juan Pérez',     'rut' => '12.345.678-9', 'email' => 'juan@demo.cl',  'telefono' => '+56987654321', 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Ana López',      'rut' => '18.901.234-5', 'email' => 'ana@demo.cl',   'telefono' => '+56911223344', 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Carlos Soto',    'rut' => '16.789.012-3', 'email' => 'carlos@demo.cl','telefono' => '+56955667788', 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Laura Torres',   'rut' => '19.456.789-0', 'email' => 'laura@demo.cl', 'telefono' => '+56933445566', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ── 4. Crear citas de hoy para el médico ─────────────────────────────
        $medicoId  = DB::table('users')->where('rol', 'operario')->value('id')
                  ?? DB::table('users')->where('rol', 'medico')->value('id');
        $servicioId = DB::table('productos')
            ->where('codigo', 'SRV-CONSULTA')->value('id');

        $clientes = DB::table('clientes')->pluck('id')->toArray();
        $hoy = now()->toDateString();

        // Solo crear si no hay citas hoy
        $citasHoy = DB::table('citas')
            ->whereDate('created_at', $hoy)->count();

        if ($citasHoy === 0 && $medicoId && count($clientes) >= 3) {
            $citas = [
                ['hora' => '09:00', 'estado' => 'completada',  'cliente' => $clientes[0]],
                ['hora' => '09:30', 'estado' => 'completada',  'cliente' => $clientes[1]],
                ['hora' => '10:00', 'estado' => 'en_curso',    'cliente' => $clientes[2]],
                ['hora' => '10:30', 'estado' => 'confirmada',  'cliente' => $clientes[0]],
                ['hora' => '11:00', 'estado' => 'confirmada',  'cliente' => $clientes[1]],
                ['hora' => '11:30', 'estado' => 'pendiente',   'cliente' => $clientes[2]],
                ['hora' => '12:00', 'estado' => 'pendiente',   'cliente' => $clientes[3] ?? $clientes[0]],
            ];
            foreach ($citas as $c) {
                DB::table('citas')->insert([
                    'cliente_id'   => $c['cliente'],
                    'medico_id'    => $medicoId,
                    'servicio_id'  => $servicioId,
                    'fecha'        => $hoy,
                    'hora_inicio'  => $c['hora'],
                    'hora_fin'     => date('H:i', strtotime($c['hora'] . ' +30 minutes')),
                    'estado'       => $c['estado'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // ── 5. Onboarding progress inicial ───────────────────────────────────
        $stepsDefault = [
            'crear_cuenta'           => 'completado',
            'agregar_profesionales'  => 'completado',
            'configurar_horarios'    => 'completado',
            'crear_servicios'        => 'completado',
            'primera_cita'           => 'pendiente',
            'configurar_sii'         => 'pendiente',
            'activar_whatsapp'       => 'pendiente',
        ];
        foreach ($stepsDefault as $step => $estado) {
            $existe = DB::table('onboarding_progress')
                ->where('step_id', $step)->exists();
            if (!$existe) {
                DB::table('onboarding_progress')->insert([
                    'step_id'    => $step,
                    'estado'     => $estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
```

**Correr el seeder en demo-medico:**

```bash
docker exec benderandos_app sh -c "cd /app && php artisan tinker --no-interaction << 'EOF'
tenancy()->initialize('demo-medico');
(new \Database\Seeders\ClinicaDemoDataSeeder)->run();
echo 'Seeder OK';
EOF"
```

---

## PARTE 3: FIXES EN EL BLADE DE LA VISTA PROFESIONAL

**Archivo:** `resources/views/tenant/profesional/agenda.blade.php`

El HTML actual (`clinica_medica.html`) tiene estos bugs que impiden que funcione:

### Fix 1 — `RECURSO_ID` hardcodeado como `1` sin verificar que exista ProfesionalConfig

**Problema:** La línea `const RECURSO_ID = 1;` es literal. Si el médico no tiene config,
el sistema igual intenta cargar citas pero el endpoint puede fallar o devolver vacío.
Además, cuando `RECURSO_ID` es `null` debería mostrar el bloque amarillo, pero nunca es null.

**Fix en el Blade:**
```blade
@php
    $profConfig = \App\Models\Tenant\ProfesionalConfig
        ::where('usuario_id', auth()->id())->first();
@endphp

{{-- En el bloque de JS --}}
<script>
const RECURSO_ID    = {{ $profConfig ? $profConfig->id : 'null' }};
const LABEL_CLIENTE = '{{ $labels["cliente"] ?? "Paciente" }}';
const LABEL_CITA    = '{{ $labels["cita"] ?? "Cita" }}';
const PROF_NOMBRE   = '{{ auth()->user()->nombre ?? "Profesional" }}';
const PROF_ESP      = '{{ $profConfig->especialidad ?? "" }}';
</script>
```

### Fix 2 — El nav lateral muestra datos hardcodeados ("Ana Martinez", "Medicina General")

**Problema:** El nombre y especialidad están en el HTML literal, no vienen del backend.

**Fix en el Blade:**
```blade
{{-- Reemplazar el bloque prof-nav-top con datos dinámicos --}}
<div class="prof-nav-top">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
        <span class="prof-dot" style="background:#3b82f6;"></span>
        <span class="prof-nombre">{{ auth()->user()->nombre }}</span>
    </div>
    <div class="prof-esp">{{ $profConfig->especialidad ?? 'Sin especialidad' }}</div>
    <div class="prof-kpis" id="kpisNav" style="margin-top:15px;">
        <div class="kpi-item">
            <span class="kpi-lbl">{{ $labels['cita'] ?? 'Cita' }}s Hoy</span>
            <span class="kpi-val" id="kpiCitasHoy">—</span>
        </div>
        <div class="kpi-item">
            <span class="kpi-lbl">Esta semana</span>
            <span class="kpi-val" id="kpiCitasSem">—</span>
        </div>
        <div class="kpi-item">
            <span class="kpi-lbl">{{ $labels['cliente'] ?? 'Paciente' }}</span>
            <span class="kpi-val" id="kpiPacientes">—</span>
        </div>
    </div>
</div>
```

### Fix 3 — El tab de Pacientes dice "Cliente" literal en el nav

El nav item tiene `Cliente` hardcodeado. Debe ser la etiqueta del rubro.

```blade
{{-- En el link del nav de pacientes --}}
<a onclick="cambiarTab('pacientes')" id="nav-pacientes" class="pni">
    <svg>...</svg>
    {{ $labels['cliente'] ?? 'Pacientes' }}
</a>
```

### Fix 4 — La función `abrirCita()` no existe pero `renderCitaRow` la referencia

El HTML llama `onclick="abrirCita(${c.id})"` pero la función no está definida en ningún
lado del JS. El detalle de la cita al hacer click no abre nada.

**Agregar en el bloque `<script>` al final:**

```javascript
async function abrirCita(id) {
    document.getElementById('profDetalle').classList.remove('cerrado');
    const det = document.getElementById('profDetalle');
    det.innerHTML = '<div style="padding:24px;color:#3a3a55;font-size:12px;">Cargando...</div>';
    try {
        const cita = await api('GET', `/api/agenda/citas/${id}`);
        const c = cita.cita ?? cita;
        det.innerHTML = `
            <div class="det-head">
                <span class="det-head-titulo">${c.paciente_nombre ?? 'Cita'}</span>
                <button onclick="cerrarDetalle()" class="btn-sm-ghost">✕</button>
            </div>
            <div class="det-body" style="padding:14px;">
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
                    <span class="estado-badge sb-${c.estado}">${c.estado}</span>
                    <span style="font-family:var(--mono,monospace);font-size:12px;color:#7878a0;">${c.hora_inicio} – ${c.hora_fin ?? ''}</span>
                </div>
                <div style="font-size:11px;display:flex;flex-direction:column;gap:6px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#3a3a55;">Servicio</span>
                        <span>${c.servicio?.nombre ?? '—'}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#3a3a55;">RUT</span>
                        <span style="font-family:monospace;">${c.paciente_rut ?? '—'}</span>
                    </div>
                    ${c.observaciones_recepcion ? `
                    <div style="padding:8px;background:#18181e;border-radius:8px;font-size:11px;color:#8888a0;font-style:italic;">
                        ${c.observaciones_recepcion}
                    </div>` : ''}
                </div>
                <div class="nf-label">Nota clínica</div>
                <textarea id="notaContenido" class="nf-textarea" rows="4"
                    placeholder="Escribe aquí la nota de esta consulta..."></textarea>
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                    <button onclick="guardarNotaDesdeCita(${c.cliente_id},${id})"
                        class="btn-agregar">Guardar nota</button>
                    ${c.estado === 'confirmada' || c.estado === 'pendiente' ? `
                    <button onclick="cambiarEstadoCita(${id},'en_curso')"
                        class="btn-sm-ghost" style="color:#00c4ff;border-color:rgba(0,196,255,.3);">
                        Iniciar consulta
                    </button>` : ''}
                    ${c.estado === 'en_curso' ? `
                    <button onclick="cambiarEstadoCita(${id},'completada')"
                        class="btn-sm-ghost" style="color:#00e5a0;border-color:rgba(0,229,160,.3);">
                        Completar
                    </button>` : ''}
                </div>
            </div>`;
    } catch(e) {
        det.innerHTML = `<div style="padding:20px;color:#ff3f5b;font-size:12px;">${e.message}</div>`;
    }
}

async function guardarNotaDesdeCita(clienteId, citaId) {
    const txt = document.getElementById('notaContenido').value.trim();
    if (!txt) return;
    try {
        await api('POST', '/api/notas-clinicas', {
            cliente_id: clienteId,
            cita_id:    citaId,
            tipo:       'anamnesis',
            contenido:  txt,
        });
        document.getElementById('notaContenido').value = '';
        // Feedback visual
        const btn = document.querySelector('[onclick*="guardarNotaDesdeCita"]');
        if (btn) { btn.textContent = '✓ Guardada'; setTimeout(() => btn.textContent = 'Guardar nota', 1500); }
    } catch(e) { alert(e.message); }
}

async function cambiarEstadoCita(id, estado) {
    try {
        await api('PUT', `/api/agenda/citas/${id}/estado`, { estado });
        abrirCita(id); // recargar el detalle
        cargarAgendaHoy(); // actualizar la lista
    } catch(e) { alert(e.message); }
}
```

### Fix 5 — Navegación de fechas (← →) falta en la agenda

El header de la agenda solo muestra la fecha pero no permite cambiar de día.

**Reemplazar el div `prof-topbar` de la tab de agenda:**

```javascript
// Dentro de cargarAgendaHoy(), reemplazar el update del título por:
const bar = document.getElementById('tab-agenda').querySelector('.prof-topbar');
bar.innerHTML = `
    <button onclick="cambiarFecha(-1)" class="btn-sm-ghost" style="padding:5px 10px;">←</button>
    <span class="prof-titulo" style="flex:1;text-align:center;" id="agendaTituloFecha">
        ${fmtFecha(fechaAgenda)}
    </span>
    <button onclick="cambiarFecha(1)" class="btn-sm-ghost" style="padding:5px 10px;">→</button>
    <button onclick="irAHoy()" class="btn-sm-ghost" style="font-size:10px;">Hoy</button>`;

function cambiarFecha(delta) {
    fechaAgenda.setDate(fechaAgenda.getDate() + delta);
    cargarAgendaHoy();
}
function irAHoy() {
    fechaAgenda = new Date();
    cargarAgendaHoy();
}
```

---

## PARTE 4: CONTROLADORES QUE FALTAN O ESTÁN INCOMPLETOS

### 4.1 `AgendaController` — métodos faltantes

Verificar que existan estos métodos en `app/Http/Controllers/Tenant/AgendaController.php`:

```php
// GET /api/agenda/mi/dia?fecha=YYYY-MM-DD
public function miDia(Request $request): JsonResponse
{
    $fecha    = $request->query('fecha', today()->toDateString());
    $medicoId = auth('sanctum')->id();

    $citas = Cita::with(['cliente', 'servicio'])
        ->where('medico_id', $medicoId)   // Global Scope también activo, doble seguridad
        ->whereDate('fecha', $fecha)
        ->orderBy('hora_inicio')
        ->get()
        ->map(fn($c) => [
            'id'              => $c->id,
            'hora_inicio'     => substr($c->hora_inicio, 0, 5),
            'hora_fin'        => $c->hora_fin ? substr($c->hora_fin, 0, 5) : null,
            'estado'          => $c->estado,
            'paciente_nombre' => $c->cliente?->nombre ?? 'Paciente',
            'paciente_rut'    => $c->cliente?->rut,
            'cliente_id'      => $c->cliente_id,
            'servicio'        => $c->servicio
                ? ['id' => $c->servicio->id, 'nombre' => $c->servicio->nombre]
                : null,
            'observaciones_recepcion' => $c->observaciones_recepcion,
        ]);

    return response()->json($citas);
}

// GET /api/agenda/citas/{id}
public function showCita(int $id): JsonResponse
{
    $cita = Cita::with(['cliente', 'servicio'])
        ->findOrFail($id);

    // Global Scope ya verifica medico_id — si no es del médico actual, devuelve 404
    return response()->json([
        'cita' => [
            'id'                      => $cita->id,
            'hora_inicio'             => substr($cita->hora_inicio, 0, 5),
            'hora_fin'                => $cita->hora_fin ? substr($cita->hora_fin, 0, 5) : null,
            'estado'                  => $cita->estado,
            'paciente_nombre'         => $cita->cliente?->nombre,
            'paciente_rut'            => $cita->cliente?->rut,
            'cliente_id'              => $cita->cliente_id,
            'servicio'                => $cita->servicio
                ? ['id' => $cita->servicio->id, 'nombre' => $cita->servicio->nombre]
                : null,
            'observaciones_recepcion' => $cita->observaciones_recepcion,
        ],
    ]);
}

// PUT /api/agenda/citas/{id}/estado
public function updateEstadoCita(Request $request, int $id): JsonResponse
{
    $cita = Cita::findOrFail($id);
    $cita->update(['estado' => $request->input('estado')]);
    return response()->json(['ok' => true, 'estado' => $cita->estado]);
}

// GET /api/agenda/mi/recurso
public function miRecurso(): JsonResponse
{
    $config = ProfesionalConfig::where('usuario_id', auth('sanctum')->id())->first();
    if (!$config) {
        return response()->json(['error' => 'sin_config'], 404);
    }
    return response()->json([
        'id'            => $config->id,
        'especialidad'  => $config->especialidad,
        'duracion'      => $config->duracion_cita_min,
        'color'         => $config->color_agenda,
    ]);
}
```

### 4.2 `ProfesionalController` — estadísticas reales

```php
// GET /api/profesional/estadisticas
public function estadisticas(): JsonResponse
{
    $medicoId = auth('sanctum')->id();
    $hoy      = today()->toDateString();

    return response()->json([
        'citas_hoy'             => Cita::where('medico_id', $medicoId)
                                       ->whereDate('fecha', $hoy)->count(),
        'citas_semana'          => Cita::where('medico_id', $medicoId)
                                       ->whereBetween('fecha', [
                                           now()->startOfWeek()->toDateString(),
                                           now()->endOfWeek()->toDateString(),
                                       ])->count(),
        'pacientes_totales'     => Cita::where('medico_id', $medicoId)
                                       ->distinct('cliente_id')->count('cliente_id'),
        'seguimientos_pendientes' => 0, // TODO: implementar cuando existan seguimientos
    ]);
}
```

### 4.3 Rutas — verificar que estén en `routes/tenant.php`

```php
// ── VISTA PROFESIONAL (Blade) ──────────────────────────────────────
Route::get('/profesional', function () {
    $labels    = \App\Services\AgendaLabelService::labels(
        \App\Models\Tenant\RubroConfig::first()?->industria_preset ?? 'medico'
    );
    $profConfig = \App\Models\Tenant\ProfesionalConfig
        ::where('usuario_id', auth()->id())->first();
    return view('tenant.profesional.agenda', compact('labels', 'profConfig'));
})->middleware(['auth', 'role:medico,operario']);

// ── API AGENDA ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/api/agenda/mi/dia',           [AgendaController::class, 'miDia']);
    Route::get('/api/agenda/mi/recurso',       [AgendaController::class, 'miRecurso']);
    Route::get('/api/agenda/citas/{id}',       [AgendaController::class, 'showCita']);
    Route::put('/api/agenda/citas/{id}/estado',[AgendaController::class, 'updateEstadoCita']);
    Route::get('/api/profesional/estadisticas',[ProfesionalController::class, 'estadisticas']);
    Route::get('/api/profesional/pacientes',   [ProfesionalController::class, 'pacientes']);
    Route::get('/api/profesional/pacientes/{id}',         [ProfesionalController::class, 'detallePaciente']);
    Route::get('/api/profesional/pacientes/{id}/historial',[ProfesionalController::class, 'historialPaciente']);
    Route::post('/api/profesional/pacientes/{id}/nota',   [ProfesionalController::class, 'crearNota']);
});
```

---

## PARTE 5: VISTA DE LA RECEPCIONISTA

La recepcionista (`rol=cajero` o `rol=recepcionista`) necesita una vista diferente.
No es el panel del médico. Necesita ver TODA la agenda y crear citas.

### 5.1 Ruta y Blade

```php
// En routes/tenant.php
Route::get('/recepcion', function () {
    $labels       = \App\Services\AgendaLabelService::labels(
        \App\Models\Tenant\RubroConfig::first()?->industria_preset ?? 'medico'
    );
    $profesionales = \App\Models\Tenant\ProfesionalConfig::with('usuario')->get()
        ->map(fn($p) => [
            'id'           => $p->usuario_id,
            'nombre'       => $p->usuario->nombre ?? 'Profesional',
            'especialidad' => $p->especialidad,
            'color'        => $p->color_agenda,
        ]);
    return view('tenant.recepcion.agenda', compact('labels', 'profesionales'));
})->middleware(['auth', 'role:admin,cajero,recepcionista']);
```

**Archivo:** `resources/views/tenant/recepcion/agenda.blade.php`

Este Blade tiene la misma estructura visual (`prof-shell`, CSS idéntico) pero:
- El nav lateral muestra **todos los profesionales** como filtros rápidos (píldoras con colores)
- El área central muestra citas de todos (o filtradas por profesional)
- Tiene un botón "+ Nueva cita" en el topbar
- Al hacer click en una cita, puede: confirmar llegada, cancelar, cobrar
- No muestra el formulario de notas clínicas (eso es solo para el médico)

### 5.2 JS específico de la recepcionista

```javascript
// Variables globales para recepción
const PROFESIONALES = @json($profesionales); // inyectado por Blade
let filtroMedicoId  = null; // null = ver todos

// ── TOPBAR con selector de profesional ──────────────────────────────
function renderTopbarRecepcion() {
    const bar = document.querySelector('.prof-topbar');
    const pills = PROFESIONALES.map(p => `
        <button onclick="filtrarPorMedico(${p.id})" id="pill-${p.id}"
            class="btn-sm-ghost"
            style="border-color:${p.color};color:${p.color};font-size:10px;">
            ${p.nombre}
        </button>`).join('');
    bar.innerHTML = `
        <button onclick="filtrarPorMedico(null)" id="pill-todos"
            class="btn-sm-ghost" style="color:#00e5a0;border-color:rgba(0,229,160,.4);font-size:10px;">
            Todos
        </button>
        ${pills}
        <span class="prof-titulo" style="margin-left:8px;" id="agendaTituloFecha">...</span>
        <span style="flex:1;"></span>
        <button onclick="cambiarFecha(-1)" class="btn-sm-ghost">←</button>
        <button onclick="irAHoy()" class="btn-sm-ghost" style="font-size:10px;">Hoy</button>
        <button onclick="cambiarFecha(1)" class="btn-sm-ghost">→</button>
        <button onclick="abrirModalNuevaCita()"
            style="padding:6px 14px;border-radius:8px;border:none;background:#00e5a0;color:#000;font-size:12px;font-weight:700;cursor:pointer;">
            + Nueva cita
        </button>`;
}

function filtrarPorMedico(id) {
    filtroMedicoId = id;
    // Resaltar pill activo
    document.querySelectorAll('[id^="pill-"]').forEach(p => {
        p.style.fontWeight = '';
        p.style.background = '';
    });
    const activo = document.getElementById(id ? `pill-${id}` : 'pill-todos');
    if (activo) { activo.style.fontWeight = '700'; activo.style.background = 'rgba(255,255,255,.05)'; }
    cargarAgendaRecepcion();
}

// ── CARGAR AGENDA DE TODOS (o filtrada) ─────────────────────────────
async function cargarAgendaRecepcion() {
    const el = document.getElementById('timelineCitas');
    el.innerHTML = '<div style="text-align:center;padding:24px;color:#3a3a55;font-size:12px;">Cargando...</div>';
    try {
        let url = `/api/agenda?fecha=${toISO(fechaAgenda)}`;
        if (filtroMedicoId) url += `&medico_id=${filtroMedicoId}`;
        const data = await api('GET', url);
        const citas = Array.isArray(data) ? data : (data.citas ?? []);
        if (!citas.length) {
            el.innerHTML = `<div style="text-align:center;padding:40px;color:#3a3a55;font-size:13px;">Sin citas para este día.</div>`;
            return;
        }
        // Agrupar por médico si se muestran todos
        if (!filtroMedicoId && PROFESIONALES.length > 1) {
            el.innerHTML = renderCitasAgrupadasPorMedico(citas);
        } else {
            el.innerHTML = citas.map(c => renderCitaRowRecepcion(c)).join('');
        }
    } catch(e) { el.innerHTML = `<div style="color:#ff3f5b;padding:20px;">${e.message}</div>`; }
}

function renderCitasAgrupadasPorMedico(citas) {
    const grupos = {};
    citas.forEach(c => {
        const key = c.medico_id ?? 'sin_medico';
        if (!grupos[key]) grupos[key] = [];
        grupos[key].push(c);
    });
    return Object.entries(grupos).map(([medicoId, citasGrupo]) => {
        const prof = PROFESIONALES.find(p => p.id == medicoId);
        const color = prof?.color ?? '#8888a0';
        const nombre = prof?.nombre ?? 'Sin asignar';
        return `
            <div style="margin-bottom:20px;">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
                    color:${color};padding:4px 0 8px;border-bottom:1px solid rgba(255,255,255,.05);margin-bottom:8px;">
                    ${nombre}
                </div>
                ${citasGrupo.map(c => renderCitaRowRecepcion(c)).join('')}
            </div>`;
    }).join('');
}

function renderCitaRowRecepcion(c) {
    const colores = { pendiente:'#f5c518', confirmada:'#00e5a0', en_curso:'#00c4ff', completada:'#3a3a55', cancelada:'#ff3f5b' };
    const color = colores[c.estado] ?? '#3a3a55';
    // La recepcionista puede hacer check-in (confirmar llegada)
    const btnCheckin = (c.estado === 'confirmada' || c.estado === 'pendiente')
        ? `<button onclick="event.stopPropagation();checkinPaciente(${c.id})"
                style="padding:3px 8px;border-radius:5px;border:none;background:rgba(0,229,160,.15);
                       color:#00e5a0;font-size:10px;cursor:pointer;font-weight:700;margin-left:auto;">
                Llegó</button>`
        : '';
    return `<div class="cita-row" onclick="abrirCitaRecepcion(${c.id})">
        <div class="hora-col">${c.hora_inicio}</div>
        <div class="barra-estado" style="background:${color};"></div>
        <div class="cita-info">
            <div class="ci-nombre">${c.paciente_nombre}</div>
            <div class="ci-srv">${c.servicio?.nombre ?? ''}</div>
        </div>
        <span class="estado-badge sb-${c.estado}">${c.estado}</span>
        ${btnCheckin}
    </div>`;
}

async function checkinPaciente(citaId) {
    try {
        await api('PUT', `/api/agenda/citas/${citaId}/estado`, { estado: 'en_curso' });
        cargarAgendaRecepcion();
    } catch(e) { alert(e.message); }
}

async function abrirCitaRecepcion(id) {
    document.getElementById('profDetalle').classList.remove('cerrado');
    const det = document.getElementById('profDetalle');
    det.innerHTML = '<div style="padding:24px;color:#3a3a55;font-size:12px;">Cargando...</div>';
    try {
        const { cita: c } = await api('GET', `/api/agenda/citas/${id}`);
        det.innerHTML = `
            <div class="det-head">
                <span class="det-head-titulo">${c.paciente_nombre ?? 'Cita'}</span>
                <button onclick="cerrarDetalle()" class="btn-sm-ghost">✕</button>
            </div>
            <div class="det-body" style="padding:14px;">
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <span class="estado-badge sb-${c.estado}">${c.estado}</span>
                    <span style="font-family:monospace;font-size:12px;color:#7878a0;">${c.hora_inicio} – ${c.hora_fin ?? ''}</span>
                </div>
                <div style="font-size:11px;display:flex;flex-direction:column;gap:6px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#3a3a55;">Servicio</span>
                        <span>${c.servicio?.nombre ?? '—'}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#3a3a55;">RUT</span>
                        <span style="font-family:monospace;">${c.paciente_rut ?? '—'}</span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    ${c.estado === 'pendiente' ? `<button onclick="checkinPaciente(${c.id})" class="btn-agregar" style="width:100%;">✓ Registrar llegada</button>` : ''}
                    ${c.estado !== 'completada' && c.estado !== 'cancelada' ? `<button onclick="cancelarCita(${c.id})" class="btn-sm-ghost" style="color:#ff3f5b;border-color:rgba(255,63,91,.3);">Cancelar cita</button>` : ''}
                </div>
            </div>`;
    } catch(e) {
        det.innerHTML = `<div style="padding:20px;color:#ff3f5b;font-size:12px;">${e.message}</div>`;
    }
}

async function cancelarCita(id) {
    if (!confirm('¿Confirmar cancelación?')) return;
    try {
        await api('PUT', `/api/agenda/citas/${id}/estado`, { estado: 'cancelada' });
        cerrarDetalle();
        cargarAgendaRecepcion();
    } catch(e) { alert(e.message); }
}

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    renderTopbarRecepcion();
    cargarAgendaRecepcion();
});
```

### 5.3 `AgendaController::index()` — agenda de todos para recepcionista

```php
// GET /api/agenda?fecha=YYYY-MM-DD&medico_id=X (opcional)
// Solo accesible por admin, cajero, recepcionista
public function index(Request $request): JsonResponse
{
    $fecha    = $request->query('fecha', today()->toDateString());
    $medicoId = $request->query('medico_id');

    // Para admin/recepcionista: desactivar el Global Scope de aislamiento
    $query = Cita::withoutGlobalScope('medico_scope')
        ->with(['cliente', 'servicio'])
        ->whereDate('fecha', $fecha);

    if ($medicoId) {
        $query->where('medico_id', $medicoId);
    }

    $citas = $query->orderBy('medico_id')->orderBy('hora_inicio')->get()
        ->map(fn($c) => [
            'id'              => $c->id,
            'hora_inicio'     => substr($c->hora_inicio, 0, 5),
            'hora_fin'        => $c->hora_fin ? substr($c->hora_fin, 0, 5) : null,
            'estado'          => $c->estado,
            'medico_id'       => $c->medico_id,
            'paciente_nombre' => $c->cliente?->nombre ?? 'Paciente',
            'paciente_rut'    => $c->cliente?->rut,
            'cliente_id'      => $c->cliente_id,
            'servicio'        => $c->servicio
                ? ['id' => $c->servicio->id, 'nombre' => $c->servicio->nombre]
                : null,
        ]);

    return response()->json($citas);
}
```

**Agregar ruta:**
```php
Route::get('/api/agenda', [AgendaController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin,cajero,recepcionista']);
```

---

## PARTE 6: LINKS DE NAVEGACIÓN ENTRE VISTAS

El HTML actual tiene "Volver a Stock" (`/operario`) como único link de salida.
Agregar link a la recepcionista desde el nav de admin:

**En `resources/views/tenant/admin/partials/sidebar.blade.php` (o donde esté el sidebar admin):**
```blade
@if(in_array(auth()->user()->rol ?? '', ['admin', 'cajero', 'recepcionista']))
<a href="/recepcion" class="nav-link-item">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Agenda / Recepción
</a>
@endif
```

**En el nav del profesional, reemplazar "Volver a Stock":**
```blade
@if(auth()->user()->rol === 'admin')
    <a href="/admin/dashboard" class="pni">← Panel admin</a>
@else
    <a href="/operario" class="pni">← Stock</a>
@endif
```

---

## PARTE 7: ORDEN DE EJECUCIÓN

```bash
# 1. Correr migraciones M26 si no están aplicadas
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate"

# 2. Correr el seeder de datos clínicos en demo-medico
docker exec benderandos_app sh -c "cd /app && php artisan tinker --no-interaction << 'EOF'
tenancy()->initialize('demo-medico');
(new \Database\Seeders\ClinicaDemoDataSeeder)->run();
echo 'Seeder OK';
EOF"

# 3. Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 4. Verificar que las citas existen
docker exec benderandos_app sh -c "cd /app && php artisan tinker --no-interaction << 'EOF'
tenancy()->initialize('demo-medico');
echo 'Citas: ' . DB::table('citas')->count();
echo ' | ProfConfig: ' . DB::table('profesionales_config')->count();
echo ' | Servicios: ' . DB::table('productos')->whereIn('tipo_producto',['honorarios','servicio'])->count();
EOF"
```

---

## CRITERIOS DE VERIFICACIÓN

```
[ ] GET demo-medico.localhost:8000/profesional → nav muestra "Ana Martinez" (nombre real del usuario)
[ ] GET demo-medico.localhost:8000/profesional → agenda muestra citas del día (no bloque amarillo)
[ ] Click en una cita → panel derecho abre con detalle + formulario de nota
[ ] Botón "Iniciar consulta" cambia estado de confirmada → en_curso
[ ] KPIs del nav muestran números reales (no "—" eternamente)
[ ] Navegación ← → cambia la fecha y recarga citas
[ ] GET demo-medico.localhost:8000/recepcion → vista diferente con todos los profesionales
[ ] Recepcionista puede hacer "Llegó" en una cita desde su vista
[ ] Recepcionista NO ve el formulario de notas clínicas
[ ] GET /api/agenda/mi/dia con token de médico → solo devuelve sus citas
[ ] GET /api/agenda con token de recepcionista → devuelve todas las citas
[ ] Seeder: DB.citas cuenta > 0 en demo-medico
[ ] Seeder: DB.profesionales_config cuenta > 0 en demo-medico
[ ] Seeder: DB.productos con tipo honorarios cuenta > 0 en demo-medico
```

---

*BenderAnd ERP · H26 Fixes Clínica Demo · 2026-03-30*
*Antigravity implementa este doc sin preguntas adicionales.*
*Ref: clinica_medica.html · clinica_onboarding.html · m26-SPEC_ERP_CLINICA_AGENDA_AISLAMIENTO_2026-03-30.md*
