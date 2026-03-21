<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant() && tenant('estado') === 'suspendido') {
            return response()->json([
                'message' => 'Esta cuenta ha sido suspendida. Contacte con soporte.'
            ], 403);
        }

        return $next($request);
    }
}
