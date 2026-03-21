<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Renta extends Model
{
    protected $table = 'rentas';

    protected $fillable = [
        'item_venta_id',
        'producto_id',
        'cliente_id',
        'inicio_real',
        'fin_programado',
        'fin_real',
        'estado',
        'cargo_extra',
        'notas',
    ];

    protected $casts = [
        'inicio_real'    => 'datetime',
        'fin_programado' => 'datetime',
        'fin_real'       => 'datetime',
    ];

    public function itemVenta()
    {
        return $this->belongsTo(ItemVenta::class, 'item_venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
