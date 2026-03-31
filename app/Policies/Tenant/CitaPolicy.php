<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Usuario;
use App\Models\Tenant\Cita;
use Illuminate\Auth\Access\HandlesAuthorization;

class CitaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user)
    {
        return true; // El filtrado lo hace la DB vía Global Scope
    }

    public function view(Usuario $user, Cita $cita)
    {
        // Admin y recepcionista ven cualquier cita
        if (in_array($user->rol, ['admin', 'super_admin', 'recepcionista'])) {
            return true;
        }

        // Médicos solo ven sus citas propias
        return $user->id === $cita->medico_id;
    }

    public function update(Usuario $user, Cita $cita)
    {
        if (in_array($user->rol, ['admin', 'super_admin', 'recepcionista'])) {
            return true;
        }

        return $user->id === $cita->medico_id;
    }
}
