<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ZonaEnvio extends Model
{
    protected $table = 'zonas_envio';

    protected $fillable = [
        'nombre', 'codigo', 'costo_envio', 'tiempo_estimado_min', 'activa',
    ];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
