<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Central\MetricsService;
use Illuminate\Http\JsonResponse;

class SuperAdminDashboardController extends Controller
{
    public function index(MetricsService $metrics): JsonResponse
    {
        return response()->json([
            'mrr' => $metrics->mrr(),
            'mrr_detalle' => $metrics->mrrPorMes(),
            'tenants_activos' => $metrics->tenantsActivos(),
            'churn_count' => $metrics->churnMesActual(),
            'churn_rate' => $metrics->churnRate(),
            'crecimiento' => $metrics->crecimientoMensual(),
        ]);
    }
}
