<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SeguimientoPaciente extends Model
{
    protected $table = 'seguimiento_paciente';

    protected $fillable = [
        'cliente_id', 'usuario_id', 'agenda_cita_id',
        'tipo', 'contenido', 'fecha_seguimiento',
        'resuelto', 'privado',
    ];

    protected $casts = [
        'resuelto'          => 'boolean',
        'privado'           => 'boolean',
        'fecha_seguimiento' => 'date',
    ];

    public function cliente()   { return $this->belongsTo(Cliente::class); }
    public function usuario()   { return $this->belongsTo(Usuario::class); }
    public function cita()      { return $this->belongsTo(AgendaCita::class, 'agenda_cita_id'); }

    public function scopePendientes($q)
    {
        return $q->where('resuelto', false)
                 ->whereNotNull('fecha_seguimiento')
                 ->where('fecha_seguimiento', '<=', now()->addDays(7));
    }

    public function scopeDelProfesional($q, int $usuarioId)
    {
        return $q->where('usuario_id', $usuarioId);
    }
}
