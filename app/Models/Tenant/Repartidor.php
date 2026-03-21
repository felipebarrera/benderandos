<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Repartidor extends Model
{
    protected $table = 'repartidores';

    protected $fillable = [
        'usuario_id', 'nombre', 'telefono', 'vehiculo', 'patente',
        'zonas_cobertura', 'disponible', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'zonas_cobertura' => 'array',
            'disponible' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class, 'repartidor_id');
    }

    public function entregasActivas()
    {
        return $this->entregas()->whereIn('estado', ['asignada', 'en_preparacion', 'en_camino']);
    }

    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true)->where('activo', true);
    }
}
