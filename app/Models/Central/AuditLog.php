<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AuditLog extends Model
{
    use HasFactory;
    
    protected $connection = 'central';


    protected $fillable = [
        'user_id',
        'actor_type',
        'actor_email',
        'tenant_id',
        'accion',
        'detalles',
        'ip',
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
