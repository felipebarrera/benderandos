<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtBridgeService;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Central\Tenant;

class JwtBridgeMiddleware
{
    protected $jwtService;

    public function __construct(JwtBridgeService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Authorization header missing or invalid'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $payload = $this->jwtService->validarToken($token);
            
            // Inicializar el Tenant si los claims están presentes
            if (isset($payload['tenant_id'])) {
                $tenant = Tenant::find($payload['tenant_id']);
                if ($tenant) {
                    Tenancy::initialize($tenant);
                } else {
                    return response()->json(['message' => 'Tenant not found'], 404);
                }
            } else {
                return response()->json(['message' => 'Invalid token claims'], 401);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
