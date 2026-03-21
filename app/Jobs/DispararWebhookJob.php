<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispararWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300]; // 10s, 1m, 5m

    public function __construct(
        protected Webhook $webhook,
        protected string $evento,
        protected array $payload
    ) {}

    public function handle(): void
    {
        if (!$this->webhook->activo) return;

        // Construir la data final
        $data = [
            'id'        => uniqid('wh_'),
            'evento'    => $this->evento,
            'timestamp' => now()->toIso8601String(),
            'payload'   => $this->payload
        ];

        // Preparar firma HMAC si hay secreto
        $headers = [
            'User-Agent' => 'BenderAnd-Webhook/1.0',
            'Content-Type' => 'application/json'
        ];

        if (!empty($this->webhook->secreto)) {
            $jsonPayload = json_encode($data);
            $firma = hash_hmac('sha256', $jsonPayload, $this->webhook->secreto);
            $headers['X-BenderAnd-Signature'] = 'sha256=' . $firma;
        }

        try {
            $response = Http::timeout(10)->withHeaders($headers)
                ->post($this->webhook->url, $data);

            if ($response->successful()) {
                // Reiniciar fallos
                $this->webhook->update([
                    'fallos_consecutivos' => 0,
                    'ultimo_intento' => now()
                ]);
            } else {
                $this->manejarFallo("Respuesta HTTP {$response->status()}");
            }
            
        } catch (\Exception $e) {
            $this->manejarFallo($e->getMessage());
            $this->release($this->backoff[$this->attempts() - 1] ?? 300); // Reintentar
        }
    }

    private function manejarFallo(string $motivo)
    {
        $fallos = $this->webhook->fallos_consecutivos + 1;
        $updates = [
            'fallos_consecutivos' => $fallos,
            'ultimo_intento' => now()
        ];
        
        // Suspender si falla mucho (ej: 10 veces seguidas)
        if ($fallos >= 10) {
            $updates['activo'] = false;
            Log::warning("Webhook desactivado por fallos continuos: {$this->webhook->url}");
        }

        $this->webhook->update($updates);
    }
}
