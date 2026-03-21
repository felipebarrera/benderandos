<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OfertaEmpleo extends Model
{
    protected $table = 'ofertas_empleo';

    protected $fillable = [
        'titulo', 'slug', 'descripcion', 'cargo', 'departamento', 'ubicacion',
        'modalidad', 'jornada', 'sueldo_min', 'sueldo_max', 'mostrar_sueldo',
        'requisitos', 'beneficios', 'estado', 'fecha_cierre',
    ];

    protected function casts(): array
    {
        return [
            'mostrar_sueldo' => 'boolean',
            'fecha_cierre' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OfertaEmpleo $o) {
            if (empty($o->slug)) {
                $o->slug = Str::slug($o->titulo) . '-' . Str::random(5);
            }
        });
    }

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class, 'oferta_id');
    }

    public function scopePublicadas($query)
    {
        return $query->where('estado', 'publicada');
    }

    public function getUrlPublicaAttribute(): string
    {
        return "/empleo/{$this->slug}";
    }
}
