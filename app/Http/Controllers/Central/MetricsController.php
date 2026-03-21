<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\Central\MetricsService;

class MetricsController extends Controller
{
    public function index(MetricsService $metrics)
    {
        return response()->json([
            'mrr' => $metrics->mrr(),
            'tenants_activos' => $metrics->tenantsActivos(),
            'churn_mes_actual' => $metrics->churnMesActual(),
            'crecimiento_mensual' => $metrics->crecimientoMensual(),
        ]);
    }

    public function dashboard(MetricsService $metrics)
    {
        return view('central.dashboard', [
            'title' => 'Métricas Generales',
            'stats' => [
                'mrr' => $metrics->mrr(),
                'tenants' => $metrics->tenantsActivos(),
                'churn' => $metrics->churnMesActual(),
                'crecimiento' => $metrics->crecimientoRate(),
            ]
        ]);
    }
}
