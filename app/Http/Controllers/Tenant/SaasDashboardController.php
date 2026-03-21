<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SaasCliente;
use App\Models\Tenant\SaasCobro;
use App\Models\Tenant\SaasPlan;
use App\Services\SaasBillingService;
use App\Services\SaasMetricasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaasDashboardController extends Controller
{
    public function __construct(
        private SaasMetricasService $metricasService,
        private SaasBillingService $billingService
    ) {}

    /**
     * Dashboard general para el CEO / Admin
     */
    public function index(): JsonResponse
    {
        $data = $this->metricasService->getDashboardData();
        return response()->json($data);
    }

    /**
     * Endpoint manual para forzar el cálculo de métricas del día de hoy.
     * En producción esto corre por Scheduler (Job).
     */
    public function generarSnapshot(): JsonResponse
    {
        $metrica = $this->metricasService->snapshotDiario();
        return response()->json(['message' => 'Snapshot generado exitosamente', 'data' => $metrica]);
    }
}
