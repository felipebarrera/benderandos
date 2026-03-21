<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ItemRecepcion extends Model
{
    protected $table = 'items_recepcion';

    protected $fillable = [
        'recepcion_id', 'item_orden_id', 'producto_id',
        'cantidad_recibida', 'cantidad_rechazada', 'motivo_rechazo',
        'lote', 'fecha_vencimiento',
    ];

    protected function casts(): array
    {
        return ['fecha_vencimiento' => 'date'];
    }

    public function recepcion()
    {
        return $this->belongsTo(RecepcionCompra::class, 'recepcion_id');
    }

    public function itemOrden()
    {
        return $this->belongsTo(ItemOrdenCompra::class, 'item_orden_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
