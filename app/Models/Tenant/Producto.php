<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Proveedor;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'codigo_referencia',
        'nombre',
        'descripcion',
        'tipo_producto',
        'marca',
        'familia',
        'subfamilia',
        'zona',
        'proveedor',
        'valor_venta',
        'costo',
        'cantidad',
        'cantidad_minima',
        'unidad_medida',
        'fraccionable',
        'estado',
        'operario_id',
    ];

    protected $casts = [
        'fraccionable' => 'boolean',
        'cantidad' => 'decimal:3',
        'cantidad_minima' => 'decimal:3',
    ];

    // --- Relaciones ---

    public function operario()
    {
        return $this->belongsTo(Usuario::class, 'operario_id');
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id');
    }

    public function itemsVenta()
    {
        return $this->hasMany(ItemVenta::class, 'producto_id');
    }


    public function proveedores()
    {
        return $this->belongsToMany(
            Proveedor::class,
            'productos_proveedor',
            'producto_id',
            'proveedor_id'
        )->withPivot(
            'precio_unitario',
            'es_principal',
            'codigo_proveedor',
            'cantidad_minima_pedido',
            'dias_entrega'
        );
    }

    // --- Scopes ---

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'ilike', "%{$termino}%")
              ->orWhere('codigo', 'ilike', "%{$termino}%")
              ->orWhere('codigo_referencia', 'ilike', "%{$termino}%")
              ->orWhere('marca', 'ilike', "%{$termino}%");
        });
    }

    public function scopeConStock($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    // --- Accessors de compatibilidad para las vistas -------
    // Las vistas POS usan nombres simplificados; estos accessors
    // los mapean a las columnas reales de la DB.

    public function getPrecioAttribute(): int
    {
        return (int) $this->valor_venta;
    }

    public function getStockAttribute(): float
    {
        return (float) $this->cantidad;
    }

    public function getStockMinimoAttribute(): float
    {
        return (float) $this->cantidad_minima;
    }

    public function getTipoAttribute(): string
    {
        return $this->tipo_producto ?? 'stock_fisico';
    }

    public function getActivoAttribute(): bool
    {
        return $this->estado === 'activo';
    }

    public function getImagenUrlAttribute(): ?string
    {
        return null; // Sin imagen por defecto
    }
}
