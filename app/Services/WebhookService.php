<?php

namespace App\Services;

use App\Models\Tenant\Webhook;
use App\Jobs\DispararWebhookJob;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Evalúa todos los webhooks activos del tenant actual
     * y si están suscritos al evento, encola el Job de despacho.
     */
    public function dispatchEvent(string $evento, array $payload): void
    {
        try {
            // Buscar webhooks activos que escuchen este evento o '*'
            $webhooks = Webhook::where('activo', true)->get();

            foreach ($webhooks as $webhook) {
                if (in_array($evento, $webhook->eventos) || in_array('*', $webhook->eventos)) {
                    // Encolamos el Job para no bloquear el request actual
                    DispararWebhookJob::dispatch($webhook, $evento, $payload);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al despachar Webhooks para evento {$evento}: " . $e->getMessage());
        }
    }
}
