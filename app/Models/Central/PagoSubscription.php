<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSubscription extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'subscription_id',
        'monto_clp',
        'estado',
        'metodo_pago',
        'pagado_at',
    ];

    protected $casts = [
        'pagado_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
