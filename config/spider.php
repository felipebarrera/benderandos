<?php
return [
    'url_mapping' => [
        'super' => env('SPIDER_SUPER_URL', 'http://localhost:8000'),
        'tenant' => env('SPIDER_TENANT_URL', 'http://demo-ferreteria.localhost:8000'),
        // Futuro: permitir múltiples tenants
        // 'tenant-legal' => 'http://demo-legal.localhost:8000',
        // 'tenant-medico' => 'http://demo-medico.localhost:8000',
    ],
];