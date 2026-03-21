<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BotConfig extends Model
{
    protected $table = 'bot_config';

    protected $fillable = [
        'nombre_bot',
        'personalidad',
        'activo',
        'horario_atencion',
        'intenciones_activas',
        'faq',
        'whatsapp_numero',
        'mensaje_bienvenida',
        'mensaje_fuera_horario',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'horario_atencion' => 'array',
        'intenciones_activas' => 'array',
        'faq' => 'array',
    ];
}
