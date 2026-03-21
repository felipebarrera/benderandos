<?php

namespace App\Services;

use App\Models\Tenant\Venta;
use App\Models\Tenant\ItemVenta;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\JwtBridgeService;

class WhatsAppService
{
    private string $botUrl;
    private string $botToken;
    protected $jwtService;

    public function __construct(JwtBridgeService $jwtService)
    {
        $this->botUrl = config('services.whatsapp_bot.url', '');
        $this->botToken = config('services.whatsapp_bot.token', '');
        $this->jwtService = $jwtService;
    }

    /**
     * Envía un mensaje de texto plano a un número específico vía Bot.
     */
    public function enviar(string $numero, string $mensaje): bool
    {
        if (empty($this->botUrl) || empty($this->botToken)) {
            Log::warning("WhatsAppService: URL o Token de Bot no configurados. Omitiendo mensaje a {$numero}.");
            return false;
        }

        try {
            $response = Http::withToken($this->botToken)
                ->timeout(5)
                ->post($this->botUrl . '/send', [
                    'to'      => $this->formatearNumero($numero),
                    'message' => $mensaje,
                    'type'    => 'text',
            ]);

            if (! $response->successful()) {
                Log::error("WhatsAppService: Error al enviar mensaje a {$numero}: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Excepción al enviar mensaje a {$numero}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia comprobante de venta.
     */
    public function buildComprobante(Venta $venta): ?string
    {
        $cliente = $venta->cliente;
        if (! $cliente || ! $cliente->whatsapp) {
            return null; // Return null if no number to tell the Job not to send
        }

        $itemsText = $venta->items->map(function (ItemVenta $i) {
            $precioFmt = number_format($i->total_item, 0, ',', '.');
            return "• {$i->producto->nombre} x{$i->cantidad}: $" . $precioFmt;
        })->implode("\n");

        $totalFmt = number_format($venta->total, 0, ',', '.');
        
        return "✅ *Comprobante BenderAnd POS*\n"
             . "N° {$venta->uuid}\n\n"
             . $itemsText . "\n\n"
             . "*Total: $" . $totalFmt . "*\n\n"
             . "¡Gracias por su compra!";
    }
    
    public function buildStockCritico(mixed $movimiento): ?string
    {
        return "⚠️ *Alerta Stock Crítico*\n\n"
             . "El producto {$movimiento->producto->nombre} está bajo la cantidad mínima configurada "
             . "({$movimiento->producto->cantidad_minima} unidades).\n"
             . "Stock actual: {$movimiento->stock_despues}";
    }

    public function buildDeudaPendiente(mixed $deuda): ?string
    {
        if (! $deuda->cliente?->whatsapp) return null;

        $monto = number_format($deuda->monto_total - $deuda->monto_pagado, 0, ',', '.');
        return "👋 *Recordatorio de Deuda*\n\n"
             . "Estimado {$deuda->cliente->nombre}, tiene un saldo pendiente de $ {$monto} "
             . "correspondiente a compras anteriores. Por favor, regularice su situación. ¡Gracias!";
    }

    public function buildRentaVenciendo(mixed $renta): ?string
    {
        if (! $renta->cliente?->whatsapp) return null;

        return "⏰ *Alerta de Tiempo*\n\n"
             . "Le recordamos que su alquiler/renta para {$renta->producto->nombre} "
             . "vencerá en 10 minutos. \nPor favor acérquese a recepción si desea extender el tiempo.";
    }

    public function buildTrialExpira(mixed $tenant): ?string
    {
        if (! $tenant->whatsapp_admin) return null;

        return "⚠️ *Aviso de Expiración BenderAnd POS*\n\n"
             . "Estimado(a), su período de prueba finalizará pronto.\n"
             . "Por favor, póngase en contacto con soporte para mantener sus servicios activos en: " . $tenant->id . ".benderand.cl";
    }

    public function buildEncargoListo(mixed $encargo): ?string
    {
        if (! $encargo->cliente?->whatsapp) return null;

        return "📦 *Encargo Listo*\n\n"
             . "Hola {$encargo->cliente->nombre}, le informamos que su encargo "
             . "ya se encuentra listo para ser retirado en nuestra sucursal. ¡Le esperamos!";
    }

    public function buildCobroMensual(mixed $subscription): ?string
    {
        $tenant = $subscription->tenant;
        if (! $tenant || ! $tenant->whatsapp_admin) return null;

        $monto = number_format($subscription->monto_clp, 0, ',', '.');
        $proximo = $subscription->proximo_cobro->format('d/m/Y');

        return "💳 *Aviso de Cobro Mensual*\n\n"
             . "Estimado cliente de {$tenant->nombre}, le informamos que se ha generado el cobro de su suscripción "
             . "por un monto de $ {$monto}. \nSu próximo cobro está programado para el {$proximo}. "
             . "\n¡Gracias por preferir BenderAnd POS!";
    }

    /**
     * Notificar al Bot sobre una venta confirmada.
     */
    public function notificarVentaConfirmada(Venta $venta): bool
    {
        return $this->postAlBot('/webhook/erp/venta-confirmada', [
            'telefono'    => $venta->cliente->telefono,
            'venta_id'    => $venta->id,
            'total'       => $venta->total,
            'items'       => $venta->items->map(fn($i) => $i->producto->nombre),
            'tenant_token'=> $this->jwtService->generarToken(tenant()),
        ]);
    }

    /**
     * Helper para enviar datos al webhook del Bot.
     */
    private function postAlBot(string $path, array $data): bool
    {
        if (empty($this->botUrl)) return false;

        try {
            $response = Http::withToken($this->botToken)
                ->timeout(5)
                ->post($this->botUrl . $path, $data);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Error en postAlBot: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to optionally format a number explicitly enforcing chileno +56 if it has length.
     */
    private function formatearNumero(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);
        if (!str_starts_with($numero, '56') && strlen($numero) >= 8) {
            $numero = '56' . ltrim($numero, '0');
        }
        return $numero;
    }
}
