<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class PlanModulo extends Model
{
    protected $table = 'plan_modulos';
    protected $connection = 'central';
    protected $primaryKey = 'modulo_id'; 
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'modulo_id',
        'nombre',
        'descripcion',
        'precio_mensual',
        'es_base',
        'requiere',
        'activo',
    ];

    protected $casts = [
        'precio_mensual' => 'integer',
        'es_base'        => 'boolean',
        'activo'         => 'boolean',
        'requiere'       => 'array',
    ];
}
