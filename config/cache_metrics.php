<?php

return [
    'enabled' => env('CACHE_METRICS_ENABLED', true),
    'service_name' => env('CACHE_METRICS_SERVICE', 'backend_service'),
    'redis_connection' => env('CACHE_METRICS_REDIS_CONNECTION', env('REDIS_CACHE_CONNECTION', 'cache')),
    'key_prefix' => env('CACHE_METRICS_KEY_PREFIX', 'glamrush:metrics:cache'),
    'bucket_minutes' => max(1, (int) env('CACHE_METRICS_BUCKET_MINUTES', 5)),
    'raw_ttl_seconds' => max(3600, (int) env('CACHE_METRICS_RAW_TTL_SECONDS', 172800)),
];
