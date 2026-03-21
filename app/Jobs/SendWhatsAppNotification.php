<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * @param mixed $modelo (e.g., Venta, MovimientoStock, Renta)
     * @param string $tipo ('comprobante', 'encargo_listo', 'deuda_pendiente', 'stock_critico', 'renta_venciendo')
     */
    public function __construct(
        public mixed $modelo,
        public string $tipo
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $wa): void
    {
        $mensaje = match($this->tipo) {
            'comprobante'     => $wa->buildComprobante($this->modelo),
            'encargo_listo'   => $wa->buildEncargoListo($this->modelo),
            'deuda_pendiente' => $wa->buildDeudaPendiente($this->modelo),
            'stock_critico'   => $wa->buildStockCritico($this->modelo),
            'pedido_remoto'   => $wa->buildPedidoRemoto($this->modelo),
            'renta_venciendo' => $wa->buildRentaVenciendo($this->modelo),
            'trial_expira'    => $wa->buildTrialExpira($this->modelo),
            'cobro_mensual'   => $wa->buildCobroMensual($this->modelo),
            default           => null,
        };

        if ($mensaje) {
            // El destinatario depende del tipo (cliente o admin del tenant en saas/trial)
            $numero = null;
            if (in_array($this->tipo, ['trial_expira', 'cobro_mensual'])) {
                $numero = $this->modelo->whatsapp_admin ?? null;
            } else {
                $numero = $this->modelo->cliente?->whatsapp ?? null;
            }

            if ($numero) {
                $wa->enviar($numero, $mensaje);
            }
        }
    }
}
