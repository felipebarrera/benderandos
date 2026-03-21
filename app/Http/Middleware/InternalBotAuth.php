<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalBotAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sharedSecret = env('JWT_SHARED_SECRET');
        $botToken = $request->header('X-Bot-Token') ?: $request->input('bot_token');

        if (!$botToken || $botToken !== $sharedSecret) {
            return response()->json([
                'message' => 'Unauthorized: Invalid Bot Token',
                'status' => 'error'
            ], 401);
        }

        return $next($request);
    }
}
