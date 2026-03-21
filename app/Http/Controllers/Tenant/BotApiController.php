<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Venta;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse; // Added for JsonResponse return types

class BotApiController extends Controller
{

    /**
     * Consultar stock de un producto por SKU.
     */
    public function stock(string $sku): JsonResponse
    {
        $producto = Producto::where('codigo', $sku)->first();

        if (! $producto || $producto->estado !== 'activo') {
            return response()->json(['error' => 'Producto no encontrado o inactivo'], 404);
        }

        return response()->json([
            'sku' => $producto->codigo,
            'nombre' => $producto->nombre,
            'stock_actual' => $producto->cantidad,
            'unidad_medida' => $producto->unidad_medida,
        ]);
    }

    /**
     * Consultar precio de un producto por SKU.
     */
    public function precio(string $sku): JsonResponse
    {
        $producto = Producto::where('codigo', $sku)->first();

        if (! $producto || $producto->estado !== 'activo') {
            return response()->json(['error' => 'Producto no encontrado o inactivo'], 404);
        }

        return response()->json([
            'sku'    => $producto->codigo,
            'nombre'  => $producto->nombre,
            'precio'  => $producto->valor_venta
        ]);
    }

    /**
     * Consultar datos de un cliente por teléfono.
     */
    public function cliente($telefono)
    {
        $cliente = Cliente::where('telefono', 'LIKE', "%$telefono%")->first();
        if (!$cliente) return response()->json(['message' => 'Cliente no encontrado'], 404);

        return response()->json([
            'id'     => $cliente->id,
            'nombre' => $cliente->nombre,
            'email'  => $cliente->email,
            'rut'    => $cliente->rut
        ]);
    }

    /**
     * Crear un pedido desde el bot.
     */
    public function crearPedido(Request $request, VentaService $ventaService)
    {
        $data = $request->validate([
            'telefono' => 'required',
            'items'    => 'required|array',
            'items.*.sku'      => 'required|exists:productos,codigo',
            'items.*.cantidad' => 'required|numeric|min:0.1',
        ]);

        try {
            return DB::transaction(function() use ($data, $ventaService) {
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $data['telefono']],
                    ['nombre' => 'Cliente WhatsApp ' . substr($data['telefono'], -4)]
                );

                $venta = Venta::create([
                    'cliente_id' => $cliente->id,
                    'estado'     => 'remota_pendiente',
                    'total'      => 0,
                    'tipo'       => 'venta',
                ]);

                foreach ($data['items'] as $item) {
                    $producto = Producto::where('codigo', $item['sku'])->first();

                    if ($producto && $producto->estado === 'activo') {
                        // Aquí idealmente validar stock
                        $ventaService->agregarItem($venta, [
                            'producto_id' => $producto->id,
                            'cantidad' => $item['cantidad']
                        ]);
                    } else {
                        // Optionally handle inactive or not found products in the order
                        // For now, we just skip them or could throw an error
                        throw new \Exception("Producto con SKU {$item['sku']} no encontrado o inactivo.");
                    }
                }

                return response()->json([
                    'message'  => 'Pedido creado exitosamente',
                    'venta_id' => $venta->id,
                    'total'    => $venta->total,
                    'estado'   => $venta->estado
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Consultar estado de un pedido.
     */
    public function estadoPedido($id)
    {
        $venta = Venta::find($id);
        if (!$venta) return response()->json(['message' => 'Pedido no encontrado'], 404);

        return response()->json([
            'id'     => $venta->id,
            'estado' => $venta->estado,
            'total'  => $venta->total
        ]);
    }
}
