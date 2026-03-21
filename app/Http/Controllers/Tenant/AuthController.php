<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * POST /auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->clave_hash)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        if (! $usuario->activo) {
            return response()->json(['message' => 'Cuenta desactivada'], 403);
        }

        $usuario->update(['ultimo_login' => now()]);

        // Auth manual para soportar Sanctum SPA (cookies)
        Auth::login($usuario);

        $token = $usuario->createToken('api', $this->buildPermisos($usuario));

        return response()->json([
            'token'   => $token->plainTextToken,
            'usuario' => [
                'id'     => $usuario->id,
                'nombre' => $usuario->nombre,
                'email'  => $usuario->email,
                'rol'    => $usuario->rol,
            ],
            'permisos' => $token->accessToken->abilities,
        ]);
    }

    /**
     * POST /auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * GET /auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'usuario'  => $usuario,
            'permisos' => $usuario->currentAccessToken()->abilities ?? [],
        ]);
    }

    /**
     * Genera la lista de abilities para el token según el rol del usuario.
     */
    private function buildPermisos(Usuario $usuario): array
    {
        $base = ['ver:dashboard'];

        return match ($usuario->rol) {
            'super_admin', 'admin' => ['*'],
            'cajero' => array_merge($base, [
                'ver:productos', 'crear:venta', 'ver:ventas',
                'ver:clientes', 'crear:cliente',
            ]),
            'bodega', 'operario' => array_merge($base, [
                'ver:productos', 'editar:stock',
                'ver:compras', 'crear:compra',
            ]),
            'cliente' => ['ver:mis-pedidos'],
            default => $base,
        };
    }
}
