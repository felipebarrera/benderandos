<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogUnauthorizedRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Si la respuesta es un error (4xx o 5xx)
            if ($response->getStatusCode() >= 400) {
                $this->logRequestParams($request, $response->getStatusCode(), "HTTP_ERROR");
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            // Si ocurre un error fatal o excepción antes de generar una Response normal
            $this->logRequestParams($request, 500, "FATAL_EXCEPTION", $e->getMessage());
            throw $e;
        }
    }

    private function logRequestParams(Request $request, int $statusCode, string $type, string $message = '')
    {
        $logData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'referer' => $request->headers->get('referer'),
            'origin' => $request->headers->get('origin'),
            'user_agent' => $request->userAgent(),
            'is_xmlhttprequest' => $request->ajax(),
            'cookies' => $request->cookies->all(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : 'No session',
            'headers' => $request->headers->all(),
            'exception_message' => $message
        ];

        Log::channel('single')->error("QA_LOG [$type - $statusCode] on " . $request->path() . "\n" . json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
