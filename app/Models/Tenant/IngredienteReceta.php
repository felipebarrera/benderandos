<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class IngredienteReceta extends Model
{
    protected $table = 'ingredientes_receta';

    protected $fillable = [
        'receta_id', 'producto_id', 'cantidad', 'unidad',
        'costo_unitario', 'costo_total',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Actualizar costo desde el producto actual
     */
    public function recalcularCosto(): void
    {
        $this->costo_unitario = $this->producto->costo ?? 0;
        $this->costo_total = (int) round($this->cantidad * $this->costo_unitario);
        $this->save();
    }
}
