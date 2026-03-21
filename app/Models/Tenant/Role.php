<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'etiqueta',
        'permisos',
    ];

    protected $casts = [
        'permisos' => 'array',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'role_id');
    }
}
