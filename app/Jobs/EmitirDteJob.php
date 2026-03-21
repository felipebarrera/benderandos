<?php

namespace App\Jobs;

use App\Models\Tenant\DteEmitido;
use App\Models\Tenant\ConfigSii;
use App\Models\Tenant\Venta;
use App\Services\SiiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmitirDteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private int $ventaId,
        private ?string $tipoDteOverride = null
    ) {}

    public function handle(SiiService $siiService): void
    {
        $venta = Venta::with(['cliente', 'items.producto'])->find($this->ventaId);

        if (!$venta) {
            Log::warning("EmitirDteJob: Venta #{$this->ventaId} no encontrada.");
            return;
        }

        // Verificar si ya tiene DTE emitido
        $existeDte = DteEmitido::where('venta_id', $venta->id)
            ->whereIn('tipo_dte', [DteEmitido::BOLETA, DteEmitido::FACTURA])
            ->whereNotIn('estado_sii', ['error'])
            ->exists();

        if ($existeDte) {
            Log::info("EmitirDteJob: Venta #{$venta->id} ya tiene DTE emitido.");
            return;
        }

        $config = ConfigSii::first();
        if (!$config) {
            Log::warning("EmitirDteJob: Sin configuración SII. Omitiendo DTE para venta #{$venta->id}.");
            return;
        }

        try {
            // Determinar tipo de DTE
            $tipoDte = $this->tipoDteOverride ?? $config->documento_default;

            if ($tipoDte === 'factura' || $venta->tipo_documento === 'factura') {
                $dte = $siiService->emitirFactura($venta);
            } else {
                $dte = $siiService->emitirBoleta($venta);
            }

            Log::info("EmitirDteJob: DTE emitido exitosamente. Tipo: {$dte->tipo_dte}, Folio: {$dte->folio}");

        } catch (\Exception $e) {
            Log::error("EmitirDteJob: Error emitiendo DTE para venta #{$venta->id}: " . $e->getMessage());
            throw $e; // re-throw para que el job se reintente
        }
    }
}
