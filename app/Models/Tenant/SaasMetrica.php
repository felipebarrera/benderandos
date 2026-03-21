<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasMetrica extends Model
{
    protected $table = 'saas_metricas';

    protected $fillable = [
        'fecha', 'mrr', 'arr', 'tenants_activos', 'tenants_trial',
        'tenants_morosos', 'nuevos_mes', 'cancelados_mes', 'churn_rate',
        'ltv_promedio', 'arpu'
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }
}
