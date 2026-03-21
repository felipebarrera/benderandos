<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
    protected $table = 'postulaciones';

    protected $fillable = [
        'oferta_id', 'nombre', 'email', 'telefono', 'rut', 'mensaje',
        'cv_path', 'pretension_salarial', 'estado', 'notas_internas', 'puntaje',
    ];

    public function oferta()
    {
        return $this->belongsTo(OfertaEmpleo::class, 'oferta_id');
    }

    public function entrevistas()
    {
        return $this->hasMany(Entrevista::class, 'postulacion_id');
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['recibida', 'preseleccionada']);
    }

    /**
     * Pipeline válido: recibida → preseleccionada → entrevista → evaluacion → oferta → contratada
     */
    public function puedeAvanzarA(string $estado): bool
    {
        $pipeline = ['recibida', 'preseleccionada', 'entrevista', 'evaluacion', 'oferta', 'contratada'];
        $posActual = array_search($this->estado, $pipeline);
        $posNueva = array_search($estado, $pipeline);

        return $posNueva !== false && ($posNueva === $posActual + 1 || $estado === 'descartada');
    }
}
