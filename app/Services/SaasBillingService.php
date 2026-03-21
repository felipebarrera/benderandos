<?php

namespace App\Services;

use App\Models\Tenant\SaasCliente;
use App\Models\Tenant\SaasCobro;
use Illuminate\Support\Carbon;

class SaasBillingService
{
    /**
     * Generar los cobros mensuales pendientes. Default: se ejecuta el día 1 de cada mes.
     */
    public function generarCobrosDelMes(Carbon $periodo = null)
    {
        $periodo = $periodo ?? now()->startOfMonth();

        // Obtener clientes activos o morosos (no suspendidos)
        $clientesFacturables = SaasCliente::whereIn('estado', ['activo', 'moroso'])
            ->where('ciclo_facturacion', 'mensual')
            ->get();

        $creados = 0;
        foreach ($clientesFacturables as $cliente) {
            // Evitar duplicar cobros para el mismo periodo
            $existe = SaasCobro::where('cliente_id', $cliente->id)
                ->where('periodo', $periodo->format('Y-m-d'))
                ->exists();

            if (!$existe) {
                // El total con descuentos aplicados extra permanentemente (sobre precio_actual)
                $descuentoMensual = round($cliente->precio_actual * ($cliente->descuento_pct / 100));
                
                SaasCobro::create([
                    'cliente_id'        => $cliente->id,
                    'periodo'           => $periodo->format('Y-m-d'),
                    'monto'             => $cliente->precio_actual,
                    'descuento'         => $descuentoMensual,
                    'total'             => $cliente->precio_actual - $descuentoMensual,
                    'estado'            => 'pendiente',
                    'fecha_vencimiento' => $periodo->copy()->addDays(5)->format('Y-m-d'), // Vence el día 5
                ]);
                $creados++;
            }
        }

        return $creados;
    }

    /**
     * Marcar un cobro como pagado manualmente o por webhook automático
     */
    public function registrarPago(SaasCobro $cobro, string $metodo, string $referencia = null)
    {
        $cobro->update([
            'estado'          => 'pagado',
            'fecha_pago'      => now(),
            'metodo_pago'     => $metodo,
            'referencia_pago' => $referencia
        ]);

        // Si el cliente estaba moroso, y no debe nada más, volverlo activo
        if ($cobro->cliente->estado === 'moroso') {
            $deudaPendiente = SaasCobro::where('cliente_id', $cobro->cliente_id)
                ->where('estado', 'vencido')
                ->where('id', '!=', $cobro->id)
                ->exists();

            if (!$deudaPendiente) {
                $cobro->cliente->update(['estado' => 'activo']);
            }
        }
        
        return $cobro;
    }

    /**
     * Evaluar vencimientos (Ej: Ejecutar día 6). Pasa cobros pendientes a vencidos y cliente a moroso
     */
    public function procesarVencimientos()
    {
        $hoy = today()->format('Y-m-d');
        
        // Cobros no pagados, cuya fecha ya pasó
        $vencidos = SaasCobro::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', $hoy)
            ->get();

        foreach ($vencidos as $cobro) {
            $cobro->update(['estado' => 'vencido']);
            $cobro->cliente->update(['estado' => 'moroso']);
        }

        return count($vencidos);
    }
}
