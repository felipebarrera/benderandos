<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

echo "=== BenderAnd API Diagnóstico ===\n";

$tenant = Tenant::find('demo');
if (!$tenant) { echo "Tenant 'demo' no existe.\n"; exit(1); }
tenancy()->initialize($tenant);

$user = App\Models\Tenant\Usuario::where('email', 'admin@demo.cl')->first();
auth()->login($user);
echo "Usuario logueado: {$user->email} ({$user->rol})\n\n";

$endpoints = [
    ['method' => 'GET', 'uri' => '/api/dashboard'],
    ['method' => 'GET', 'uri' => '/api/productos'],
    ['method' => 'GET', 'uri' => '/api/clientes'],
    ['method' => 'GET', 'uri' => '/api/ventas'],
    ['method' => 'GET', 'uri' => '/api/compras'],
    ['method' => 'GET', 'uri' => '/api/usuarios'],
    ['method' => 'GET', 'uri' => '/api/roles'],
    ['method' => 'GET', 'uri' => '/api/rentas/panel'],
    ['method' => 'GET', 'uri' => '/auth/me'],
];

$errores = [];

foreach ($endpoints as $ep) {
    // Para que Sanctum confíe en la petición como "stateful" (SPA auth), simulamos el referer
    $req = Request::create('http://demo.localhost:8000' . $ep['uri'], $ep['method']);
    $req->headers->set('referer', 'http://demo.localhost:8000');
    // Injectamos la sesión manual si hace falta, o simplemente pasamos el request
    try {
        $res = app()->handle($req);
        $code = $res->getStatusCode();
        echo str_pad($ep['method'] . ' ' . $ep['uri'], 25) . " -> HTTP $code\n";
        
        if ($code >= 400 && $code != 404 && $code != 405 && $code != 422) {
            $excerpt = substr(strip_tags($res->getContent()), 0, 500);
            $errores[] = "[$code] " . $ep['uri'] . "\n" . trim(preg_replace('/\s+/', ' ', $excerpt));
        }
    } catch (\Throwable $e) {
        echo str_pad($ep['method'] . ' ' . $ep['uri'], 25) . " -> FATAL ERROR\n";
        $errores[] = "[FATAL] " . $ep['uri'] . "\n" . $e->getMessage();
    }
}

echo "\n=== Resumen de Errores ===\n";
if (empty($errores)) {
    echo "¡Ningún error encontrado (401/500)!\n";
} else {
    foreach ($errores as $err) {
        echo "\n----------------------------------------\n";
        echo $err . "\n";
    }
}
