<?php
namespace App\Observers\Tenant;

use App\Models\Tenant\Usuario;
use App\Services\AgendaAutoRegistroService;

class UsuarioAgendaObserver
{
    private AgendaAutoRegistroService $svc;

    public function __construct(AgendaAutoRegistroService $svc)
    {
        $this->svc = $svc;
    }

    /**
     * Cuando se crea un usuario → registrarlo como recurso si M08 activo.
     */
    public function created(Usuario $usuario): void
    {
        $this->svc->registrarOperario($usuario);
    }

    /**
     * Cuando se actualiza → re-evaluar.
     */
    public function updated(Usuario $usuario): void
    {
        $roles_agenda = ['operario', 'cajero', 'admin'];

        if ($usuario->wasChanged('activo') && !$usuario->activo) {
            $this->svc->desactivarOperario($usuario->id);
            return;
        }

        if (in_array($usuario->rol, $roles_agenda)) {
            $this->svc->registrarOperario($usuario);
        } else {
            $this->svc->desactivarOperario($usuario->id);
        }
    }

    /**
     * Cuando se elimina un usuario → desactivar recurso.
     */
    public function deleted(Usuario $usuario): void
    {
        $this->svc->desactivarOperario($usuario->id);
    }
}
