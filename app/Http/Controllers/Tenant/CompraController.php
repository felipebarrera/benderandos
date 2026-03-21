<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Compra;
use App\Models\Tenant\ItemCompra;
use App\Models\Tenant\MovimientoStock;
use App\Models\Tenant\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Compra::with(['usuario', 'tipoPago'])
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 30))
        );
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Compra::with(['items.producto', 'usuario', 'tipoPago'])->findOrFail($id)
        );
    }

    /**
     * POST /api/compras — Crea compra completa con items y suma stock.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_pago_id'           => 'required|integer|exists:tipos_pago,id',
            'numero_factura'         => 'nullable|string|max:100',
            'items'                  => 'required|array|min:1',
            'items.*.producto_id'    => 'required|integer|exists:productos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.001',
            'items.*.costo_unitario' => 'required|integer|min:0',
        ]);

        $compra = DB::transaction(function () use ($request) {
            $totalCompra = 0;

            $compra = Compra::create([
                'usuario_id'     => $request->user()->id,
                'tipo_pago_id'   => $request->tipo_pago_id,
                'numero_factura' => $request->numero_factura,
                'estado'         => 'completa',
            ]);

            foreach ($request->items as $itemData) {
                $totalItem = (int) round($itemData['costo_unitario'] * $itemData['cantidad']);
                $totalCompra += $totalItem;

                $compra->items()->create([
                    'producto_id'    => $itemData['producto_id'],
                    'cantidad'       => $itemData['cantidad'],
                    'costo_unitario' => $itemData['costo_unitario'],
                    'total_item'     => $totalItem,
                ]);

                // Incrementar stock
                $producto = Producto::find($itemData['producto_id']);
                $stockAntes = $producto->cantidad;
                $producto->increment('cantidad', $itemData['cantidad']);

                // Actualizar costo si aplica
                $producto->update(['costo' => $itemData['costo_unitario']]);

                MovimientoStock::create([
                    'producto_id'   => $producto->id,
                    'tipo'          => 'compra',
                    'cantidad'      => $itemData['cantidad'],
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $producto->fresh()->cantidad,
                    'referencia_id' => $compra->id,
                    'usuario_id'    => $request->user()->id,
                ]);
            }

            $compra->update(['total' => $totalCompra]);

            return $compra;
        });

        return response()->json($compra->load('items.producto'), 201);
    }
}
