<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'codigo', 'proveedor_id', 'usuario_id', 'autorizado_por',
        'estado', 'subtotal', 'descuento_pct', 'descuento_monto', 'total',
        'fecha_entrega_esperada', 'notas', 'origen',
        'autorizada_at', 'enviada_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega_esperada' => 'date',
            'autorizada_at' => 'datetime',
            'enviada_at' => 'datetime',
            'descuento_pct' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por');
    }

    public function items()
    {
        return $this->hasMany(ItemOrdenCompra::class, 'orden_compra_id');
    }

    public function recepciones()
    {
        return $this->hasMany(RecepcionCompra::class, 'orden_compra_id');
    }

    // --- Scopes ---

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['borrador', 'autorizada', 'enviada', 'parcial']);
    }

    // --- Helpers ---

    public static function generarCodigo(): string
    {
        $ultimo = static::max('id') ?? 0;
        return 'OC-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
    }

    public function puedeAutorizar(): bool
    {
        return $this->estado === 'borrador';
    }

    public function puedeEnviar(): bool
    {
        return $this->estado === 'autorizada';
    }

    public function puedeRecibir(): bool
    {
        return in_array($this->estado, ['enviada', 'parcial']);
    }
}
