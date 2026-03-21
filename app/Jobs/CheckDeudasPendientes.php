<?php

namespace App\Jobs;

use App\Models\Tenant\Deuda;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppNotification;

class CheckDeudasPendientes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Tenant::all()->each(function ($tenant) {
            tenancy()->initialize($tenant);

            // Deudas pendientes con más de 7 días (ajustable en spec dice >7)
            $deudas = Deuda::with('cliente')
                ->where(fn($q) => $q->where('estado', 'pendiente'))
                ->where(fn($q) => $q->where('fecha_vencimiento', '<=', now()))
                ->get();

            foreach ($deudas as $deuda) {
                if ($deuda->cliente && $deuda->cliente->whatsapp) {
                    SendWhatsAppNotification::dispatch($deuda, 'deuda_pendiente');
                    Log::info("Enviando recordatorio de deuda para el cliente {$deuda->cliente_id} en tenant {$tenant->id}");
                }
            }

            tenancy()->end();
        });
    }
}
