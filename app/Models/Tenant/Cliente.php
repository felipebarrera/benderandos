<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'rut',
        'giro',
        'telefono',
        'direccion',
        'email',
        'codigo_rapido',
        'usuario_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cliente $cliente) {
            if (! $cliente->codigo_rapido) {
                $cliente->codigo_rapido = (static::max('codigo_rapido') ?? 0) + 1;
            }
        });
    }

    // --- Relaciones ---

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function deudas()
    {
        return $this->hasMany(Deuda::class, 'cliente_id');
    }

    public function encargos()
    {
        return $this->hasMany(Encargo::class, 'cliente_id');
    }

    // --- Scopes ---

    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombre', 'ilike', "%{$termino}%")
                     ->orWhere('rut', 'ilike', "%{$termino}%")
                     ->orWhere('codigo_rapido', (int) $termino);
    }
}
