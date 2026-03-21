<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Middleware: role:admin,cajero
     * Verifica que el usuario autenticado tenga uno de los roles especificados.
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($usuario->rol === 'admin' || $usuario->rol === 'super_admin') {
            return $next($request);
        }

        if (! in_array($usuario->rol, $roles)) {
            return response()->json(['message' => 'No autorizado para este recurso'], 403);
        }

        return $next($request);
    }
}
