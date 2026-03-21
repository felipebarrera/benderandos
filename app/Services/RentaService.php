<?php

namespace App\Services;

use App\Models\Tenant\ItemVenta;
use App\Models\Tenant\Renta;
use App\Models\Tenant\MovimientoStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RentaService
{
    /**
     * Inicia una nueva renta basada en un ítem de venta.
     */
    public function iniciarRenta(ItemVenta $item, Carbon $inicio, Carbon $fin): Renta
    {
        // Descontar inventario (marcar como ocupado)
        $item->producto->decrement('cantidad', $item->cantidad);

        MovimientoStock::registrar(
            $item->producto,
            'venta',
            -$item->cantidad,
            $item->venta_id,
            "Inicio de renta para venta #{$item->venta_id}"
        );

        Log::info("Renta iniciada para producto {$item->producto->nombre} ({$item->producto_id})");

        return Renta::create([
            'item_venta_id'  => $item->id,
            'producto_id'    => $item->producto_id,
            'cliente_id'     => $item->venta->cliente_id,
            'inicio_real'    => $inicio,
            'fin_programado' => $fin,
            'estado'         => 'activa',
        ]);
    }

    /**
     * Extiende el tiempo programado de una renta y suma un cargo extra.
     */
    public function extenderRenta(Renta $renta, int $minutosExtra, int $cargo): Renta
    {
        $renta->update([
            'fin_programado' => $renta->fin_programado->addMinutes($minutosExtra),
            'cargo_extra'    => $renta->cargo_extra + $cargo,
            'estado'         => 'extendida', // O mantener 'activa' si aplica, pero el requerimiento dice 'extendida'
        ]);

        return $renta;
    }

    /**
     * Finaliza y devuelve una renta, re-abasteciendo el stock.
     */
    public function devolverRenta(Renta $renta): Renta
    {
        // Devolver al inventario (marcar como libre)
        $renta->producto->increment('cantidad', $renta->itemVenta->cantidad);

        $renta->update([
            'fin_real' => now(),
            'estado'   => 'devuelta'
        ]);

        MovimientoStock::registrar(
            $renta->producto,
            'devolucion_renta',
            $renta->itemVenta->cantidad,
            $renta->itemVenta->venta_id,
            "Devolución de renta #{$renta->id}"
        );

        return $renta;
    }
}
