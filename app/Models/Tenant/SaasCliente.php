<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SaasCliente extends Model
{
    protected $table = 'saas_clientes';

    protected $fillable = [
        'uuid', 'tenant_uuid', 'razon_social', 'rut', 'industria',
        'contacto_nombre', 'contacto_whatsapp', 'contacto_email',
        'plan_id', 'modulos_addon', 'estado', 'fecha_inicio',
        'fecha_trial_fin', 'fecha_proximo_cobro', 'ciclo_facturacion',
        'precio_actual', 'descuento_pct', 'ejecutivo_id', 'notas_crm'
    ];

    protected function casts(): array
    {
        return [
            'modulos_addon'       => 'array',
            'fecha_inicio'        => 'date',
            'fecha_trial_fin'     => 'date',
            'fecha_proximo_cobro' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SaasCliente $cliente) {
            if (empty($cliente->uuid)) {
                $cliente->uuid = Str::uuid()->toString();
            }
        });
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'plan_id');
    }

    public function ejecutivo()
    {
        return $this->belongsTo(Usuario::class, 'ejecutivo_id');
    }

    public function cobros()
    {
        return $this->hasMany(SaasCobro::class, 'cliente_id');
    }

    public function getEsMorosoAttribute(): bool
    {
        return $this->cobros()->where('estado', 'vencido')->exists();
    }
}
