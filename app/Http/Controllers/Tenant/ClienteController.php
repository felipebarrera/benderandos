<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cliente::query();

        if ($request->filled('q')) {
            $query->buscar($request->q);
        }

        return response()->json(
            $query->orderBy('nombre')->paginate($request->input('per_page', 50))
        );
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            Cliente::with(['deudas' => fn($q) => $q->pendientes()])->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:150',
            'rut'       => 'nullable|string|max:20',
            'giro'      => 'nullable|string|max:255',
            'telefono'  => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'email'     => 'nullable|email|max:100',
        ]);

        return response()->json(Cliente::create($data), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->validate([
            'nombre'    => 'sometimes|string|max:150',
            'rut'       => 'nullable|string|max:20',
            'giro'      => 'nullable|string|max:255',
            'telefono'  => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'email'     => 'nullable|email|max:100',
        ]);

        $cliente->update($data);

        return response()->json($cliente);
    }
}
