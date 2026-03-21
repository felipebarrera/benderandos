<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ItemVenta extends Model
{
    protected $table = 'items_venta';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'operario_id',
        'cantidad',
        'precio_unitario',
        'costo_unitario',
        'total_item',
        'notas_item',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
    ];

    // --- Relaciones ---

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function operario()
    {
        return $this->belongsTo(Usuario::class, 'operario_id');
    }
}
