<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Entrega extends Model
{
    protected $table = 'entregas';

    protected $fillable = [
        'uuid', 'venta_id', 'repartidor_id', 'zona_envio_id',
        'estado', 'direccion_entrega', 'comuna_entrega',
        'telefono_contacto', 'nombre_receptor', 'instrucciones',
        'costo_envio', 'asignada_at', 'en_preparacion_at',
        'en_camino_at', 'entregada_at', 'motivo_fallo',
    ];

    protected function casts(): array
    {
        return [
            'asignada_at' => 'datetime',
            'en_preparacion_at' => 'datetime',
            'en_camino_at' => 'datetime',
            'entregada_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Entrega $e) {
            if (empty($e->uuid)) {
                $e->uuid = (string) Str::uuid();
            }
        });
    }

    // --- Relaciones ---

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }

    public function zonaEnvio()
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_envio_id');
    }

    public function tracking()
    {
        return $this->hasMany(TrackingEntrega::class, 'entrega_id')->orderBy('created_at');
    }

    // --- Scopes ---

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['pendiente', 'asignada', 'en_preparacion', 'en_camino']);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // --- State Machine ---

    public function puedeAsignar(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function puedePrepara(): bool
    {
        return $this->estado === 'asignada';
    }

    public function puedeDespachar(): bool
    {
        return $this->estado === 'en_preparacion';
    }

    public function puedeEntregar(): bool
    {
        return $this->estado === 'en_camino';
    }

    public function getUrlPublicaAttribute(): string
    {
        return "/tracking/{$this->uuid}";
    }
}
