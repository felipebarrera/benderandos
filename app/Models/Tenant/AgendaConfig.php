<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class AgendaConfig extends Model
{
    protected $table = 'agenda_config';
    protected $guarded = [];

    protected $casts = [
        'landing_publico_activo' => 'boolean',
        'confirmacion_wa_activa' => 'boolean',
        'recordatorio_horas_antes' => 'integer',
    ];
}
