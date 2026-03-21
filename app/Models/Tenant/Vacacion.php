<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Vacacion extends Model
{
    protected $table = 'vacaciones';

    protected $fillable = [
        'empleado_id', 'fecha_inicio', 'fecha_fin', 'dias_solicitados',
        'estado', 'aprobado_por', 'motivo', 'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
