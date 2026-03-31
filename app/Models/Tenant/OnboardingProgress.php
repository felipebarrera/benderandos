<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class OnboardingProgress extends Model
{
    protected $table = 'onboarding_progress';

    protected $fillable = [
        'step_id',
        'estado',
        'completado_at',
        'data'
    ];

    protected $casts = [
        'completado_at' => 'datetime',
        'data' => 'array'
    ];
}
