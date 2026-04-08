<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\MovimientoStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::activos();

        if ($request->filled('q')) {
            $query->buscar($request->q);
        }

        if ($request->filled('familia')) {
            $query->where('familia', $request->familia);
        }

        $productos = $query->orderBy('nombre')
                           ->paginate($request->input('per_page', 50));

        // Add precio alias for POS compatibility
        $productos->getCollection()->transform(function($p) {
            $p->precio = $p->valor_venta;
            return $p;
        });

        return response()->json($productos);
    }

    /**
     * GET /api/productos/buscar?q=...  (búsqueda rápida para POS)
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);

        $productos = Producto::activos()
            ->buscar($request->q)
            ->limit(20)
            ->get(['id', 'codigo', 'nombre', 'valor_venta', 'cantidad', 'unidad_medida', 'fraccionable'])
            ->map(function($p) {
                $p->precio = $p->valor_venta;
                return $p;
            });

        return response()->json($productos);
    }

    public function show($id): JsonResponse
    {
        return response()->json(Producto::findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo'            => 'nullable|string|max:100',
            'codigo_referencia' => 'nullable|string|max:100',
            'nombre'            => 'required|string|max:500',
            'descripcion'       => 'nullable|string',
            'tipo_producto'     => 'in:stock_fisico,servicio,renta,fraccionado,honorarios',
            'marca'             => 'nullable|string',
            'familia'           => 'nullable|string',
            'subfamilia'        => 'nullable|string',
            'zona'              => 'nullable|string|max:50',
            'proveedor'         => 'nullable|string',
            'valor_venta'       => 'required|integer|min:0',
            'costo'             => 'nullable|integer|min:0',
            'cantidad'          => 'nullable|numeric|min:0',
            'cantidad'          => 'nullable|numeric|min:0',
            'cantidad'          => 'nullable|numeric|min:0',
            'cantidad_minima'   => 'nullable|numeric|min:0',
            'unidad_medida'     => 'nullable|string',
            'fraccionable'      => 'nullable|boolean',
        ]);

        $producto = Producto::create($data);

        return response()->json($producto, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);

        $data = $request->validate([
            'codigo'            => 'nullable|string|max:100',
            'codigo_referencia' => 'nullable|string|max:100',
            'nombre'            => 'sometimes|string|max:500',
            'descripcion'       => 'nullable|string',
            'tipo_producto'     => 'in:stock_fisico,servicio,renta,fraccionado,honorarios',
            'marca'             => 'nullable|string',
            'familia'           => 'nullable|string',
            'subfamilia'        => 'nullable|string',
            'zona'              => 'nullable|string|max:50',
            'proveedor'         => 'nullable|string',
            'valor_venta'       => 'sometimes|integer|min:0',
            'costo'             => 'nullable|integer|min:0',
            'cantidad'          => 'nullable|numeric|min:0',
            'cantidad'          => 'nullable|numeric|min:0',
            'cantidad_minima'   => 'nullable|numeric|min:0',
            'unidad_medida'     => 'nullable|string',
            'fraccionable'      => 'nullable|boolean',
            'estado'            => 'in:activo,inactivo,agotado',
        ]);

        $producto->update($data);

        return response()->json($producto);
    }

    /**
     * POST /api/productos/{id}/ajuste-stock
     */
    public function ajusteStock(Request $request, $id): JsonResponse
    {
        $request->validate([
            'cantidad' => 'required|numeric',
            'notas'    => 'nullable|string',
        ]);

        $producto = Producto::findOrFail($id);
        $stockAntes = $producto->cantidad;
        $nuevaCantidad = $stockAntes + $request->cantidad;

        $producto->update(['cantidad' => $nuevaCantidad]);

        MovimientoStock::create([
            'producto_id'   => $producto->id,
            'tipo'          => 'ajuste_manual',
            'cantidad'      => $request->cantidad,
            'stock_antes'   => $stockAntes,
            'stock_despues' => $nuevaCantidad,
            'usuario_id'    => $request->user()->id,
            'notas'         => $request->notas,
        ]);

        return response()->json([
            'producto'       => $producto->fresh(),
            'stock_anterior' => $stockAntes,
            'stock_nuevo'    => $nuevaCantidad,
        ]);
    }
}
