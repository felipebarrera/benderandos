<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class NotaClinica extends Model
{
    use SoftDeletes;

    protected $table = 'notas_clinicas';

    protected $fillable = [
        'id_externo',
        'cita_id',
        'cliente_id',
        'medico_id',
        'titulo',
        'contenido_cifrado',
        'iv'
    ];

    // Ocultar campos de cifrado en respuestas API por seguridad
    protected $hidden = [
        'contenido_cifrado',
        'iv'
    ];

    /**
     * AISLAMIENTO EXTREMO (SÓLO EL AUTOR VE LA NOTA)
     */
    protected static function booted()
    {
        static::addGlobalScope('nota_autor_scope', function (Builder $builder) {
            if (!auth()->check()) return;

            $user = auth()->user();
            
            // ACL: Sólo el médico autor puede ver sus notas clínicas.
            // Ni admin ni recepcionista ven el contenido por privacidad médica.
            $builder->where('medico_id', $user->id);
        });
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function medico()
    {
        return $this->belongsTo(Usuario::class, 'medico_id');
    }
}
