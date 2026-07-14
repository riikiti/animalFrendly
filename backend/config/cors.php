<?php

declare(strict_types=1);

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Request-Id'],

    'max_age' => 0,

    // Аутентификация через Bearer-токены (Sanctum personal access tokens), а не через
    // cookie-based SPA auth — см. docs/plan/04-backend-structure.md. Credentials/cookies
    // между источниками не нужны.
    'supports_credentials' => false,

];
