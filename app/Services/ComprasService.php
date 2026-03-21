<?php

namespace App\Services;

use App\Models\Tenant\ItemOrdenCompra;
use App\Models\Tenant\ItemRecepcion;
use App\Models\Tenant\MovimientoStock;
use App\Models\Tenant\OrdenCompra;
use App\Models\Tenant\Producto;
use App\Models\Tenant\ProductoProveedor;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\RecepcionCompra;
use App\Models\Tenant\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprasService
{
    /**
     * Crear Orden de Compra
     */
    public function crearOrden(array $data, Usuario $usuario): OrdenCompra
    {
        return DB::transaction(function () use ($data, $usuario) {
            $subtotal = 0;

            $oc = OrdenCompra::create([
                'codigo'                => OrdenCompra::generarCodigo(),
                'proveedor_id'          => $data['proveedor_id'],
                'usuario_id'            => $usuario->id,
                'estado'                => 'borrador',
                'fecha_entrega_esperada' => $data['fecha_entrega_esperada'] ?? null,
                'notas'                 => $data['notas'] ?? null,
                'origen'                => $data['origen'] ?? 'manual',
            ]);

            foreach ($data['items'] as $item) {
                $totalItem = (int) round($item['precio_unitario'] * $item['cantidad']);
                $subtotal += $totalItem;

                $oc->items()->create([
                    'producto_id'        => $item['producto_id'],
                    'cantidad_solicitada' => $item['cantidad'],
                    'precio_unitario'    => $item['precio_unitario'],
                    'total_item'         => $totalItem,
                ]);
            }

            // Aplicar descuento por volumen del proveedor
            $proveedor = Proveedor::find($data['proveedor_id']);
            $descPct = $data['descuento_pct'] ?? $proveedor?->descuento_volumen_pct ?? 0;
            $descMonto = (int) round($subtotal * $descPct / 100);

            $oc->update([
                'subtotal'        => $subtotal,
                'descuento_pct'   => $descPct,
                'descuento_monto' => $descMonto,
                'total'           => $subtotal - $descMonto,
            ]);

            return $oc->load('items.producto', 'proveedor');
        });
    }

    /**
     * Autorizar OC
     */
    public function autorizar(OrdenCompra $oc, Usuario $usuario): OrdenCompra
    {
        if (!$oc->puedeAutorizar()) {
            throw new \RuntimeException("OC {$oc->codigo} no puede ser autorizada en estado {$oc->estado}.");
        }

        $oc->update([
            'estado'         => 'autorizada',
            'autorizado_por' => $usuario->id,
            'autorizada_at'  => now(),
        ]);

        return $oc->fresh();
    }

    /**
     * Marcar OC como enviada al proveedor
     */
    public function enviar(OrdenCompra $oc): OrdenCompra
    {
        if (!$oc->puedeEnviar()) {
            throw new \RuntimeException("OC {$oc->codigo} no puede enviarse en estado {$oc->estado}.");
        }

        $oc->update([
            'estado'    => 'enviada',
            'enviada_at' => now(),
        ]);

        return $oc->fresh();
    }

    /**
     * Registrar recepción de mercancía
     */
    public function registrarRecepcion(OrdenCompra $oc, array $data, Usuario $usuario): RecepcionCompra
    {
        if (!$oc->puedeRecibir()) {
            throw new \RuntimeException("OC {$oc->codigo} no puede recibir mercancía en estado {$oc->estado}.");
        }

        return DB::transaction(function () use ($oc, $data, $usuario) {
            $recepcion = RecepcionCompra::create([
                'orden_compra_id' => $oc->id,
                'usuario_id'      => $usuario->id,
                'numero_guia'     => $data['numero_guia'] ?? null,
                'observaciones'   => $data['observaciones'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $itemOrden = ItemOrdenCompra::findOrFail($itemData['item_orden_id']);
                $cantidadAceptada = $itemData['cantidad_recibida'] - ($itemData['cantidad_rechazada'] ?? 0);

                $recepcion->items()->create([
                    'item_orden_id'     => $itemData['item_orden_id'],
                    'producto_id'       => $itemOrden->producto_id,
                    'cantidad_recibida' => $itemData['cantidad_recibida'],
                    'cantidad_rechazada' => $itemData['cantidad_rechazada'] ?? 0,
                    'motivo_rechazo'    => $itemData['motivo_rechazo'] ?? null,
                    'lote'              => $itemData['lote'] ?? null,
                    'fecha_vencimiento' => $itemData['fecha_vencimiento'] ?? null,
                ]);

                // Actualizar cantidad recibida en item de OC
                $itemOrden->increment('cantidad_recibida', $cantidadAceptada);

                // Incrementar stock solo con cantidad aceptada (rechazada NO)
                if ($cantidadAceptada > 0) {
                    $producto = Producto::find($itemOrden->producto_id);
                    $stockAntes = $producto->cantidad;
                    $producto->increment('cantidad', $cantidadAceptada);

                    // Actualizar costo si aplica
                    $producto->update(['costo' => $itemOrden->precio_unitario]);

                    MovimientoStock::create([
                        'producto_id'   => $producto->id,
                        'tipo'          => 'compra',
                        'cantidad'      => $cantidadAceptada,
                        'stock_antes'   => $stockAntes,
                        'stock_despues' => $producto->fresh()->cantidad,
                        'referencia_id' => $oc->id,
                        'usuario_id'    => $usuario->id,
                    ]);
                }
            }

            // Verificar si OC está completa o parcial
            $oc->load('items');
            $todosCompletos = $oc->items->every(fn($item) => $item->estaCompletoRecibido());
            $oc->update(['estado' => $todosCompletos ? 'completa' : 'parcial']);

            return $recepcion->load('items.producto');
        });
    }

    /**
     * Detectar productos bajo stock mínimo y sugerir OC
     */
    public function detectarStockBajo(): array
    {
        return Producto::whereColumn('cantidad', '<=', 'cantidad_minima')
            ->where('cantidad_minima', '>', 0)
            ->with(['proveedores' => function ($q) {
                $q->wherePivot('es_principal', true);
            }])
            ->get()
            ->map(function ($producto) {
                $provPrincipal = $producto->proveedores->first();
                return [
                    'producto_id'   => $producto->id,
                    'nombre'        => $producto->nombre,
                    'stock_actual'  => $producto->cantidad,
                    'stock_minimo'  => $producto->cantidad_minima,
                    'proveedor'     => $provPrincipal?->nombre,
                    'proveedor_id'  => $provPrincipal?->id,
                    'precio'        => $provPrincipal?->pivot?->precio_unitario,
                    'sugerido'      => max(0, ($producto->cantidad_minima * 2) - $producto->cantidad),
                ];
            })
            ->toArray();
    }

    /**
     * Dashboard de compras
     */
    public function getDashboard(): array
    {
        return [
            'oc_pendientes'     => OrdenCompra::pendientes()->count(),
            'oc_mes'            => OrdenCompra::whereMonth('created_at', now()->month)->count(),
            'total_mes'         => OrdenCompra::where('estado', 'completa')->whereMonth('created_at', now()->month)->sum('total'),
            'proveedores_activos' => Proveedor::activos()->count(),
            'alertas_stock'     => Producto::whereColumn('cantidad', '<=', 'cantidad_minima')->where('cantidad_minima', '>', 0)->count(),
        ];
    }
}
