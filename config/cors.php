<?php

// config/cors.php — editar para desarrollo local
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'auth/*', 'web/*', 'portal/*', 'admin/*', 'operario', 'rentas', 'pos/*'],

    'allowed_origins' => [
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://demo.localhost:8000',
        'http://demo-legal.localhost:8000',
        'http://demo-padel.localhost:8000',
        'http://demo-motel.localhost:8000',
        'http://demo-abarrotes.localhost:8000',
        'http://demo-ferreteria.localhost:8000',
        'http://demo-medico.localhost:8000',
        'http://demo-saas.localhost:8000',
    ],

    'allowed_origins_patterns' => [
        // Para flexibilidad en dev:
        // '/^http://.*\.localhost(:\d+)?$/',
    ],

    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // ← IMPORTANTE para cookies de sesión
];