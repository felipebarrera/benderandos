<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'uuid',
        'estado',
        'cliente_id',
        'usuario_id',
        'cajero_id',
        'tipo_pago_id',
        'subtotal',
        'descuento_monto',
        'descuento_pct',
        'total',
        'tipo_entrega',
        'es_deuda',
        'numero_documento',
        'tipo_documento',
        'origen',
        'notas',
        'pagado_at',
    ];

    protected $casts = [
        'es_deuda' => 'boolean',
        'descuento_pct' => 'decimal:2',
        'pagado_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Venta $venta) {
            if (empty($venta->uuid)) {
                $venta->uuid = (string) Str::uuid();
            }
        });
    }

    // --- Relaciones ---

    public function items()
    {
        return $this->hasMany(ItemVenta::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cajero()
    {
        return $this->belongsTo(Usuario::class, 'cajero_id');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }

    public function deuda()
    {
        return $this->hasOne(Deuda::class, 'venta_id');
    }

    public function dtes()
    {
        return $this->hasMany(DteEmitido::class, 'venta_id');
    }

    // --- Scopes ---

    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagada');
    }

    public function scopeEnCaja($query)
    {
        return $query->where('estado', 'en_caja');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    public function estaEnCaja(): bool
    {
        return $this->estado === 'en_caja';
    }

    public function permiteAgregarItems(): bool
    {
        return $this->estado === 'abierta';
    }
}
