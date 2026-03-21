<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Entrega;
use App\Models\Tenant\Repartidor;
use App\Models\Tenant\ZonaEnvio;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(
        private DeliveryService $deliveryService
    ) {}

    // =================== ENTREGAS ===================

    public function dashboard(): JsonResponse
    {
        $kpis = $this->deliveryService->getDashboard();
        $activas = Entrega::with(['repartidor', 'venta.cliente'])
            ->activas()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'kpis'    => $kpis,
            'activas' => $activas,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Entrega::with(['repartidor', 'venta.cliente'])->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Entrega::with(['repartidor', 'venta.cliente', 'tracking', 'zonaEnvio'])->findOrFail($id)
        );
    }

    public function asignar(Request $request, int $id): JsonResponse
    {
        $request->validate(['repartidor_id' => 'required|integer|exists:repartidores,id']);

        $entrega = Entrega::findOrFail($id);
        $repartidor = Repartidor::findOrFail($request->repartidor_id);

        try {
            $entrega = $this->deliveryService->asignar($entrega, $repartidor);
            return response()->json(['message' => "Repartidor {$repartidor->nombre} asignado", 'entrega' => $entrega]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado'   => 'required|string|in:en_preparacion,en_camino,entregada,fallida',
            'motivo'   => 'nullable|string|max:255',
            'latitud'  => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ]);

        $entrega = Entrega::findOrFail($id);

        try {
            $entrega = $this->deliveryService->cambiarEstado($entrega, $request->estado, $request->only('motivo', 'latitud', 'longitud'));
            return response()->json(['message' => "Estado actualizado a {$request->estado}", 'entrega' => $entrega]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Endpoint móvil simplificado para repartidores (sin auth compleja)
     * PUT /delivery/repartidor/{entregaUuid}/estado
     */
    public function actualizarEstadoMovil(Request $request, string $uuid): JsonResponse
    {
        $entrega = Entrega::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'estado'   => 'required|string|in:en_preparacion,en_camino,entregada,fallida',
            'motivo'   => 'nullable|string|max:255',
            'latitud'  => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ]);

        try {
            $entrega = $this->deliveryService->cambiarEstado($entrega, $request->estado, $request->only('motivo', 'latitud', 'longitud'));
            return response()->json(['message' => 'OK', 'entrega' => $entrega]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Página pública de seguimiento por UUID (sin login)
     * GET /tracking/{uuid}
     */
    public function trackingPublico(string $uuid): JsonResponse
    {
        $data = $this->deliveryService->getTrackingPublico($uuid);

        if (!$data) {
            return response()->json(['message' => 'Entrega no encontrada'], 404);
        }

        return response()->json($data);
    }

    // =================== REPARTIDORES ===================

    public function repartidoresIndex(): JsonResponse
    {
        return response()->json(
            Repartidor::withCount(['entregas' => fn($q) => $q->activas()])
                ->orderBy('nombre')
                ->get()
        );
    }

    public function repartidorStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:255',
            'telefono'         => 'nullable|string|max:20',
            'vehiculo'         => 'nullable|string|max:50',
            'patente'          => 'nullable|string|max:10',
            'zonas_cobertura'  => 'nullable|array',
        ]);
        return response()->json(Repartidor::create($data), 201);
    }

    public function repartidorUpdate(Request $request, int $id): JsonResponse
    {
        $rep = Repartidor::findOrFail($id);
        $rep->update($request->only('nombre', 'telefono', 'vehiculo', 'patente', 'zonas_cobertura', 'disponible', 'activo'));
        return response()->json($rep->fresh());
    }

    // =================== ZONAS DE ENVÍO ===================

    public function zonasIndex(): JsonResponse
    {
        return response()->json(ZonaEnvio::orderBy('nombre')->get());
    }

    public function zonaStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:20|unique:zonas_envio,codigo',
            'costo_envio'         => 'required|integer|min:0',
            'tiempo_estimado_min' => 'nullable|integer|min:1',
        ]);
        return response()->json(ZonaEnvio::create($data), 201);
    }

    public function zonaUpdate(Request $request, int $id): JsonResponse
    {
        $zona = ZonaEnvio::findOrFail($id);
        $zona->update($request->only('nombre', 'costo_envio', 'tiempo_estimado_min', 'activa'));
        return response()->json($zona->fresh());
    }
}
