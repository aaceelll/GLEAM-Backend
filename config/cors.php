<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'], // GET, POST, PUT, DELETE, OPTIONS
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // atau spesifik: ['Content-Type','Authorization','Accept','X-Requested-With']
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => false, // pakai Bearer token, jadi false
];
