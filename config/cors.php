<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://owfinances.com',
        'https://www.owfinances.com',
        // Dev/stage — remover en produccion pura si se desea
        'https://appfinanzas.blockshift.website',
        'https://appfinanzasdev.blockshift.website',
        'http://localhost:9000',
        'http://127.0.0.1:9000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
