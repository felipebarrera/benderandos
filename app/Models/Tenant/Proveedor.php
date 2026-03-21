<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'rut', 'nombre', 'razon_social', 'giro',
        'direccion', 'comuna', 'ciudad',
        'telefono', 'email', 'contacto_nombre', 'contacto_telefono',
        'plazo_pago_dias', 'descuento_volumen_pct', 'monto_minimo_oc',
        'notas', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'descuento_volumen_pct' => 'decimal:2',
        ];
    }

    public function productosProveedor()
    {
        return $this->hasMany(ProductoProveedor::class, 'proveedor_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'productos_proveedor')
            ->withPivot('precio_unitario', 'codigo_proveedor', 'es_principal', 'dias_entrega')
            ->withTimestamps();
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $term)
    {
        return $query->where('nombre', 'ilike', "%{$term}%")
                     ->orWhere('rut', 'ilike', "%{$term}%");
    }
}
