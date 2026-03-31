<?php
namespace App\Services;

use App\Models\Tenant\{AgendaCita, AgendaRecurso, AgendaConfig, AgendaBloqueo, AgendaHorario, AgendaServicio, Cliente};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgendaService
{
    /**
     * Retorna slots disponibles para un recurso en una fecha específica.
     */
    public function getSlotsDisponibles(int $recursoId, Carbon $fecha, ?int $servicioId = null): array
    {
        $recurso = AgendaRecurso::findOrFail($recursoId);
        
        // Carbon dayOfWeekIso: 1=Lunes ... 7=Domingo.
        $diaAjustado = $fecha->dayOfWeekIso;

        $horarios = AgendaHorario::where('agenda_recurso_id', $recursoId)
            ->where('dia_semana', $diaAjustado)
            ->where('activo', true)
            ->get();

        if ($horarios->isEmpty()) return [];

        $duracionMin = 30;
        if ($servicioId) {
            $servicio = AgendaServicio::find($servicioId);
            if ($servicio) $duracionMin = $servicio->duracion_min;
        }

        $citasOcupadas = AgendaCita::where('agenda_recurso_id', $recursoId)
            ->where('fecha', $fecha->toDateString())
            ->where('estado', '!=', 'cancelada')
            ->get(['hora_inicio', 'hora_fin']);

        $bloqueos = AgendaBloqueo::where(function($q) use ($recursoId) {
                $q->where('agenda_recurso_id', $recursoId)->orWhereNull('agenda_recurso_id');
            })
            ->where('fecha_inicio', '<=', $fecha->toDateString())
            ->where('fecha_fin', '>=', $fecha->toDateString())
            ->get(['hora_inicio', 'hora_fin', 'fecha_inicio', 'fecha_fin']);

        $slots = [];
        foreach ($horarios as $horario) {
            $slotDuration = $horario->duracion_slot_min ?? $duracionMin;
            $cursor = Carbon::createFromFormat('H:i:s', $horario->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', $horario->hora_fin);

            while ($cursor->copy()->addMinutes($slotDuration)->lte($fin)) {
                $slotFin = $cursor->copy()->addMinutes($slotDuration);
                $disponible = $this->isSlotLibre($cursor->format('H:i:s'), $slotFin->format('H:i:s'), $citasOcupadas, $bloqueos, $fecha);

                $slots[] = [
                    'hora' => $cursor->format('H:i'),
                    'hora_fin' => $slotFin->format('H:i'),
                    'disponible' => $disponible
                ];
                $cursor->addMinutes($slotDuration);
            }
        }
        return $slots;
    }

    private function isSlotLibre(string $inicio, string $fin, Collection $citas, Collection $bloqueos, Carbon $fecha): bool
    {
        foreach ($citas as $cita) {
            if ($inicio < $cita->hora_fin && $fin > $cita->hora_inicio) return false;
        }
        foreach ($bloqueos as $bloqueo) {
            if (!$bloqueo->hora_inicio && !$bloqueo->hora_fin) return false;
            $overlap = ($inicio < $bloqueo->hora_fin && $fin > $bloqueo->hora_inicio);
            if ($overlap) return false;
        }
        return true;
    }

    public function getAgendaDia(string $fecha, ?int $recursoId = null): array
    {
        $query = AgendaCita::with(['recurso', 'servicio'])
            ->where('fecha', $fecha)
            ->where('estado', '!=', 'cancelada')
            ->orderBy('hora_inicio');

        if ($recursoId) $query->where('agenda_recurso_id', $recursoId);
        $citas = $query->get();
        
        $recursos = AgendaRecurso::where('activo', true)->orderBy('orden')->get();
        $agenda = [];

        foreach ($recursos as $r) {
            if ($recursoId && $r->id != $recursoId) continue;
            
            $slots = $this->getSlotsDisponibles($r->id, Carbon::parse($fecha));
            $citasRecurso = $citas->where('agenda_recurso_id', $r->id);

            foreach ($slots as &$slot) {
                $cita = $citasRecurso->first(fn($c) => substr($c->hora_inicio, 0, 5) == $slot['hora']);
                if ($cita) {
                    $slot['cita'] = $cita;
                    $slot['disponible'] = false;
                }
            }
            $agenda[$r->id] = [
                'recurso' => $r,
                'slots' => $slots
            ];
        }
        return $agenda;
    }

    public function crearCita(array $datos): AgendaCita
    {
        return DB::transaction(function() use ($datos) {
            $conflict = AgendaCita::where('agenda_recurso_id', $datos['agenda_recurso_id'])
                ->where('fecha', $datos['fecha'])
                ->where('estado', '!=', 'cancelada')
                ->where(function($q) use ($datos) {
                    $q->where('hora_inicio', '<', $datos['hora_fin'])
                      ->where('hora_fin', '>', $datos['hora_inicio']);
                })->exists();

            if ($conflict) throw new \Exception("El horario seleccionado ya no está disponible.");

            $config = AgendaConfig::first();
            $estado = ($config && $config->confirmacion_wa_activa) ? 'pendiente' : 'confirmada';
            if (($datos['origen'] ?? 'pos') === 'pos') $estado = 'confirmada';

            // Sincronizar o crear cliente si viene RUT o Email
            $clienteId = $datos['cliente_id'] ?? null;
            if (!$clienteId && (!empty($datos['paciente_rut']) || !empty($datos['paciente_email']))) {
                $c = Cliente::where(function($q) use ($datos) {
                    if (!empty($datos['paciente_rut'])) $q->where('rut', $datos['paciente_rut']);
                    if (!empty($datos['paciente_email'])) $q->orWhere('email', $datos['paciente_email']);
                })->first();

                if (!$c) {
                    $c = Cliente::create([
                        'nombre'   => $datos['paciente_nombre'],
                        'rut'      => $datos['paciente_rut'] ?? null,
                        'email'    => $datos['paciente_email'] ?? null,
                        'telefono' => $datos['paciente_telefono'] ?? null,
                        'origen'   => 'portal',
                    ]);
                }
                $clienteId = $c->id;
            }

            $cita = AgendaCita::create(array_merge($datos, [
                'estado' => $estado,
                'cliente_id' => $clienteId
            ]));

            // Dispatch notification if needed
            if ($config && $config->recordatorio_activo && $cita->paciente_telefono) {
                $fechaCita = Carbon::parse($cita->fecha . ' ' . $cita->hora_inicio);
                $delay = $fechaCita->subDay();
                if ($delay->isFuture()) {
                    \App\Jobs\RecordatorioCitaJob::dispatch($cita->id)->delay($delay);
                }
            }

            return $cita;
        });
    }

    public function cambiarEstado(AgendaCita $cita, string $nuevoEstado, ?int $userId = null): AgendaCita
    {
        $estadosValidos = ['pendiente', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \Exception("Estado inválido: {$nuevoEstado}");
        }

        $update = ['estado' => $nuevoEstado];
        if ($nuevoEstado === 'confirmada') $update['confirmado_por'] = $userId;
        if ($nuevoEstado === 'cancelada')  $update['cancelado_por']  = $userId;

        $cita->update($update);
        return $cita->fresh();
    }

    public function iniciarConsulta(AgendaCita $cita, ?int $userId = null): AgendaCita
    {
        if (!in_array($cita->estado, ['confirmada', 'pendiente'])) {
            throw new \Exception("Solo se pueden iniciar citas confirmadas o pendientes.");
        }
        $cita->update([
            'estado' => 'en_curso',
            'atendido_por' => $userId ?? auth()->id(),
            'inicio_atencion' => now()
        ]);
        return $cita->fresh();
    }

    public function completarCita(AgendaCita $cita, ?int $userId = null): AgendaCita
    {
        if ($cita->estado !== 'en_curso') {
            throw new \Exception("Solo se pueden completar citas que están en curso.");
        }
        $cita->update([
            'estado' => 'completada',
            'fin_atencion' => now()
        ]);
        return $cita->fresh();
    }
}
