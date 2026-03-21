<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Producto;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendWhatsAppNotification;

class WhatsAppPedidoController extends Controller
{
    /**
     * POST /webhook/whatsapp/pedido-remoto
     * El bot envía un JSON con los productos que el cliente quiere.
     */
    public function crear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'    => 'required|string',
            'cliente'      => 'required|array',
            'cliente.nombre'   => 'required|string',
            'cliente.whatsapp' => 'required|string',
            'items'        => 'required|array|min:1',
            'items.*.codigo'   => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        $tenant = Tenant::find($data['tenant_id']);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }

        tenancy()->initialize($tenant);

        try {
            $venta = DB::transaction(function () use ($data) {
                // 1. Buscar o crear cliente por WhatsApp
                $cliente = Cliente::firstOrCreate(
                    ['whatsapp' => $data['cliente']['whatsapp']],
                    ['nombre'   => $data['cliente']['nombre']]
                );

                // 2. Crear Venta en estado remota_pendiente
                $venta = Venta::create([
                    'cliente_id' => $cliente->id,
                    'estado'     => 'remota_pendiente',
                    'subtotal'   => 0,
                    'total'      => 0,
                    'es_deuda'   => false,
                ]);

                $subtotal = 0;

                // 3. Procesar Items
                foreach ($data['items'] as $itemData) {
                    $producto = Producto::where('codigo', $itemData['codigo'])->first();
                    
                    if (!$producto) {
                        throw new \Exception("Producto con código {$itemData['codigo']} no encontrado", 404);
                    }

                    $cantidad = $itemData['cantidad'];
                    $totalItem = $producto->precio * $cantidad;

                    $venta->items()->create([
                        'producto_id'     => $producto->id,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $producto->precio,
                        'total_item'      => $totalItem,
                    ]);

                    $subtotal += $totalItem;
                }

                $venta->update([
                    'subtotal' => $subtotal,
                    'total'    => $subtotal, // asumiendo sin dto desde wha
                ]);

                return $venta;
            });
            
            // 4. Despachar validación/mensaje al cliente
            SendWhatsAppNotification::dispatch('pedido_remoto', $venta);

            tenancy()->end();

            return response()->json([
                'success'  => true,
                'venta_id' => $venta->id,
                'uuid'     => $venta->uuid,
                'total'    => $venta->total,
                'estado'   => $venta->estado,
            ], 201);

        } catch (\Exception $e) {
            tenancy()->end();
            $code = $e->getCode() === 404 ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    /**
     * GET /webhook/whatsapp/pedido/{uuid}
     */
    public function estado(string $uuid, Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        if (!$tenantId) {
            return response()->json(['error' => 'tenant_id es requerido'], 400);
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }

        tenancy()->initialize($tenant);
        
        $venta = Venta::where('uuid', $uuid)->first();
        tenancy()->end();

        if (!$venta) {
             return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        return response()->json([
            'uuid'   => $venta->uuid,
            'estado' => $venta->estado,
            'total'  => $venta->total,
        ]);
    }
}
