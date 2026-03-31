<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalBotAuth
{
    public function handle(Request $request, Closure $next)
    {
        $botToken = $request->header('X-Bot-Token');
        $authHeader = $request->header('Authorization');
        $sharedSecret = env('JWT_SHARED_SECRET') ?? env('BOT_INTERNAL_SECRET') ?? env('INTERNAL_API_SECRET');

        // Validar X-Bot-Token
        if ($botToken && $botToken === $sharedSecret) {
            return $next($request);
        }

        // Validar Bearer token (JWT simple)
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            if ($token === $sharedSecret) {
                return $next($request);
            }
        }

        // Bypass en desarrollo local
        if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
            \Log::info('Bot auth bypass en desarrollo');
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}