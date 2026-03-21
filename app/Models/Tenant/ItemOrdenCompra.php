<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ItemOrdenCompra extends Model
{
    protected $table = 'items_orden_compra';

    protected $fillable = [
        'orden_compra_id', 'producto_id',
        'cantidad_solicitada', 'cantidad_recibida',
        'precio_unitario', 'total_item',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function estaCompletoRecibido(): bool
    {
        return $this->cantidad_recibida >= $this->cantidad_solicitada;
    }
}
