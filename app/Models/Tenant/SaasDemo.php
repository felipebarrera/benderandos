<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasDemo extends Model
{
    protected $table = 'saas_demos';

    protected $fillable = [
        'pipeline_id', 'fecha', 'hora', 'modalidad', 'link_reunion',
        'ejecutivo_id', 'duracion_min', 'asistio', 'notas_post_demo',
        'siguiente_paso'
    ];

    protected function casts(): array
    {
        return [
            'fecha'   => 'date',
            'asistio' => 'boolean',
        ];
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
