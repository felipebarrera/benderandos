<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'usuario_id', 'nombre', 'rut', 'fecha_nacimiento', 'direccion', 'comuna',
        'telefono', 'email', 'cargo', 'fecha_ingreso', 'fecha_termino',
        'tipo_contrato', 'sueldo_base', 'afp', 'afp_pct', 'salud', 'salud_tipo',
        'salud_pct', 'salud_uf', 'mutual', 'mutual_pct', 'horario',
        'dias_vacaciones_anuales', 'dias_vacaciones_pendientes', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_ingreso' => 'date',
            'fecha_termino' => 'date',
            'mutual' => 'boolean',
            'activo' => 'boolean',
            'afp_pct' => 'decimal:2',
            'salud_pct' => 'decimal:2',
            'mutual_pct' => 'decimal:2',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'empleado_id');
    }

    public function vacaciones()
    {
        return $this->hasMany(Vacacion::class, 'empleado_id');
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'empleado_id');
    }

    public function liquidaciones()
    {
        return $this->hasMany(Liquidacion::class, 'empleado_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getHorarioEntradaAttribute(): ?string
    {
        return explode('-', $this->horario)[0] ?? null;
    }

    public function getHorarioSalidaAttribute(): ?string
    {
        return explode('-', $this->horario)[1] ?? null;
    }
}
