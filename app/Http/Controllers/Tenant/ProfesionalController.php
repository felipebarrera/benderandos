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
     */
    public function paciente(int $id)
    {
        $recurso = $this->miRecurso();
        $paciente = Cliente::findOrFail($id);

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
                    'notas_internas'  => $c->notas_internas,
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

        $timeline = $citas->concat($seguimientos)
            ->sortByDesc('fecha')
            ->values();

        return response()->json($timeline);
    }

    /**
     * POST /api/profesional/pacientes/{id}/nota
     */
    public function agregarNota(Request $r, int $id)
    {
        Cliente::findOrFail($id);

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

    public function seguimientoPaciente(int $id)
    {
        $items = SeguimientoPaciente::where('cliente_id', $id)
            ->where('usuario_id', auth()->id())
            ->orderBy('resuelto')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items);
    }

    public function crearSeguimiento(Request $r, int $id)
    {
        return $this->agregarNota($r, $id);
    }

    public function actualizarSeguimiento(Request $r, int $id)
    {
        $seg = SeguimientoPaciente::where('id', $id)
            ->where('usuario_id', auth()->id())
            ->firstOrFail();

        $seg->update($r->only(['contenido','fecha_seguimiento','resuelto','tipo']));
        return response()->json($seg);
    }

    public function estadisticas()
    {
        $recurso = $this->miRecurso();
        $uid     = auth()->id();
        $hoy     = now()->toDateString();
        $semIni  = now()->startOfWeek()->toDateString();
        $semFin  = now()->endOfWeek()->toDateString();

        if (!$recurso) {
            return response()->json([
                'citas_hoy'               => 0,
                'citas_semana'            => 0,
                'pacientes_totales'       => 0,
                'seguimientos_pendientes' => SeguimientoPaciente::where('usuario_id', $uid)->where('resuelto', false)->whereNotNull('fecha_seguimiento')->count(),
                'proxima_cita'            => null,
            ]);
        }

        return response()->json([
            'citas_hoy'            => AgendaCita::where('agenda_recurso_id', $recurso->id)
                                        ->where('fecha', $hoy)
                                        ->whereNotIn('estado', ['cancelada'])
                                        ->count(),
            'citas_semana'         => AgendaCita::where('agenda_recurso_id', $recurso->id)
                                        ->whereBetween('fecha', [$semIni, $semFin])
                                        ->whereNotIn('estado', ['cancelada'])
                                        ->count(),
            'pacientes_totales'    => AgendaCita::where('agenda_recurso_id', $recurso->id)
                                        ->whereNotNull('cliente_id')
                                        ->distinct('cliente_id')
                                        ->count('cliente_id'),
            'seguimientos_pendientes' => SeguimientoPaciente::where('usuario_id', $uid)
                                        ->where('resuelto', false)
                                        ->whereNotNull('fecha_seguimiento')
                                        ->count(),
            'proxima_cita'         => AgendaCita::where('agenda_recurso_id', $recurso->id)
                                        ->where('fecha', '>=', $hoy)
                                        ->whereNotIn('estado', ['cancelada','completada'])
                                        ->orderBy('fecha')->orderBy('hora_inicio')
                                        ->select('id','fecha','hora_inicio','paciente_nombre','estado')
                                        ->first(),
        ]);
    }

    private function miRecurso(): ?AgendaRecurso
    {
        return AgendaRecurso::where('usuario_id', auth()->id())
            ->where('activo', true)
            ->first();
    }
}
