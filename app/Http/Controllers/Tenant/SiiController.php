<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ConfigSii;
use App\Models\Tenant\DteEmitido;
use App\Models\Tenant\Venta;
use App\Services\SiiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiiController extends Controller
{
    public function __construct(
        private SiiService $siiService
    ) {}

    /**
     * GET /api/sii/dashboard — KPIs del día
     */
    public function dashboard(): JsonResponse
    {
        $resumen = $this->siiService->getResumenDiario();

        $ultimosDtes = DteEmitido::with('venta.cliente')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'resumen'     => $resumen,
            'ultimos_dte' => $ultimosDtes,
        ]);
    }

    /**
     * GET /api/sii/dtes — Lista paginada
     */
    public function index(Request $request): JsonResponse
    {
        $query = DteEmitido::with('venta.cliente')
            ->orderByDesc('fecha_emision');

        if ($request->filled('tipo')) {
            $query->where('tipo_dte', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado_sii', $request->estado);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_emision', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->hasta);
        }

        return response()->json(
            $query->paginate($request->input('per_page', 25))
        );
    }

    /**
     * GET /api/sii/dtes/{id} — Detalle
     */
    public function show($id): JsonResponse
    {
        $dte = DteEmitido::with(['venta.items.producto', 'venta.cliente', 'notasCredito'])
            ->findOrFail($id);

        return response()->json($dte);
    }

    /**
     * POST /api/sii/emitir/{ventaId} — Emisión manual
     */
    public function emitir(Request $request, int $ventaId): JsonResponse
    {
        $venta = Venta::with(['cliente', 'items.producto'])
            ->whereIn('estado', ['pagada', 'fiada'])
            ->findOrFail($ventaId);

        $tipo = $request->input('tipo', 'boleta');

        try {
            $dte = $tipo === 'factura'
                ? $this->siiService->emitirFactura($venta)
                : $this->siiService->emitirBoleta($venta);

            return response()->json([
                'message' => "DTE emitido: Folio {$dte->folio}",
                'dte'     => $dte,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/sii/nota-credito/{dteId} — Emitir NC
     */
    public function notaCredito(Request $request, int $dteId): JsonResponse
    {
        $request->validate(['motivo' => 'required|string|max:255']);

        $dteOriginal = DteEmitido::findOrFail($dteId);

        try {
            $nc = $this->siiService->emitirNotaCredito($dteOriginal, $request->motivo);

            return response()->json([
                'message' => "Nota de Crédito emitida: Folio {$nc->folio}",
                'dte'     => $nc,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/sii/libro-ventas?mes=3&anio=2026
     */
    public function libroVentas(Request $request): JsonResponse
    {
        $mes  = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $libro = $this->siiService->getLibroVentas($mes, $anio);

        return response()->json($libro);
    }

    /**
     * GET /api/sii/config
     */
    public function getConfig(): JsonResponse
    {
        $config = ConfigSii::first();
        return response()->json($config);
    }

    /**
     * PUT /api/sii/config
     */
    public function saveConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rut_empresa'       => 'required|string|max:12',
            'razon_social'      => 'required|string|max:255',
            'giro'              => 'required|string|max:255',
            'acteco'            => 'nullable|string|max:10',
            'direccion'         => 'required|string|max:255',
            'comuna'            => 'required|string|max:100',
            'ciudad'            => 'required|string|max:100',
            'ambiente'          => 'required|in:certificacion,produccion',
            'documento_default' => 'required|in:boleta,factura',
            'email_dte'         => 'nullable|email',
            'libredte_hash'     => 'nullable|string',
            'resolucion_fecha'  => 'nullable|string',
            'resolucion_numero' => 'nullable|integer',
        ]);

        $config = ConfigSii::firstOrNew();
        $config->fill($data);
        $config->save();

        return response()->json(['message' => 'Configuración SII guardada correctamente', 'config' => $config]);
    }

    /**
     * POST /api/sii/consultar-estado/{dteId}
     */
    public function consultarEstado(int $dteId): JsonResponse
    {
        $dte = DteEmitido::findOrFail($dteId);
        $estado = $this->siiService->consultarEstado($dte);

        return response()->json([
            'estado' => $estado,
            'dte'    => $dte->fresh(),
        ]);
    }
}
