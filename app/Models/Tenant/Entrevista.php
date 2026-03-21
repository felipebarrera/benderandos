<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Entrevista extends Model
{
    protected $table = 'entrevistas';

    protected $fillable = [
        'postulacion_id', 'entrevistador_id', 'fecha_hora', 'tipo',
        'lugar', 'link_video', 'estado', 'puntaje', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function entrevistador()
    {
        return $this->belongsTo(Usuario::class, 'entrevistador_id');
    }
}
