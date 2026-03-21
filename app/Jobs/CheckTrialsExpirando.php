<?php

namespace App\Jobs;

use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppNotification;

class CheckTrialsExpirando implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tenants = Tenant::where('estado', 'trial')->get();

        foreach ($tenants as $tenant) {
            if (! $tenant->trial_hasta) continue;

            $diasRestantes = (int) now()->startOfDay()->diffInDays($tenant->trial_hasta->startOfDay(), false);

            if (in_array($diasRestantes, [7, 3, 1])) {
                Log::info("Tenant {$tenant->id} con trial por expirar en {$diasRestantes} días. Alertando admin.");
                if ($tenant->whatsapp_admin) {
                     SendWhatsAppNotification::dispatch($tenant, 'trial_expirando');
                }
            } elseif ($diasRestantes < 0) {
                // Posiblemente auto-suspender o pasar a trial_expirado
                $tenant->estado = 'trial_vencido';
                $tenant->save();
            }
        }
    }
}
