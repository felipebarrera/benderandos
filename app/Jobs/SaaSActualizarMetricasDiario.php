<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SaasMetricasService;

class SaaSActualizarMetricasDiario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * TAREA PROGRAMADA: Ejecutada cada día a medianoche
     * Guarda el snapshot transversal de las metricas del negocio
     */
    public function handle(SaasMetricasService $metrics): void
    {
        $metrics->snapshotDiario();
    }
}
