<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Encargo extends Model
{
    protected $table = 'encargos';

    protected $fillable = [
        'cliente_id',
        'descripcion',
        'valor',
        'abono',
        'estado',
        'fecha_llegada',
    ];

    protected $casts = [
        'fecha_llegada' => 'datetime',
    ];

    // --- Relaciones ---

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // --- Scopes ---

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // --- Accessors ---

    public function getSaldoAttribute(): int
    {
        return $this->valor - $this->abono;
    }
}
