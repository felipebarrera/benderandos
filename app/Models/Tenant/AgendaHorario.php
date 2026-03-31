<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaHorario extends Model
{
    protected $table = 'agenda_horarios';
    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'dia_semana' => 'integer',
        'duracion_slot_min' => 'integer',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id');
    }
}
