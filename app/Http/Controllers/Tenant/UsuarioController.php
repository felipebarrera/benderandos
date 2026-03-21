<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Usuario::with('role')
                ->orderBy('nombre')
                ->paginate($request->input('per_page', 50))
        );
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Usuario::with('role')->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'whatsapp' => 'nullable|string',
            'password' => 'required|string|min:6',
            'rol'      => 'required|in:admin,cajero,operario,bodega,cliente',
            'role_id'  => 'nullable|integer|exists:roles,id',
        ]);

        $usuario = Usuario::create([
            'nombre'    => $data['nombre'],
            'email'     => $data['email'],
            'whatsapp'  => $data['whatsapp'] ?? null,
            'clave_hash' => Hash::make($data['password']),
            'rol'       => $data['rol'],
            'role_id'   => $data['role_id'] ?? null,
            'activo'    => true,
        ]);

        return response()->json($usuario->load('role'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'nombre'   => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $id,
            'whatsapp' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'rol'      => 'sometimes|in:admin,cajero,operario,bodega,cliente',
            'role_id'  => 'nullable|integer|exists:roles,id',
            'activo'   => 'nullable|boolean',
        ]);

        if (isset($data['password'])) {
            $data['clave_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $usuario->update($data);

        return response()->json($usuario->fresh()->load('role'));
    }

    public function roles(): JsonResponse
    {
        return response()->json(Role::all());
    }
}
