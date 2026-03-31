<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Cita extends Model
{
    protected $table = 'agenda_citas';

    protected $fillable = [
        'cliente_id',
        'medico_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'observaciones_recepcion',
        'producto_id',
        'precio',
        'canal_origen',
        'tipo_servicio'
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio' => 'integer'
    ];

    /**
     * AISLAMIENTO POR PROFESIONAL (Eloquent Global Scope)
     */
    protected static function booted()
    {
        static::addGlobalScope('profesional_scope', function (Builder $builder) {
            $user = auth()->user();
            if (!$user) return;

            // Admin y Recepcionista ven todo
            if (in_array($user->rol, ['admin', 'super_admin', 'recepcionista'])) {
                return;
            }

            // Médicos/Profesionales ven SOLO lo asignado a su ID
            // Nota: El profesional ES el usuario autenticado
            $builder->where('medico_id', $user->id);
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function medico()
    {
        return $this->belongsTo(Usuario::class, 'medico_id');
    }

    public function notas()
    {
        return $this->hasMany(NotaClinica::class, 'cita_id');
    }
}
