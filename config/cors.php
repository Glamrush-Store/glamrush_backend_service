<?php

$local = env('APP_ENV', 'production') === 'local';
$configuredOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', '')),
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $local ? ['*'] : $configuredOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 600,
    'supports_credentials' => true,
];
