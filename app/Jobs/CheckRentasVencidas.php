<?php

namespace App\Jobs;

use App\Models\Tenant\Renta;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppNotification;
use App\Events\Tenant\RentaVenciendo;

class CheckRentasVencidas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Iterar sobre todos los tenants para verificar rentas por vencer
        Tenant::all()->each(function ($tenant) {
            tenancy()->initialize($tenant);

            $venciendose = Renta::with('cliente')
                ->where(fn($q) => $q->where('estado', 'activa'))
                ->where(fn($q) => $q->where('fin_programado', '<=', now()->addMinutes(10)))
                ->where(fn($q) => $q->where('fin_programado', '>', now()))
                ->get();

            foreach ($venciendose as $renta) {
                Log::info("Renta #{$renta->id} (Producto {$renta->producto_id}) está por vencer para el tenant {$tenant->id}");
                // Notificar al cliente via WhatsApp
                if ($renta->cliente && $renta->cliente->whatsapp) {
                    SendWhatsAppNotification::dispatch($renta, 'renta_venciendo');
                }
                
                // Emitir Broadcasting Event al frontend
                broadcast(new RentaVenciendo($renta));
            }

            // También podemos marcar las que ya vencieron
            $vencidas = Renta::where(fn($q) => $q->where('estado', 'activa'))
                ->where(fn($q) => $q->where('fin_programado', '<=', now()))
                ->get();

            foreach ($vencidas as $vencida) {
                $vencida->update(['estado' => 'vencida']);
                Log::info("Renta #{$vencida->id} marcada como vencida (Tenant {$tenant->id})");
            }

            tenancy()->end();
        });
    }
}
