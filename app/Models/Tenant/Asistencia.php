<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'empleado_id', 'fecha', 'hora_entrada', 'hora_salida',
        'minutos_atraso', 'minutos_extra', 'horas_trabajadas', 'observacion',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
}
