<?php
namespace App\Jobs;

use App\Models\Tenant\AgendaCita;
use App\Models\Tenant\Usuario;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificarNuevaCitaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private AgendaCita $cita) {}

    public function handle(WhatsAppService $wa): void
    {
        // Notifica al admin del tenant (primer usuario admin encontrado con whatsapp)
        $admin = Usuario::where('rol', 'admin')->whereNotNull('whatsapp')->first();
        if (!$admin) return;

        $msg = "📅 Nueva reserva desde el portal\n" .
               "Cliente: {$this->cita->cliente_nombre}\n" .
               "Recurso: {$this->cita->recurso?->nombre}\n" .
               "Fecha: {$this->cita->fecha_inicio->format('d/m/Y H:i')}\n" .
               "Estado: {$this->cita->estado}";

        $wa->enviar($admin->whatsapp, $msg);
    }
}
