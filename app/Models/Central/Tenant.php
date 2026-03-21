<?php

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'nombre',
            'rut_empresa',
            'estado',
            'trial_hasta',
            'whatsapp_admin',
            'plan_id',
        ];
    }

    /**
     * Data to be merged into global config when tenant is initialized.
     * Requires TenantConfig feature enabled.
     */
    public static function getDataColumn(): string
    {
        return 'data';
    }

    protected $fillable = [
        'uuid', 'nombre', 'slug', 'rut_empresa',
        'plan_id', 'whatsapp_admin', 'estado',
        'trial_hasta', 'rubro_config', 'data',
    ];

    protected function casts(): array
    {
        return [
            'rubro_config' => 'array',
            'data'         => 'array',
            'trial_hasta'  => 'datetime',
        ];
    }

    public function getNombreAttribute($value)
    {
        return $value ?: ($this->data['nombre'] ?? $this->id);
    }
}
