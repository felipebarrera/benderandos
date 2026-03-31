<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaServicio extends Model
{
    protected $table = 'agenda_servicios';
    protected $guarded = [];

    protected $casts = [
        'duracion_min' => 'integer',
        'precio' => 'integer',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
