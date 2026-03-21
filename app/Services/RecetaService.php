<?php

namespace App\Services;

use App\Models\Tenant\IngredienteReceta;
use App\Models\Tenant\ItemProduccion;
use App\Models\Tenant\MovimientoStock;
use App\Models\Tenant\Produccion;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Receta;
use App\Models\Tenant\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecetaService
{
    /**
     * Crear o actualizar receta con ingredientes
     */
    public function guardarReceta(array $data, ?Receta $recetaExistente = null): Receta
    {
        return DB::transaction(function () use ($data, $recetaExistente) {
            $receta = $recetaExistente
                ? tap($recetaExistente)->update($data)
                : Receta::create($data);

            if (isset($data['ingredientes'])) {
                $receta->ingredientes()->delete();

                foreach ($data['ingredientes'] as $ing) {
                    $producto = Producto::find($ing['producto_id']);
                    $costoUnitario = $producto->costo ?? 0;

                    $receta->ingredientes()->create([
                        'producto_id'    => $ing['producto_id'],
                        'cantidad'       => $ing['cantidad'],
                        'unidad'         => $ing['unidad'] ?? 'unidad',
                        'costo_unitario' => $costoUnitario,
                        'costo_total'    => (int) round($ing['cantidad'] * $costoUnitario),
                    ]);
                }
            }

            $receta->load('ingredientes');
            $receta->recalcularCosto();

            return $receta->fresh('ingredientes.producto');
        });
    }

    /**
     * Recalcular costos de una receta (cuando cambian precios de ingredientes)
     */
    public function recalcularCostos(Receta $receta): Receta
    {
        foreach ($receta->ingredientes as $ing) {
            $ing->recalcularCosto();
        }
        $receta->recalcularCosto();
        return $receta->fresh('ingredientes.producto');
    }

    /**
     * Verificar stock antes de producir
     */
    public function verificarStock(Receta $receta, int $batches = 1): array
    {
        $faltantes = [];
        foreach ($receta->ingredientes as $ing) {
            $necesario = $ing->cantidad * $batches * (1 + $receta->porcentaje_merma / 100);
            $producto = $ing->producto;
            $disponible = $producto->cantidad ?? 0;

            if ($disponible < $necesario) {
                $faltantes[] = [
                    'producto_id'   => $producto->id,
                    'nombre'        => $producto->nombre,
                    'necesario'     => round($necesario, 3),
                    'disponible'    => $disponible,
                    'faltante'      => round($necesario - $disponible, 3),
                    'unidad'        => $ing->unidad,
                ];
            }
        }
        return $faltantes;
    }

    /**
     * Ejecutar producción: descuenta ingredientes del inventario
     */
    public function producir(Receta $receta, int $batches, Usuario $usuario, ?string $obs = null): Produccion
    {
        return DB::transaction(function () use ($receta, $batches, $usuario, $obs) {
            $costoTotal = 0;
            $porciones = $receta->porciones_por_batch * $batches;

            $produccion = Produccion::create([
                'receta_id'           => $receta->id,
                'usuario_id'          => $usuario->id,
                'cantidad_batches'    => $batches,
                'porciones_producidas' => $porciones,
                'estado'              => 'en_proceso',
                'observaciones'       => $obs,
            ]);

            foreach ($receta->ingredientes as $ing) {
                $cantBase = $ing->cantidad * $batches;
                $cantMerma = round($cantBase * ($receta->porcentaje_merma / 100), 3);
                $cantTotal = $cantBase + $cantMerma;

                $producto = Producto::find($ing->producto_id);
                $stockSuficiente = ($producto->cantidad ?? 0) >= $cantTotal;
                $cantUsada = $stockSuficiente ? $cantTotal : $producto->cantidad;

                $produccion->items()->create([
                    'producto_id'       => $ing->producto_id,
                    'cantidad_necesaria' => $cantTotal,
                    'cantidad_usada'    => $cantUsada,
                    'cantidad_merma'    => $cantMerma,
                    'stock_suficiente'  => $stockSuficiente,
                ]);

                // Descontar stock
                if ($cantUsada > 0) {
                    $stockAntes = $producto->cantidad;
                    $producto->decrement('cantidad', $cantUsada);

                    MovimientoStock::create([
                        'producto_id'   => $producto->id,
                        'tipo'          => 'produccion',
                        'cantidad'      => -$cantUsada,
                        'stock_antes'   => $stockAntes,
                        'stock_despues' => $producto->fresh()->cantidad,
                        'referencia_id' => $produccion->id,
                        'usuario_id'    => $usuario->id,
                    ]);
                }

                $costoTotal += (int) round($cantBase * $ing->costo_unitario);
            }

            // Sumar costo mano de obra
            $costoTotal += $receta->costo_mano_obra * $batches;

            // Si el producto resultado existe, incrementar su stock
            if ($receta->producto_id) {
                $productoFinal = Producto::find($receta->producto_id);
                if ($productoFinal) {
                    $productoFinal->increment('cantidad', $porciones);
                }
            }

            $produccion->update([
                'costo_total' => $costoTotal,
                'estado'      => 'completada',
            ]);

            return $produccion->load('items.producto', 'receta');
        });
    }

    /**
     * Reporte de costo real vs precio venta por receta
     */
    public function reporteCostoVsPrecio(): array
    {
        return Receta::activas()
            ->with('ingredientes')
            ->get()
            ->map(function ($receta) {
                return [
                    'id'                => $receta->id,
                    'nombre'            => $receta->nombre,
                    'categoria'         => $receta->categoria,
                    'costo_por_porcion' => $receta->costo_por_porcion,
                    'precio_venta'      => $receta->precio_venta,
                    'margen_pct'        => $receta->margen_pct,
                    'porciones_batch'   => $receta->porciones_por_batch,
                    'rentable'          => $receta->margen_pct >= 30,
                ];
            })
            ->toArray();
    }

    /**
     * Dashboard cocina
     */
    public function getDashboard(): array
    {
        return [
            'total_recetas'       => Receta::activas()->count(),
            'producciones_hoy'    => Produccion::whereDate('created_at', today())->count(),
            'porciones_hoy'       => Produccion::whereDate('created_at', today())->where('estado', 'completada')->sum('porciones_producidas'),
            'costo_promedio_margen' => (float) Receta::activas()->avg('margen_pct') ?: 0,
        ];
    }
}
