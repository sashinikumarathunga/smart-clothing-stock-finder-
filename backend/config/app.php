<?php

declare(strict_types=1);

return [
    // Origins allowed for CORS. Under XAMPP/Apache the SPA is same-origin
    // (http://localhost), so CORS is not strictly required, but these keep
    // common dev servers working too.
    'cors_origins' => [
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:5500',
        'http://127.0.0.1:5500',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],
    'token_ttl_hours' => 24,
];
