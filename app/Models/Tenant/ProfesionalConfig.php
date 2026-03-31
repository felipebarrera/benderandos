<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProfesionalConfig extends Model
{
    protected $table = 'profesionales_config';

    protected $fillable = [
        'usuario_id',
        'especialidad',
        'titulo_prefijo',
        'color',
        'duracion_cita_minutos',
        'horario_json',
        'visible_web',
        'permiso_notas_cifradas',
        'permiso_ver_solo_agenda'
    ];

    protected $casts = [
        'horario_json' => 'array',
        'visible_web' => 'boolean',
        'permiso_notas_cifradas' => 'boolean',
        'permiso_ver_solo_agenda' => 'boolean'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
