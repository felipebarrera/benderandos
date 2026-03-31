<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\{AgendaCita, AgendaRecurso, AgendaServicio, AgendaConfig, AgendaHorario, AgendaBloqueo};
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function __construct(private AgendaService $svc)
    {
    }

    // ── VISTAS ──────────────────────────────────────────────────
    public function posIndex()
    {
        $rol = auth()->user()->rol->slug ?? 'operario';

        // Redirección H26: Cajero no ve agenda general
        if ($rol === 'cajero') {
            return redirect()->route('pos.index')->with('error', 'No tienes permisos para acceder a la agenda.');
        }

        return view('tenant.pos.agenda', [
            'rol' => $rol,
            'title' => 'Agenda de Citas'
        ]);
    }
    public function adminIndex()
    {
        return view('tenant.admin.agenda.index');
    }

    public function adminCitasIndex()
    {
        return view('tenant.admin.agenda.citas');
    }

    /**
     * GET /api/agenda/disponibilidad?recurso_id=&fecha=&hora_inicio=&hora_fin=
     * Check if a time slot is available for a resource.
     */
    public function disponibilidad(Request $req)
    {
        $req->validate([
            'recurso_id'  => 'required|integer',
            'fecha'       => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin'    => 'required',
        ]);

        $conflicto = AgendaCita::where('agenda_recurso_id', $req->recurso_id)
            ->where('fecha', $req->fecha)
            ->whereNotIn('estado', ['cancelada'])
            ->where('hora_inicio', '<', $req->hora_fin)
            ->where('hora_fin', '>', $req->hora_inicio)
            ->exists();

        return response()->json([
            'disponible' => !$conflicto,
            'conflicto'  => $conflicto,
        ]);
    }
    public function landing()
    {
        $config = AgendaConfig::first();

        // Auto-crear config si no existe
        if (!$config) {
            $config = AgendaConfig::create([
                'titulo_landing'         => 'Agenda tu hora',
                'descripcion_landing'    => 'Selecciona un profesional y horario para tu atención.',
                'landing_publico_activo' => true,
                'requiere_telefono'      => true,
                'requiere_email'         => false,
                'color_primario'         => '#00e5a0',
                'recordatorio_activo'    => true,
                'recordatorio_horas_antes' => 24,
            ]);
        }

        if (!$config->landing_publico_activo) {
            abort(404, 'El sistema de agenda no está disponible.');
        }

        $recursos = AgendaRecurso::with(['servicios', 'horarios'])
            ->where('activo', true)->orderBy('orden')->get();
        return view('public.agenda', compact('config', 'recursos'));
    }

    // ── API: AGENDA DEL DÍA (POS) ───────────────────────────────
    public function getDia(Request $req)
    {
        $fecha = $req->query('fecha', today()->toDateString());
        return response()->json($this->svc->getAgendaDia($fecha));
    }

    public function calendario(Request $req)
    {
        $req->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date'
        ]);

        $query = AgendaCita::with(['servicio', 'recurso'])
            ->whereBetween('fecha', [$req->start_date, $req->end_date])
            ->where('estado', '!=', 'cancelada');

        if ($req->recurso_id) {
            $query->where('agenda_recurso_id', (int)$req->recurso_id);
        }

        $citas = $query->orderBy('fecha')->orderBy('hora_inicio')->get();

        return response()->json($citas->groupBy(fn($c) => $c->fecha->toDateString()));
    }

    //* ── API: SLOTS (POS/ADMIN) ──────────────────────────────────
    /*
     public function getSlots(Request $req, int $recursoId)
     {
     $fecha = Carbon::parse($req->query('fecha', today()));
     return response()->json($this->svc->getSlotsDisponibles($recursoId, $fecha, $req->servicio_id));
     }*/

    // ── API: CITAS (CRUD) ───────────────────────────────────────
    public function crearCita(Request $req)
    {
        $req->validate([
            'agenda_recurso_id' => 'required|exists:agenda_recursos,id',
            'paciente_nombre' => 'required|string',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required'
        ]);

        try {
            $cita = $this->svc->crearCita(array_merge($req->all(), ['creado_por' => auth()->id()]));
            return response()->json($cita->load('recurso', 'servicio'), 201);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function cambiarEstado(Request $req, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        try {
            $cita = $this->svc->cambiarEstado($cita, $req->estado, auth()->id());
            return response()->json($cita);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function actualizarCita(Request $req, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $cita->update($req->only(['paciente_nombre', 'paciente_rut', 'paciente_telefono', 'paciente_email', 'notas_internas']));
        return response()->json($cita);
    }

    public function show(int $id)
    {
        $cita = AgendaCita::with(['recurso', 'servicio', 'cliente', 'venta.tipoPago'])->findOrFail($id);
        return response()->json($cita);
    }

    public function cancelarCita(Request $req, int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        $cita->update([
            'estado' => 'cancelada',
            'cancelado_por' => auth()->id(),
            'notas_internas' => $cita->notas_internas . "\nCancelada: " . $req->motivo
        ]);
        return response()->json($cita);
    }

    // ── API: RECURSOS Y HORARIOS ────────────────────────────────
    public function getRecursos()
    {
        return response()->json(AgendaRecurso::with('horarios', 'servicios')->orderBy('orden')->get());
    }

    public function crearRecurso(Request $req)
    {
        $recurso = AgendaRecurso::create($req->all());
        return response()->json($recurso, 201);
    }

    public function actualizarHorarios(Request $req, int $id)
    {
        $req->validate(['horarios' => 'required|array']);
        AgendaHorario::where('agenda_recurso_id', $id)->delete();
        foreach ($req->horarios as $h) {
            AgendaHorario::create(array_merge($h, ['agenda_recurso_id' => $id]));
        }
        return response()->json(['ok' => true]);
    }

    /** POST /api/agenda/recursos/{id}/servicios */
    public function crearServicio(Request $r, int $recursoId)
    {
        $recurso = AgendaRecurso::findOrFail($recursoId);
        $r->validate([
            'nombre' => 'required|string|max:100',
            'duracion_min' => 'required|integer|min:5',
            'precio' => 'required|integer|min:0',
        ]);
        $srv = $recurso->servicios()->create($r->only(['nombre', 'duracion_min', 'precio']));
        return response()->json($srv, 201);
    }

    // ── API: CONFIG ─────────────────────────────────────────────
    public function getConfig()
    {
        return response()->json(AgendaConfig::firstOrCreate([]));
    }

    public function updateConfig(Request $req)
    {
        $config = AgendaConfig::firstOrCreate([]);
        $config->update($req->only([
            'titulo_landing', 'descripcion_landing', 'landing_publico_activo',
            'confirmacion_wa_activa', 'recordatorio_activo', 'recordatorio_horas_antes',
            'requiere_telefono', 'requiere_email', 'color_primario',
        ]));
        return response()->json($config);
    }

    public function publicSlots(Request $req)
    {
        $recursoId = $req->query('recurso_id') ?? $req->query('agenda_recurso_id');
        $fecha = $req->query('fecha');
        
        $fechaInicio = $req->query('fecha_inicio', $fecha);
        $fechaFin = $req->query('fecha_fin', $fecha);

        if (!$fechaInicio || !$fechaFin) {
            return response()->json(['error' => 'Faltan parámetros de fecha'], 422);
        }

        $query = AgendaRecurso::with('horarios')->where('activo', true);
        if ($recursoId) {
            $query->where('id', (int) $recursoId);
        }
        $recursos = $query->get();

        $respuestaData = [];

        $fIni = Carbon::parse($fechaInicio);
        $fFin = Carbon::parse($fechaFin);

        for ($d = $fIni->copy(); $d->lte($fFin); $d->addDay()) {
            $fechaStr = $d->toDateString();
            $diaSemana = $d->dayOfWeekIso; // 1=Lun … 7=Dom
            $esHoy = $d->isToday();
            $ahora = Carbon::now();

            foreach ($recursos as $recurso) {
                if (!isset($respuestaData[$recurso->id])) {
                    $respuestaData[$recurso->id] = [];
                }
                $respuestaData[$recurso->id][$fechaStr] = [];

                $horario = $recurso->horarios->where('dia_semana', $diaSemana)->where('activo', true)->first();
                if (!$horario) continue;

                $duracion = $horario->duracion_slot_min ?? 30;

                $citasOcupadas = AgendaCita::where('agenda_recurso_id', $recurso->id)
                    ->where('fecha', $fechaStr)
                    ->whereNotIn('estado', ['cancelada'])
                    ->get(['hora_inicio', 'hora_fin']);

                $bloqueos = AgendaBloqueo::where('agenda_recurso_id', $recurso->id)
                    ->where('fecha_inicio', '<=', $fechaStr)
                    ->where('fecha_fin', '>=', $fechaStr)
                    ->get(['hora_inicio', 'hora_fin']);

                $current = Carbon::createFromFormat('Y-m-d H:i:s', $fechaStr . ' ' . $horario->hora_inicio);
                $fin = Carbon::createFromFormat('Y-m-d H:i:s', $fechaStr . ' ' . $horario->hora_fin);

                while ($current->copy()->addMinutes($duracion)->lessThanOrEqualTo($fin)) {
                    $slotIni = $current->format('H:i');
                    $slotFin = $current->copy()->addMinutes($duracion)->format('H:i');

                    // Saltar slots pasados si es hoy
                    if ($esHoy && $current->lessThanOrEqualTo($ahora)) {
                        $current->addMinutes($duracion);
                        continue;
                    }

                    $ocupado = $citasOcupadas->first(fn($c) => $c->hora_inicio < $slotFin.":00" && $c->hora_fin > $slotIni.":00");
                    $bloqueado = $bloqueos->first(function ($b) use ($slotIni, $slotFin) {
                        if (!$b->hora_inicio) return true;
                        return $b->hora_inicio < $slotFin.":00" && $b->hora_fin > $slotIni.":00";
                    });

                    if (!$ocupado && !$bloqueado) {
                        $respuestaData[$recurso->id][$fechaStr][] = [
                            'hora'        => $slotIni,
                            'hora_inicio' => $slotIni,
                            'hora_fin'    => $slotFin,
                            'disponible'  => true,
                        ];
                    }

                    $current->addMinutes($duracion);
                }
            }
        }

        // Compatibilidad hacia atrás: Si pasaron un solo recurso y fecha y NO fecha_inicio/fin específicos
        if ($recursoId && $fecha && !($req->has('fecha_inicio') && $req->has('fecha_fin'))) {
            return response()->json($respuestaData[$recursoId][$fecha] ?? []);
        }

        return response()->json(['data' => $respuestaData]);
    }

    public function publicCrearCita(Request $req)
    {
        $req->validate([
            'agenda_recurso_id' => 'required',
            'paciente_nombre' => 'required',
            'paciente_telefono' => 'required',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required'
        ]);

        // Verificar disponibilidad
        $conflicto = AgendaCita::where('agenda_recurso_id', $req->agenda_recurso_id)
            ->where('fecha', $req->fecha)
            ->whereNotIn('estado', ['cancelada'])
            ->where('hora_inicio', '<', $req->hora_fin)
            ->where('hora_fin', '>', $req->hora_inicio)
            ->exists();

        if ($conflicto) {
            return response()->json([
                'error' => 'El horario seleccionado ya no está disponible. Por favor elige otro.'
            ], 409);
        }

        try {
            $datosCita = [
                'agenda_recurso_id'  => $req->agenda_recurso_id,
                'agenda_servicio_id' => $req->servicio_id ?? $req->agenda_servicio_id,
                'paciente_nombre'    => $req->paciente_nombre,
                'paciente_telefono'  => $req->paciente_telefono,
                'paciente_email'     => $req->paciente_email,
                'paciente_rut'       => $req->paciente_rut,
                'fecha'              => $req->fecha,
                'hora_inicio'        => $req->hora_inicio,
                'hora_fin'           => $req->hora_fin,
                'notas_paciente'     => $req->notas_internas ?? $req->notas_paciente,
                'origen'             => 'landing',
            ];

            $cita = $this->svc->crearCita($datosCita);
            return response()->json(['uuid' => $cita->uuid, 'cita' => $cita], 201);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** GET /api/public/agenda/recursos — Lista recursos públicos para el landing */
    public function publicRecursos()
    {
        $recursos = AgendaRecurso::with(['servicios', 'horarios'])
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'nombre'       => $r->nombre,
                'especialidad' => $r->especialidad,
                'color'        => $r->color,
                'tipo'         => $r->tipo,
                'servicios'    => $r->servicios->map(fn($s) => [
                    'id'           => $s->id,
                    'nombre'       => $s->nombre,
                    'duracion_min' => $s->duracion_min,
                    'precio'       => $s->precio,
                ]),
                'horarios'     => $r->horarios->where('activo', true)->pluck('dia_semana')->values(),
            ]);
        return response()->json($recursos);
    }

    /**
     * GET /api/agenda/paciente/{clienteId}/historial
     * Historial de citas del paciente.
     */
    public function historialPaciente(int $clienteId)
    {
        $usuario = auth()->user();
        $esProfesional = AgendaRecurso::where('usuario_id', $usuario->id)->exists();

        $citas = AgendaCita::where('cliente_id', $clienteId)
            ->with(['recurso:id,nombre,especialidad', 'servicio:id,nombre'])
            ->orderByDesc('fecha')
            ->get();

        if (!$esProfesional && !in_array($usuario->rol, ['admin', 'super_admin'])) {
            $citas->each(function(\App\Models\Tenant\AgendaCita $c) { $c->makeHidden('notas_internas'); });
        }

        return response()->json($citas);
    }

    public function iniciarConsulta(int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        try {
            $cita = $this->svc->iniciarConsulta($cita, auth()->id());
            return response()->json($cita);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function completarCita(int $id)
    {
        $cita = AgendaCita::findOrFail($id);
        try {
            $cita = $this->svc->completarCita($cita, auth()->id());
            return response()->json($cita);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function eliminarRecurso(int $id)
    {
        $recurso = AgendaRecurso::findOrFail($id);
        $recurso->delete();
        return response()->json(['ok' => true]);
    }

    public function eliminarServicio(int $id)
    {
        $srv = AgendaServicio::findOrFail($id);
        $srv->delete();
        return response()->json(['ok' => true]);
    }

    // ── PANEL PERSONAL DEL OPERARIO ─────────────────────────────────────

    /**
     * GET /pos/mi-agenda — Vista Blade personal del operario
     */
    public function miAgendaIndex()
    {
        $usuario = auth()->user();
        $recurso = AgendaRecurso::where('usuario_id', $usuario->id)->first();

        // Si no tiene recurso todavía, crearlo automáticamente
        if (!$recurso) {
            /** @var \App\Models\Tenant\Usuario $usuario */
            $recurso = app(\App\Services\AgendaAutoRegistroService::class)->registrarOperario($usuario);
        }

        return view('tenant.pos.mi-agenda', compact('recurso', 'usuario'));
    }

    /**
     * GET /api/agenda/mi/recurso — El AgendaRecurso del usuario logueado
     */
    public function miRecurso()
    {
        $usuario = auth()->user();
        $recurso = AgendaRecurso::with(['horarios', 'servicios'])
            ->where('usuario_id', $usuario->id)
            ->first();

        if (!$recurso) {
            /** @var \App\Models\Tenant\Usuario $usuario */
            $recurso = app(\App\Services\AgendaAutoRegistroService::class)->registrarOperario($usuario);
            $recurso->load(['horarios', 'servicios']);
        }

        return response()->json($recurso);
    }

    /**
     * GET /api/agenda/slots?recurso_id=&fecha=&duracion=
     * Devuelve los slots libres de un recurso para una fecha y duración dada.
     */
    public function getSlots(Request $request)
    {
        $request->validate([
            'recurso_id' => 'required|integer',
            'fecha' => 'required|date',
            'duracion' => 'nullable|integer|min:5|max:480',
        ]);

        $recurso = \App\Models\Tenant\AgendaRecurso::with(['horarios'])
            ->where('id', (int)$request->recurso_id)
            ->where('activo', true)
            ->firstOrFail();

        $fecha = $request->fecha;
        $duracion = (int)($request->duracion ?? 30);
        $diaSemana = \Carbon\Carbon::parse($fecha)->dayOfWeekIso; // 1-7
        $horario = $recurso->horarios->firstWhere('dia_semana', $diaSemana);

        if (!$horario || !$horario->activo)
            return response()->json([]);

        $citasOcupadas = \App\Models\Tenant\AgendaCita::where('agenda_recurso_id', $recurso->id)
            ->where('fecha', $fecha)
            ->whereNotIn('estado', ['cancelada'])
            ->get(['hora_inicio', 'hora_fin']);

        $slots = [];
        $durSlot = $horario->duracion_slot_min ?? $duracion;
        $current = \Carbon\Carbon::createFromFormat('H:i', $horario->hora_inicio);
        $fin = \Carbon\Carbon::createFromFormat('H:i', $horario->hora_fin);
        $finCita = $current->copy()->addMinutes($duracion);

        while ($finCita->lessThanOrEqualTo($fin)) {
            $inicio = $current->format('H:i');
            $finStr = $finCita->format('H:i');
            $ocupado = $citasOcupadas->first(fn($c) => $c->hora_inicio < $finStr && $c->hora_fin > $inicio);

            $slots[] = [
                'hora_inicio' => $inicio,
                'hora_fin' => $finStr,
                'disponible' => !$ocupado,
            ];

            $current->addMinutes($durSlot);
            $finCita = $current->copy()->addMinutes($duracion);
        }

        return response()->json($slots);
    }

    /**
     * GET /api/agenda/mi/dia?fecha=YYYY-MM-DD — Agenda del día solo del operario
     */
    public function miDia(Request $r)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $fecha = $r->query('fecha', today()->toDateString());
        return response()->json($this->svc->getAgendaDia($fecha, (int)$recurso->id));
    }

    /**
     * GET /api/agenda/mi/semana?fecha=YYYY-MM-DD — Semana del operario
     */
    public function miSemana(Request $r)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $fechaRef = $r->query('fecha', today()->toDateString());
        $inicio = \Carbon\Carbon::parse($fechaRef)->startOfWeek();
        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $f = $inicio->copy()->addDays($i)->toDateString();
            $dias[] = [
                'fecha' => $f,
                'citas' => $this->svc->getAgendaDia($f, (int)$recurso->id),
            ];
        }
        return response()->json($dias);
    }

    /**
     * PUT /api/agenda/mi/horarios — El operario edita sus propios horarios
     */
    public function misHorarios(Request $r)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $r->validate(['horarios' => 'required|array']);

        foreach ($r->horarios as $h) {
            AgendaHorario::updateOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $h['dia_semana']],
            [
                'hora_inicio' => $h['hora_inicio'],
                'hora_fin' => $h['hora_fin'],
                'activo' => $h['activo'] ?? true,
                'duracion_slot_min' => $h['duracion_slot_min'] ?? 30,
            ]
            );
        }

        return response()->json(['ok' => true, 'horarios' => $recurso->fresh()->horarios]);
    }

    /**
     * POST /api/agenda/mi/bloqueo — Crear bloqueo personal
     */
    public function crearBloqueo(Request $r)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $r->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'motivo' => 'nullable|string|max:200',
        ]);

        $bloqueo = AgendaBloqueo::create([
            'agenda_recurso_id' => $recurso->id,
            'fecha_inicio' => $r->fecha_inicio,
            'fecha_fin' => $r->fecha_fin ?? $r->fecha_inicio,
            'hora_inicio' => $r->hora_inicio,
            'hora_fin' => $r->hora_fin,
            'motivo' => $r->motivo ?? 'Bloqueo personal',
        ]);

        return response()->json($bloqueo, 201);
    }

    /**
     * DELETE /api/agenda/mi/bloqueo/{id}
     */
    public function eliminarBloqueo(int $id)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $bloqueo = AgendaBloqueo::where('id', $id)
            ->where('agenda_recurso_id', $recurso->id)
            ->firstOrFail();
        $bloqueo->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/agenda/mi/citas?estado=&fecha_desde=&fecha_hasta=
     */
    public function misCitas(Request $r)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();

        $query = AgendaCita::where('agenda_recurso_id', $recurso->id)
            ->with(['servicio:id,nombre', 'cliente:id,nombre,telefono'])
            ->orderBy('fecha')->orderBy('hora_inicio');

        if ($r->estado) {
            $query->where('estado', $r->estado);
        }
        if ($r->fecha_desde) {
            $query->where('fecha', '>=', $r->fecha_desde);
        }
        if ($r->fecha_hasta) {
            $query->where('fecha', '<=', $r->fecha_hasta);
        }

        return response()->json($query->get());
    }

    /**
     * PUT /api/agenda/mi/citas/{id}/estado — El operario cambia estado de su cita
     */
    public function cambiarEstadoMia(Request $r, int $id)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $cita = AgendaCita::where('id', $id)
            ->where('agenda_recurso_id', $recurso->id)
            ->firstOrFail();

        $r->validate(['estado' => 'required|in:pendiente,confirmada,en_curso,completada,cancelada']);
        $cita->update(['estado' => $r->estado]);
        return response()->json($cita->fresh());
    }

    /**
     * PUT /api/agenda/mi/citas/{id}/notas — El operario actualiza notas internas
     */
    public function actualizarNotasMia(Request $r, int $id)
    {
        $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
        $cita = AgendaCita::where('id', $id)
            ->where('agenda_recurso_id', $recurso->id)
            ->firstOrFail();

        $r->validate(['notas_internas' => 'nullable|string|max:2000']);
        $cita->update(['notas_internas' => $r->notas_internas]);
        return response()->json(['ok' => true]);
    }
}