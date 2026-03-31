<?php
namespace App\Jobs;

use App\Models\Tenant\AgendaCita;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordatorioCitaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $citaId) {}

    public function handle(WhatsAppService $wa): void
    {
        $cita = AgendaCita::with(['recurso', 'servicio'])->find($this->citaId);
        
        if (!$cita || $cita->estado === 'cancelada' || $cita->recordatorio_enviado) return;

        $telefono = $cita->paciente_telefono;
        if (!$telefono) return;

        $msg = "Recordatorio 🗓️\n" .
               "Hola {$cita->paciente_nombre}, mañana tienes cita con {$cita->recurso->nombre}\n" .
               "Hora: " . substr($cita->hora_inicio, 0, 5) . " hrs\n" .
               "Servicio: " . ($cita->servicio?->nombre ?? 'Atención General') . "\n\n" .
               "¿Deseas confirmar o cancelar? Responde a este mensaje.";

        $wa->enviar($telefono, $msg);
        $cita->update(['recordatorio_enviado' => true]);
    }
}
