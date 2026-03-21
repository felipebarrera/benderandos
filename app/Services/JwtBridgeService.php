<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Central\Tenant;

class JwtBridgeService
{
    /**
     * Generar un token JWT para un tenant específico.
     */
    public function generarToken(Tenant $tenant): string
    {
        $secret = config('tenancy.jwt_secret', env('JWT_BRIDGE_SECRET', 'benderand_shared_secret'));
        
        $payload = [
            'tenant_id'     => $tenant->id,
            'tenant_domain' => $tenant->domain, // O el campo real que represente el dominio
            'iat'           => time(),
            'exp'           => time() + 3600, // 1 hora de validez
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Validar y decodificar un token JWT.
     */
    public function validarToken(string $token): array
    {
        $secret = config('tenancy.jwt_secret', env('JWT_BRIDGE_SECRET', 'benderand_shared_secret'));
        
        return (array) JWT::decode($token, new Key($secret, 'HS256'));
    }
}
