<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CampanaMarketing extends Model
{
    protected $table = 'campanas_marketing';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo_accion', 'valor_descuento',
        'link_destino', 'mensaje_whatsapp', 'fecha_inicio', 'fecha_fin',
        'estado', 'limite_usos', 'usos_actuales', 'codigo_pos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function qrs()
    {
        return $this->hasMany(QrCampana::class, 'campana_id');
    }

    public function getActivaAttribute(): bool
    {
        if ($this->estado !== 'activa') return false;
        if ($this->fecha_inicio > today()) return false;
        if ($this->fecha_fin && $this->fecha_fin < today()) return false;
        if ($this->limite_usos && $this->usos_actuales >= $this->limite_usos) return false;
        return true;
    }
}
