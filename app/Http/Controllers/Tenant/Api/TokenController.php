<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    /**
     * Listar tokens del usuario actual
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'tokens' => $tokens->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_four' => substr($t->token, -4),
                'last_used_at' => $t->last_used_at,
                'created_at' => $t->created_at
            ])
        ]);
    }

    /**
     * Crear un nuevo token
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $token = $request->user()->createToken($request->name);

        return response()->json([
            'token' => $token->plainTextToken
        ]);
    }

    /**
     * Revocar un token específico
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();
        
        return response()->json(['message' => 'Token revocado correctamente']);
    }
}
