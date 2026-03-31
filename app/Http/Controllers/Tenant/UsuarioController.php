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
            Usuario::with(['role', 'agendaRecurso'])
                ->orderBy('nombre')
                ->paginate($request->input('per_page', 50))
        );
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Usuario::with(['role', 'agendaRecurso'])->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'whatsapp'   => 'nullable|string',
            'password'   => 'required|string|min:6',
            'rol'        => 'required|in:admin,cajero,operario,bodega,cliente',
            'role_id'    => 'nullable|integer|exists:roles,id',
            'recurso_id' => 'nullable|integer|exists:agenda_recursos,id',
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

        if (isset($data['recurso_id'])) {
            \App\Models\Tenant\AgendaRecurso::where('usuario_id', $usuario->id)->update(['usuario_id' => null]);
            \App\Models\Tenant\AgendaRecurso::where('id', $data['recurso_id'])->update(['usuario_id' => $usuario->id]);
        }

        return response()->json($usuario->load(['role', 'agendaRecurso']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'nombre'     => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $id,
            'whatsapp'   => 'nullable|string',
            'password'   => 'nullable|string|min:6',
            'rol'        => 'sometimes|in:admin,cajero,operario,bodega,cliente',
            'role_id'    => 'nullable|integer|exists:roles,id',
            'recurso_id' => 'nullable|integer|exists:agenda_recursos,id',
            'activo'     => 'nullable|boolean',
        ]);

        if (isset($data['password'])) {
            $data['clave_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        if (array_key_exists('recurso_id', $data)) {
            \App\Models\Tenant\AgendaRecurso::where('usuario_id', $id)->update(['usuario_id' => null]);
            if ($data['recurso_id']) {
                \App\Models\Tenant\AgendaRecurso::where('id', $data['recurso_id'])->update(['usuario_id' => $id]);
            }
            unset($data['recurso_id']);
        }

        $usuario->update($data);

        return response()->json($usuario->fresh()->load(['role', 'agendaRecurso']));
    }

    public function roles(): JsonResponse
    {
        return response()->json(Role::all());
    }
}
