<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'usuario_id',
        'tipo_pago_id',
        'numero_factura',
        'total',
        'estado',
    ];

    // --- Relaciones ---

    public function items()
    {
        return $this->hasMany(ItemCompra::class, 'compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }
}
