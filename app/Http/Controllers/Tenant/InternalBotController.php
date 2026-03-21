<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Producto;
use App\Services\Tenant\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalBotController extends Controller
{
    /**
     * Buscar productos y su stock actual
     */
    public function getStock(Request $request): JsonResponse
    {
        $query = $request->input('q');
        
        $productos = Producto::where('nombre', 'ilike', "%{$query}%")
            ->orWhere('codigo', 'ilike', "%{$query}%")
            ->limit(10)
            ->get(['id', 'nombre', 'codigo', 'precio', 'stock', 'tipo']);

        return response()->json($productos);
    }

    /**
     * Buscar clientes por RUT o Teléfono
     */
    public function buscarCliente(Request $request): JsonResponse
    {
        $rut = $request->input('rut');
        $telefono = $request->input('telefono');

        $cliente = Cliente::when($rut, function($q) use ($rut) {
                return $q->where('rut', $rut);
            })
            ->when($telefono, function($q) use ($telefono) {
                return $q->where('telefono', 'like', "%{$telefono}%");
            })
            ->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        return response()->json($cliente);
    }

    /**
     * Crear una venta remota (pedido desde WhatsApp)
     */
    public function crearVentaRemota(Request $request, VentaService $ventaService): JsonResponse
    {
        // Validar datos mínimos
        $data = $request->validate([
            'cliente_id' => 'required|exists:tenant.clientes,id',
            'items'      => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:tenant.productos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.01',
            'tipo_pago_id'        => 'nullable|exists:tenant.tipos_pago,id',
            'observaciones'       => 'nullable|string',
        ]);

        try {
            // Forzamos el estado a 'pendiente' para ser procesado en el POS
            $data['estado'] = 'pendiente';
            $data['es_remota'] = true;
            
            // Usamos el service existente para la lógica de creación
            $venta = $ventaService->crearVenta($data);

            Log::info("Venta remota creada desde Bot WA: ID {$venta->id}");

            // H8: Notificar al bot que el ERP recibió el pedido correctamente
            app(\App\Services\WhatsAppService::class)->notificarEvento('pedido_recibido', [
                'venta_id' => $venta->id,
                'total'    => $venta->total,
                'status'   => 'pendiente'
            ]);

            return response()->json([
                'message' => 'Pedido recibido correctamente',
                'venta_id' => $venta->id,
                'total' => $venta->total
            ]);

        } catch (\Exception $e) {
            Log::error("Error al crear venta remota bot: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar pedido: ' . $e->getMessage()], 500);
        }
    }
}
