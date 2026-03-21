<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = [
        'empleado_id', 'fecha', 'tipo', 'horas', 'con_goce',
        'estado', 'aprobado_por', 'motivo',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'con_goce' => 'boolean'];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }
}
