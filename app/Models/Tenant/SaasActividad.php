<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasActividad extends Model
{
    protected $table = 'saas_actividades';

    protected $fillable = [
        'cliente_id', 'pipeline_id', 'tipo', 'descripcion',
        'resultado', 'ejecutivo_id', 'fecha_actividad'
    ];

    protected function casts(): array
    {
        return [
            'fecha_actividad' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(SaasCliente::class, 'cliente_id');
    }

    public function prospecto()
    {
        return $this->belongsTo(SaasPipeline::class, 'pipeline_id');
    }

    public function ejecutivo()
    {
        return $this->belongsTo(Usuario::class, 'ejecutivo_id');
    }
}
