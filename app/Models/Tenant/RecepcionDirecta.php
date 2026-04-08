<?php
// app/Models/Tenant/RecepcionDirecta.php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecepcionDirecta extends Model
{
    protected $table = 'recepciones_directas';

    protected $fillable = [
        'proveedor_id', 'proveedor_nombre', 'usuario_id', 'aprobado_por',
        'estado', 'numero_documento', 'monto_total', 'tipo_pago',
        'notas', 'cerrada_at', 'pagada_at',
    ];

    protected $casts = [
        'monto_total' => 'integer',
        'cerrada_at'  => 'datetime',
        'pagada_at'   => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemRecepcionDirecta::class, 'recepcion_id');
    }

    // Nombre para mostrar (proveedor registrado o nombre libre)
    public function getNombreProveedorDisplayAttribute(): string
    {
        return $this->proveedor?->nombre ?? $this->proveedor_nombre ?? 'Sin proveedor';
    }
}

