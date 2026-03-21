<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'webhooks';

    protected $fillable = [
        'nombre', 'url', 'eventos', 'secreto',
        'activo', 'fallos_consecutivos', 'ultimo_intento'
    ];

    protected function casts(): array
    {
        return [
            'eventos'         => 'array',
            'activo'          => 'boolean',
            'ultimo_intento'  => 'datetime',
        ];
    }
}
