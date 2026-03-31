<?php

namespace App\Models\Tenant;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'nombre',
        'email',
        'whatsapp',
        'clave_hash',
        'rol',
        'role_id',
        'activo',
    ];

    protected $hidden = ['clave_hash'];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_login' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->clave_hash;
    }

    // --- Relaciones ---

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'usuario_id');
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'usuario_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'usuario_id');
    }

    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'usuario_id');
    }

    public function agendaRecurso()
    {
        return $this->hasOne(\App\Models\Tenant\AgendaRecurso::class, 'usuario_id');
    }

    public function profesionalConfig()
    {
        return $this->hasOne(ProfesionalConfig::class, 'usuario_id');
    }

    // --- Helpers ---

    public function esAdmin(): bool
    {
        return in_array($this->rol, ['super_admin', 'admin']);
    }

    public function esCajero(): bool
    {
        return $this->rol === 'cajero' || $this->esAdmin();
    }

    public function esOperario(): bool
    {
        return $this->rol === 'operario';
    }

    public function tienePermiso(string $permiso): bool
    {
        if ($this->esAdmin()) return true;
        if ($this->role && is_array($this->role->permisos)) {
            return in_array($permiso, $this->role->permisos);
        }
        return false;
    }
}
