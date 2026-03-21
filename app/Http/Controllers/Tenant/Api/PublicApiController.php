<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WebhookService;

class PublicApiController extends Controller
{
    public function __construct(
        private VentaService $ventaService,
        private WebhookService $webhookService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/public/productos",
     *     summary="Listar el catálogo de productos",
     *     tags={"Catálogo"}
     * )
     */
    public function productos(Request $request): JsonResponse
    {
        $query = Producto::where('estado', 'activo');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw("LOWER(nombre) LIKE ?", ["%{$search}%"])
                  ->orWhere('codigo', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/clientes",
     *     summary="Listar clientes (CRM / Deudas)",
     *     tags={"CRM"}
     * )
     */
    public function clientes(Request $request): JsonResponse
    {
        $query = Cliente::query();

        if ($request->filled('rut')) {
            $query->where('rut', $request->rut);
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/stock/{sku}",
     *     summary="Consultar stock de un producto específico por SKU",
     *     tags={"Inventario"}
     * )
     */
    public function stock(string $sku): JsonResponse
    {
        $producto = Producto::where('codigo', $sku)->firstOrFail();
        
        return response()->json([
            'sku' => $producto->codigo,
            'nombre' => $producto->nombre,
            'stock_actual' => $producto->cantidad,
            'unidad_medida' => $producto->unidad_medida,
            'precio_venta' => $producto->valor_venta
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/public/ventas",
     *     summary="Crear un pedido o venta externa",
     *     tags={"Ventas"}
     * )
     */
    public function storeVenta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,completada',
            'metodo_pago' => 'nullable|string',
            'origen' => 'nullable|string', // ej: 'e-commerce'
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|numeric|min:0.1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $venta = Venta::create([
                'cliente_id' => $data['cliente_id'] ?? null,
                'usuario_id' => $request->user()->id, // El token (API User)
                'monto_total'=> $data['monto_total'],
                'estado'     => 'pendiente', // Siempre entra pendiente inicialmente
                'vendedor_id'=> $request->user()->id,
                'origen'     => $data['origen'] ?? 'api'
            ]);

            foreach ($data['items'] as $item) {
                $venta->items()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario']
                ]);
            }

            // Si viene completada, la procesamos
            if ($data['estado'] === 'completada') {
                $this->ventaService->confirmar($venta, [
                    'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                    'monto_pagado' => $data['monto_total'],
                ], $request->user());
            }

            // Disparar Webhook nativo de `venta.creada`
            $this->webhookService->dispatchEvent('venta.creada', [
                'venta_id' => $venta->id,
                'total' => $venta->monto_total,
                'estado' => $venta->estado
            ]);

            DB::commit();
            return response()->json(['message' => 'Venta registrada vía API', 'venta' => $venta->load('items')], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar venta desde API: '.$e->getMessage()], 500);
        }
    }
}
