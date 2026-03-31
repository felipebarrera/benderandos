<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaRecurso extends Model
{
    protected $table = 'agenda_recursos';

    protected $fillable = [
        'nombre', 'tipo', 'especialidad', 'color_hex', 'orden',
        'usuario_id', 'producto_id',
        'auto_creado', 'hereda_horario_tenant', 'activo',
    ];

    protected $casts = [
        'auto_creado'             => 'boolean',
        'hereda_horario_tenant'   => 'boolean',
        'activo'                  => 'boolean',
        'color_hex'               => 'string', // Assuming this is the intended cast for color_hex
        'orden'                   => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────────

    /** Usuario del sistema vinculado (operario/profesional) */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** Producto de renta vinculado */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** Servicios ofrecidos por este recurso */
    public function servicios(): HasMany
    {
        return $this->hasMany(AgendaServicio::class, 'agenda_recurso_id')
                    ->where('activo', true);
    }

    /** Horarios operativos del recurso */
    public function horarios(): HasMany
    {
        return $this->hasMany(AgendaHorario::class, 'agenda_recurso_id');
    }

    /** Citas agendadas a este recurso */
    public function citas(): HasMany
    {
        return $this->hasMany(AgendaCita::class, 'agenda_recurso_id');
    }

    /** Bloqueos de agenda */
    public function bloqueos(): HasMany
    {
        return $this->hasMany(AgendaBloqueo::class, 'agenda_recurso_id')
                    ->where(function($q) {
                        $q->where('fecha_fin', '>=', now()->toDateString())
                          ->orWhereNull('fecha_fin');
                    })
                    ->orderBy('fecha_inicio');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActivos($q)
    {
        return $q->where('activo', true)->orderBy('orden');
    }

    public function scopeProfesionales($q)
    {
        return $q->where('tipo', 'profesional')->whereNotNull('usuario_id');
    }

    public function scopeParaUsuario($q, int $usuarioId)
    {
        return $q->where('usuario_id', $usuarioId);
    }
}
