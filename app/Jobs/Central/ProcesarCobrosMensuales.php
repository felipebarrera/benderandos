<?php

namespace App\Jobs\Central;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Central\Subscription;
use Carbon\Carbon;

class ProcesarCobrosMensuales implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $vencidas = Subscription::where('estado', 'activa')
                        ->where('proximo_cobro', '<=', Carbon::now())
                        ->get();

        foreach ($vencidas as $sub) {
            // 1. Genera el cobro (factura pendiente)
            $sub->pagos()->create([
                'monto_clp' => $sub->monto_clp,
                'estado' => 'pendiente',
            ]);

            // 2. Notificar al admin del tenant vía WhatsApp
            \App\Jobs\SendWhatsAppNotification::dispatch($sub, 'cobro_mensual');

            // 3. Avanza la f. cobro un mes
            $sub->update([
                'proximo_cobro' => Carbon::parse($sub->proximo_cobro)->addMonth(),
            ]);
        }
    }
}
