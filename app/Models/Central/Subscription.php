<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'estado',
        'inicio',
        'proximo_cobro',
        'cancelada_at',
        'monto_clp',
        'modulos_activos',
        'precio_calculado',
        'trial_termina',
        'dias_gracia',
        'descuento_pct',
        'descuento_motivo',
        'link_pago',
    ];

    protected $casts = [
        'inicio'          => 'datetime',
        'proximo_cobro'   => 'datetime',
        'cancelada_at'    => 'datetime',
        'trial_termina'   => 'date',
        'modulos_activos' => 'array',
    ];

    /**
     * Determines if the subscription allows operations.
     */
    public function puedeOperar(): bool
    {
        return in_array($this->estado, ['trial', 'activa', 'gracia']);
    }

    /**
     * Calculates the remaining grace days before locking out.
     */
    public function diasGraciaRestantes(): int
    {
        if ($this->estado !== 'gracia') {
            return 0;
        }

        if (!$this->proximo_cobro) {
            return 0; // Prevent errors if no proximo_cobro date set
        }

        $vencimientoExtendido = $this->proximo_cobro->copy()->addDays($this->dias_gracia);
        $diasRestantes = now()->diffInDays($vencimientoExtendido, false);
        
        return (int) max(0, ceil($diasRestantes));
    }

    /**
     * Helper to retrieve a formatted payment link
     */
    public function linkPago(): string
    {
        // Future WebPay Integration
        return $this->link_pago ?? url("/pago/suscripcion/{$this->id}");
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoSubscription::class);
    }
}
