<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Deuda;
use App\Models\Tenant\TipoPago;
use App\Models\Tenant\Empleado;
use App\Models\Tenant\Asistencia;
use App\Models\Tenant\Entrega;
use App\Models\Tenant\DteEmitido;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(\App\Services\MarketingService $marketingService): JsonResponse
    {
        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();

        // 1. VENTAS
        $ventasHoy = Venta::whereDate('created_at', $hoy)
            ->whereIn('estado', ['pagada', 'en_caja'])
            ->get();
            
        $ventasMes = Venta::whereDate('created_at', '>=', $inicioMes)
            ->whereIn('estado', ['pagada', 'en_caja'])
            ->get();

        $ingresosHoy = $ventasHoy->sum('total');
        $ingresosMes = $ventasMes->sum('total');
        $ticketPromedioMes = $ventasMes->count() > 0 ? $ingresosMes / $ventasMes->count() : 0;

        // 2. STOCK & DEUDAS
        $productosActivos = Producto::activos()->count();
        $stockBajoCount = Producto::activos()
                ->whereColumn('cantidad', '<=', 'cantidad_minima')
                ->count();
        $deudasPendientes = Deuda::pendientes()->sum('valor');

        // 3. RRHH
        $empleadosActivos = Empleado::activos()->count();
        $asistenciaHoy = Asistencia::whereDate('fecha', $hoy)->count();

        // 4. DELIVERY
        $deliveriesPendientes = Entrega::activas()->count();
        $deliveriesEntregadosMes = Entrega::where('estado', 'entregada')
                ->whereDate('created_at', '>=', $inicioMes)
                ->count();

        // 5. SII (DTEs)
        $dtesRechazados = DteEmitido::whereIn('estado_sii', ['REC', 'REP'])->count();

        // 6. MARKETING (H17)
        $marketing = $marketingService->getDashboard();

        return response()->json([
            'kpis' => [
                'ventas' => [
                    'hoy' => $ingresosHoy,
                    'mes' => $ingresosMes,
                    'count_hoy' => $ventasHoy->count(),
                    'ticket_promedio' => $ticketPromedioMes,
                ],
                'operaciones' => [
                    'productos_count' => $productosActivos,
                    'stock_bajo_alertas' => $stockBajoCount,
                    'deudas_total' => $deudasPendientes,
                ],
                'rrhh' => [
                    'empleados_activos' => $empleadosActivos,
                    'presentes_hoy' => $asistenciaHoy,
                ],
                'logistica' => [
                    'pendientes' => $deliveriesPendientes,
                    'entregados_mes' => $deliveriesEntregadosMes,
                ],
                'alertas_sii' => [
                    'dtes_rechazados' => $dtesRechazados,
                ],
                'marketing' => $marketing
            ],
            'tipos_pago' => TipoPago::activos()->get(),
        ]);
    }
}
