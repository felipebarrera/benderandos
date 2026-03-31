<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Usuario;
use App\Models\Tenant\NotaClinica;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotaClinicaPolicy
{
    use HandlesAuthorization;

    public function view(Usuario $user, NotaClinica $nota)
    {
        // Únicamente el autor puede ver la nota clínica
        return $user->id === $nota->medico_id;
    }

    public function update(Usuario $user, NotaClinica $nota)
    {
        return $user->id === $nota->medico_id;
    }

    public function delete(Usuario $user, NotaClinica $nota)
    {
        return $user->id === $nota->medico_id;
    }
}
