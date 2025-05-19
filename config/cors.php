<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'], // Autorise toutes les méthodes (GET, POST, etc.)

    'allowed_origins' => ['*'], // Remplace par l'URL de ton front (React/Vite/Next.js)

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
