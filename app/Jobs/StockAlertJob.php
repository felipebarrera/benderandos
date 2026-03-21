<?php

namespace App\Jobs;

use App\Models\Tenant\Producto;
use App\Models\Tenant\ProductoProveedor;
use App\Models\Tenant\OrdenCompra;
use App\Services\ComprasService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ComprasService $comprasService): void
    {
        $alertas = $comprasService->detectarStockBajo();

        if (empty($alertas)) {
            Log::info('StockAlertJob: Sin alertas de stock bajo.');
            return;
        }

        Log::warning('StockAlertJob: ' . count($alertas) . ' productos bajo stock mínimo.');

        // Agrupar por proveedor para sugerir OC
        $porProveedor = collect($alertas)
            ->filter(fn($a) => $a['proveedor_id'] !== null)
            ->groupBy('proveedor_id');

        foreach ($porProveedor as $proveedorId => $productos) {
            Log::info("StockAlertJob: Sugerencia OC para proveedor #{$proveedorId} con " . $productos->count() . " productos.");

            // Notificar vía WhatsApp si disponible
            try {
                $wa = app(WhatsAppService::class);
                $lista = $productos->map(fn($p) => "- {$p['nombre']}: stock {$p['stock_actual']}/{$p['stock_minimo']}")->join("\n");
                $wa->notificarEvento('stock_bajo', [
                    'proveedor_id' => $proveedorId,
                    'productos'    => $productos->count(),
                    'detalle'      => $lista,
                ]);
            } catch (\Exception $e) {
                // WhatsApp opcional
            }
        }
    }
}
