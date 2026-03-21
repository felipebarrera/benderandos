<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Deuda extends Model
{
    protected $table = 'deudas';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'valor',
        'pagada',
        'fecha_pago',
        'vencimiento_at',
        'comentario',
    ];

    protected $casts = [
        'pagada' => 'boolean',
        'fecha_pago' => 'datetime',
        'vencimiento_at' => 'datetime',
    ];

    // --- Relaciones ---

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // --- Accessors ---

    public function getMontoPendienteAttribute(): int
    {
        return $this->pagada ? 0 : $this->valor;
    }

    // --- Scopes ---

    public function scopePendientes($query)
    {
        return $query->where('pagada', false);
    }
}
