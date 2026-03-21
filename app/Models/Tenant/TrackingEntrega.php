<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TrackingEntrega extends Model
{
    protected $table = 'tracking_entregas';

    protected $fillable = [
        'entrega_id', 'estado', 'descripcion', 'latitud', 'longitud',
    ];

    public function entrega()
    {
        return $this->belongsTo(Entrega::class, 'entrega_id');
    }
}
