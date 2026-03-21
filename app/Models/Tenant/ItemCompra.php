<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ItemCompra extends Model
{
    protected $table = 'items_compra';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'total_item',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
    ];

    // --- Relaciones ---

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
