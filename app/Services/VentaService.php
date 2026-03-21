<?php

namespace App\Services;

use App\Models\Tenant\ItemVenta;
use App\Models\Tenant\MovimientoStock;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Deuda;
use App\Models\Tenant\Usuario;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\EmitirDteJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function __construct(
        private RentaService $rentaService,
        private DeliveryService $deliveryService
    ) {}

    /**
     * Crea una venta nueva en estado "abierta".
     */
    public function crear(Usuario $usuario, ?int $clienteId = null): Venta
    {
        return Venta::create([
            'usuario_id' => $usuario->id,
            'cliente_id' => $clienteId,
            'estado'     => 'abierta',
        ]);
    }

    /**
     * Agrega un item a la venta abierta.
     */
    public function agregarItem(Venta $venta, array $datos): ItemVenta
    {
        if (! $venta->permiteAgregarItems()) {
            throw new \RuntimeException('La venta está en caja, no se pueden agregar ítems');
        }

        $producto = Producto::findOrFail($datos['producto_id']);

        $cantidad = $datos['cantidad'] ?? 1;

        // --- T3.1 Lógica Fraccionados ---
        if ($producto->tipo_producto === 'fraccionado') {
            if ($cantidad < $producto->unidad_minima) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "cantidad" => "La cantidad mínima para {$producto->nombre} es {$producto->unidad_minima} {$producto->unidad_medida}"
                ]);
            }
        }

        $precio   = $datos['precio_unitario'] ?? $producto->valor_venta;
        $total    = (int) round($precio * $cantidad);

        $item = $venta->items()->create([
            'producto_id'     => $producto->id,
            'operario_id'     => $datos['operario_id'] ?? null,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precio,
            'costo_unitario'  => $producto->costo,
            'total_item'      => $total,
            'notas_item'      => $datos['notas_item'] ?? null,
        ]);

        if ($producto->tipo_producto === 'renta' && isset($datos['inicio_renta'], $datos['fin_renta'])) {
            $inicio = Carbon::parse($datos['inicio_renta']);
            $fin    = Carbon::parse($datos['fin_renta']);
            $this->rentaService->iniciarRenta($item, $inicio, $fin);
        }

        $this->recalcularTotales($venta);

        return $item;
    }

    /**
     * Elimina un item de la venta.
     */
    public function quitarItem(Venta $venta, int $itemId): void
    {
        $item = $venta->items()->findOrFail($itemId);

        if ($item->producto->tipo_producto === 'renta') {
            $renta = \App\Models\Tenant\Renta::where('item_venta_id', $item->id)->first();
            if ($renta && $renta->estado === 'activa') {
                $this->rentaService->devolverRenta($renta);
            }
        }

        $item->delete();
        $this->recalcularTotales($venta);
    }

    /**
     * Confirma (paga) la venta: descuenta stock y cambia estado.
     */
    public function confirmar(Venta $venta, array $datos, Usuario $usuario): Venta
    {
        return DB::transaction(function () use ($venta, $datos, $usuario) {
            $venta->load('items.producto');

            // Descontar stock por cada item
            foreach ($venta->items as $item) {
                $this->descontarStock($item, $usuario);
            }

            $esDeuda = $datos['es_deuda'] ?? false;

            $venta->update([
                'estado'           => $esDeuda ? 'fiada' : 'pagada',
                'tipo_pago_id'     => $datos['tipo_pago_id'] ?? null,
                'cajero_id'        => $usuario->id,
                'descuento_monto'  => $datos['descuento_monto'] ?? 0,
                'descuento_pct'    => $datos['descuento_pct'] ?? 0,
                'es_deuda'         => $esDeuda,
                'numero_documento' => $datos['numero_documento'] ?? null,
                'tipo_documento'   => $datos['tipo_documento'] ?? null,
                'notas'            => $datos['notas'] ?? null,
                'pagado_at'        => $esDeuda ? null : now(),
            ]);

            $this->recalcularTotales($venta);

            // Crear deuda si aplica
            if ($esDeuda) {
                Deuda::create([
                    'venta_id'   => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'valor'      => $venta->total,
                    'pagada'     => false,
                    'vencimiento_at' => now()->addDays(7), // Default 7 días
                ]);
            }

            // Disparar comprobante vía WhatsApp si el cliente tiene número
            // Disparar comprobante vía WhatsApp si el cliente tiene número
            if ($venta->cliente && $venta->cliente->whatsapp) {
                SendWhatsAppNotification::dispatch($venta, 'comprobante')
                    ->delay(now()->addSeconds(2));
                
                // H8: Notificar al bot el evento de confirmación
                app(\App\Services\WhatsAppService::class)->notificarEvento('venta_confirmada', [
                    'venta_id' => $venta->id,
                    'uuid'     => $venta->uuid,
                    'total'    => $venta->total,
                    'cliente'  => $venta->cliente->nombre,
                    'telefono' => $venta->cliente->whatsapp
                ]);
            }

            // H9: Emitir DTE automáticamente (async)
            EmitirDteJob::dispatch($venta->id);

            // H11: Delivery y Logística
            if ($venta->tipo_entrega === 'envio') {
                $this->deliveryService->crearEntrega($venta, $datos['delivery_info'] ?? []);
            }

            return $venta->fresh();
        });
    }

    /**
     * Anula la venta y devuelve el stock.
     */
    public function anular(Venta $venta, Usuario $usuario): Venta
    {
        return DB::transaction(function () use ($venta, $usuario) {
            $venta->load('items.producto');

            foreach ($venta->items as $item) {
                $this->devolverStock($item, $usuario);
            }

            $venta->update(['estado' => 'anulada']);

            return $venta->fresh();
        });
    }

    // --- Helpers internos ---

    private function recalcularTotales(Venta $venta): void
    {
        $subtotal = $venta->items()->sum('total_item');
        $descuento = $venta->descuento_monto;

        if ($venta->descuento_pct > 0) {
            $descuento = (int) round($subtotal * $venta->descuento_pct / 100);
        }

        $venta->update([
            'subtotal' => $subtotal,
            'total'    => max(0, $subtotal - $descuento),
        ]);
    }

    private function descontarStock(ItemVenta $item, Usuario $usuario): void
    {
        $producto = $item->producto;

        if ($producto->tipo_producto === 'servicio' || $producto->tipo_producto === 'honorarios') {
            return; // No hay stock físico
        }

        $stockAntes = $producto->cantidad;
        $producto->decrement('cantidad', $item->cantidad);

        MovimientoStock::create([
            'producto_id'   => $producto->id,
            'tipo'          => 'venta',
            'cantidad'      => -$item->cantidad,
            'stock_antes'   => $stockAntes,
            'stock_despues' => $producto->fresh()->cantidad,
            'referencia_id' => $item->venta_id,
            'usuario_id'    => $usuario->id,
        ]);
    }

    private function devolverStock(ItemVenta $item, Usuario $usuario): void
    {
        $producto = $item->producto;

        if ($producto->tipo_producto === 'servicio' || $producto->tipo_producto === 'honorarios') {
            return;
        }

        $stockAntes = $producto->cantidad;
        $producto->increment('cantidad', $item->cantidad);

        MovimientoStock::create([
            'producto_id'   => $producto->id,
            'tipo'          => 'devolucion',
            'cantidad'      => $item->cantidad,
            'stock_antes'   => $stockAntes,
            'stock_despues' => $producto->fresh()->cantidad,
            'referencia_id' => $item->venta_id,
            'usuario_id'    => $usuario->id,
        ]);
    }
}
