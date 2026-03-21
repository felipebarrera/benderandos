<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasPipeline extends Model
{
    protected $table = 'saas_pipeline';

    protected $fillable = [
        'razon_social', 'contacto_nombre', 'contacto_whatsapp', 'contacto_email',
        'industria', 'etapa', 'plan_interes', 'valor_estimado', 'probabilidad_pct',
        'ejecutivo_id', 'fecha_proximo_contacto', 'motivo_perdida', 'notas', 'origen'
    ];

    protected function casts(): array
    {
        return [
            'fecha_proximo_contacto' => 'date',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'plan_interes');
    }

    public function ejecutivo()
    {
        return $this->belongsTo(Usuario::class, 'ejecutivo_id');
    }

    public function actividades()
    {
        return $this->hasMany(SaasActividad::class, 'pipeline_id');
    }

    public function demos()
    {
        return $this->hasMany(SaasDemo::class, 'pipeline_id');
    }
}
