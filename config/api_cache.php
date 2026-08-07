<?php

return [
    'storefront_context_ttl' => (int) env('STOREFRONT_CONTEXT_CACHE_TTL', 300),
    'payment_methods_ttl' => (int) env('PAYMENT_METHODS_CACHE_TTL', 600),
    'shipping_ttl' => (int) env('SHIPPING_CACHE_TTL', 300),
    'http' => [
        'max_age' => (int) env('PUBLIC_HTTP_CACHE_MAX_AGE', 60),
        'shared_max_age' => (int) env('PUBLIC_HTTP_CACHE_SHARED_MAX_AGE', 300),
        'stale_while_revalidate' => (int) env('PUBLIC_HTTP_CACHE_STALE_WHILE_REVALIDATE', 30),
    ],
];
