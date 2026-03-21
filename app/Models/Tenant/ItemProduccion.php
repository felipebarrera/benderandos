<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ItemProduccion extends Model
{
    protected $table = 'items_produccion';

    protected $fillable = [
        'produccion_id', 'producto_id',
        'cantidad_necesaria', 'cantidad_usada', 'cantidad_merma',
        'stock_suficiente',
    ];

    protected function casts(): array
    {
        return ['stock_suficiente' => 'boolean'];
    }

    public function produccion()
    {
        return $this->belongsTo(Produccion::class, 'produccion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
