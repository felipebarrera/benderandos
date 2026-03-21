<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model
{
    protected $table = 'saas_planes';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'precio_mensual', 'precio_anual',
        'max_usuarios', 'max_productos', 'modulos_incluidos', 'modulos_addon',
        'soporte_nivel', 'activo'
    ];

    protected function casts(): array
    {
        return [
            'modulos_incluidos' => 'array',
            'modulos_addon'     => 'array',
            'activo'            => 'boolean',
        ];
    }
}
