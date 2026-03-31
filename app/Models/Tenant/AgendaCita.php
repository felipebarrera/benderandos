<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgendaCita extends Model
{
    protected $table = 'agenda_citas';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'recordatorio_enviado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($cita) {
            $cita->uuid = $cita->uuid ?? (string) Str::uuid();
        });
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(AgendaRecurso::class, 'agenda_recurso_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(AgendaServicio::class, 'agenda_servicio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ?? today()->toDateString();
        return $query->where('fecha', $fecha);
    }

    public function scopeOcupadas($query)
    {
        return $query->whereNotIn('estado', ['cancelada']);
    }
}
