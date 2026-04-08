<?php
// app/Models/Tenant/ItemRecepcionDirecta.php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRecepcionDirecta extends Model
{
    protected $table = 'items_recepcion_directa';

    protected $fillable = [
        'recepcion_id', 'producto_id', 'cantidad', 'costo_unitario', 'costo_total',
    ];

    protected $casts = [
        'cantidad'       => 'decimal:3',
        'costo_unitario' => 'integer',
        'costo_total'    => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionDirecta::class, 'recepcion_id');
    }
}