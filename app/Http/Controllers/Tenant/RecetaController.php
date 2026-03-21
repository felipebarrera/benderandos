<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Produccion;
use App\Models\Tenant\Receta;
use App\Services\RecetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecetaController extends Controller
{
    public function __construct(
        private RecetaService $recetaService
    ) {}

    // =================== RECETAS ===================

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'kpis'    => $this->recetaService->getDashboard(),
            'reporte' => $this->recetaService->reporteCostoVsPrecio(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Receta::with('ingredientes.producto')->orderBy('nombre');
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->boolean('solo_activas', true)) {
            $query->activas();
        }
        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Receta::with(['ingredientes.producto', 'producciones' => fn($q) => $q->latest()->limit(10)])->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'                    => 'required|string|max:255',
            'descripcion'               => 'nullable|string',
            'categoria'                 => 'nullable|string|max:50',
            'producto_id'               => 'nullable|integer|exists:productos,id',
            'porciones_por_batch'       => 'required|integer|min:1',
            'tiempo_preparacion_min'    => 'nullable|integer|min:0',
            'costo_mano_obra'           => 'nullable|integer|min:0',
            'porcentaje_merma'          => 'nullable|numeric|min:0|max:100',
            'precio_venta'              => 'nullable|integer|min:0',
            'instrucciones'             => 'nullable|string',
            'ingredientes'              => 'required|array|min:1',
            'ingredientes.*.producto_id' => 'required|integer|exists:productos,id',
            'ingredientes.*.cantidad'   => 'required|numeric|min:0.001',
            'ingredientes.*.unidad'     => 'nullable|string|max:20',
        ]);

        $receta = $this->recetaService->guardarReceta($data);
        return response()->json($receta, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $receta = Receta::findOrFail($id);
        $data = $request->validate([
            'nombre'                    => 'nullable|string|max:255',
            'descripcion'               => 'nullable|string',
            'categoria'                 => 'nullable|string|max:50',
            'producto_id'               => 'nullable|integer|exists:productos,id',
            'porciones_por_batch'       => 'nullable|integer|min:1',
            'tiempo_preparacion_min'    => 'nullable|integer|min:0',
            'costo_mano_obra'           => 'nullable|integer|min:0',
            'porcentaje_merma'          => 'nullable|numeric|min:0|max:100',
            'precio_venta'              => 'nullable|integer|min:0',
            'instrucciones'             => 'nullable|string',
            'ingredientes'              => 'nullable|array|min:1',
            'ingredientes.*.producto_id' => 'required|integer|exists:productos,id',
            'ingredientes.*.cantidad'   => 'required|numeric|min:0.001',
            'ingredientes.*.unidad'     => 'nullable|string|max:20',
        ]);

        $receta = $this->recetaService->guardarReceta($data, $receta);
        return response()->json($receta);
    }

    public function recalcularCostos($id): JsonResponse
    {
        $receta = Receta::with('ingredientes.producto')->findOrFail($id);
        $receta = $this->recetaService->recalcularCostos($receta);
        return response()->json(['message' => 'Costos recalculados', 'receta' => $receta]);
    }

    // =================== PRODUCCIÓN ===================

    public function verificarStock(Request $request, int $id): JsonResponse
    {
        $receta = Receta::with('ingredientes.producto')->findOrFail($id);
        $batches = $request->input('batches', 1);
        $faltantes = $this->recetaService->verificarStock($receta, $batches);

        return response()->json([
            'puede_producir' => empty($faltantes),
            'faltantes'      => $faltantes,
        ]);
    }

    public function producir(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'batches'       => 'required|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        $receta = Receta::with('ingredientes.producto')->findOrFail($id);

        // Verificar stock primero
        $faltantes = $this->recetaService->verificarStock($receta, $request->batches);
        if (!empty($faltantes)) {
            return response()->json([
                'message'   => 'Stock insuficiente para producir',
                'faltantes' => $faltantes,
            ], 422);
        }

        $produccion = $this->recetaService->producir(
            $receta,
            $request->batches,
            $request->user(),
            $request->observaciones,
        );

        return response()->json([
            'message'    => "Producción completada: {$produccion->porciones_producidas} porciones",
            'produccion' => $produccion,
        ], 201);
    }

    public function historialProducciones(Request $request): JsonResponse
    {
        return response()->json(
            Produccion::with(['receta', 'usuario'])
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 25))
        );
    }

    public function reporteCostos(): JsonResponse
    {
        return response()->json($this->recetaService->reporteCostoVsPrecio());
    }
}
