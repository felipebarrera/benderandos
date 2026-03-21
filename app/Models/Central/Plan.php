<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'nombre',
        'precio_mensual_clp',
        'max_usuarios',
        'max_productos',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
