<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = [
        'producto_id', 'nombre', 'descripcion', 'categoria',
        'porciones_por_batch', 'tiempo_preparacion_min',
        'costo_mano_obra', 'porcentaje_merma',
        'costo_por_porcion', 'precio_venta', 'margen_pct',
        'instrucciones', 'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'porcentaje_merma' => 'decimal:2',
            'margen_pct' => 'decimal:2',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ingredientes()
    {
        return $this->hasMany(IngredienteReceta::class, 'receta_id');
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class, 'receta_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Recalcular costo por porción basado en ingredientes + merma + mano de obra
     */
    public function recalcularCosto(): void
    {
        $costoIngredientes = $this->ingredientes->sum('costo_total');
        $costoConMerma = (int) round($costoIngredientes * (1 + $this->porcentaje_merma / 100));
        $costoTotal = $costoConMerma + $this->costo_mano_obra;
        $costoPorcion = $this->porciones_por_batch > 0 ? (int) round($costoTotal / $this->porciones_por_batch) : 0;

        $margen = $this->precio_venta > 0 ? round((($this->precio_venta - $costoPorcion) / $this->precio_venta) * 100, 2) : 0;

        $this->update([
            'costo_por_porcion' => $costoPorcion,
            'margen_pct' => $margen,
        ]);
    }
}
