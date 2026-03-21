<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SaasBillingService;
use App\Services\SaasMetricasService;

class SaaSGenerarFacturacionMensual implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * TAREA PROGRAMADA: Ejecutada cada 1er día del mes a las 00:01
     * Genera automáticamente los registros vencidos de mes y las facturas pendientes.
     */
    public function handle(SaasBillingService $billing, SaasMetricasService $metrics): void
    {
        // 1. Generar los cobros pendientes de todos los perfiles "mensuales"
        $billing->generarCobrosDelMes();
        
        // 2. Procesar los vencidos del mes pasado (suspender morosos, etc)
        $billing->procesarVencimientos();

        // 3. Aprovechar y hacer snapshot de métricas
        $metrics->snapshotDiario();
    }
}
