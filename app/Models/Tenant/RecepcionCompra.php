<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RecepcionCompra extends Model
{
    protected $table = 'recepciones_compra';

    protected $fillable = [
        'orden_compra_id', 'usuario_id', 'numero_guia', 'observaciones',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(ItemRecepcion::class, 'recepcion_id');
    }
}
