<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductoProveedor extends Model
{
    protected $table = 'productos_proveedor';

    protected $fillable = [
        'proveedor_id', 'producto_id', 'codigo_proveedor',
        'precio_unitario', 'precio_anterior',
        'cantidad_minima_pedido', 'dias_entrega', 'es_principal',
    ];

    protected function casts(): array
    {
        return ['es_principal' => 'boolean'];
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
