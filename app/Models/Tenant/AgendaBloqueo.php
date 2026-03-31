<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaBloqueo extends Model
{
    protected $table = 'agenda_bloqueos';
    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
