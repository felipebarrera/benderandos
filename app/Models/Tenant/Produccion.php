<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    protected $table = 'producciones';

    protected $fillable = [
        'receta_id', 'usuario_id', 'cantidad_batches',
        'porciones_producidas', 'costo_total', 'estado', 'observaciones',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(ItemProduccion::class, 'produccion_id');
    }
}
