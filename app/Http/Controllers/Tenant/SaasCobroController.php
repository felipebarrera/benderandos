<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SaasCobro;
use App\Models\Tenant\SaasCliente;
use App\Services\SaasBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaasCobroController extends Controller
{
    public function __construct(
        private SaasBillingService $billingService
    ) {}

    /**
     * Endpoint Admin para listar los cobros de todos los clientes
     */
    public function index(Request $request): JsonResponse
    {
        $query = SaasCobro::with('cliente.plan')->orderBy('periodo', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    /**
     * Ejecuta el trabajo manual de facturación recurrente.
     * En prod, esto se llama vía Laravel Cron a las 00:01 del día 1.
     */
    public function generarFacturacionMes(): JsonResponse
    {
        $creados = $this->billingService->generarCobrosDelMes();
        return response()->json(['message' => "Se generaron $creados cobros pendientes."]);
    }

    /**
     * Procesar vencimientos manualmente
     */
    public function procesarVencimientos(): JsonResponse
    {
        $vencidos = $this->billingService->procesarVencimientos();
        return response()->json(['message' => "Se marcaron $vencidos clientes como morosos."]);
    }

    /**
     * El ejecutivo de cuenta registra un pago (transferencia manual).
     */
    public function registrarPago(Request $request, int $cobroId): JsonResponse
    {
        $request->validate([
            'metodo_pago' => 'required|string',
            'referencia'  => 'nullable|string'
        ]);

        $cobro = SaasCobro::with('cliente')->findOrFail($cobroId);

        if ($cobro->estado === 'pagado') {
            return response()->json(['message' => 'Este cobro ya estaba pagado'], 422);
        }

        $this->billingService->registrarPago($cobro, $request->metodo_pago, $request->referencia);

        return response()->json(['message' => 'Pago registrado con éxito', 'cobro' => $cobro->fresh()]);
    }
}
