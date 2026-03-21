<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use App\Jobs\SendWhatsAppNotification;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'stock_antes',
        'stock_despues',
        'referencia_id',
        'usuario_id',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'stock_antes' => 'decimal:3',
        'stock_despues' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::created(function (MovimientoStock $movimiento) {
            $producto = $movimiento->producto;
            // Si el movimiento causa que el stock caiga por debajo (o igual) a la cantidad mínima
            if ($movimiento->stock_antes > $producto->cantidad_minima && 
                $movimiento->stock_despues <= $producto->cantidad_minima) {
                SendWhatsAppNotification::dispatch($movimiento, 'stock_critico');
            }
        });
    }

    // --- Relaciones ---

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
