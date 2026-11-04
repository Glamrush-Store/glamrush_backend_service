<?php

return [
    'homepage' => [
        'cache_ttl' => (int) env('STOREFRONT_HOMEPAGE_CACHE_TTL', 300),
        'default_item_limit' => 8,
        'max_item_limit' => (int) env('STOREFRONT_HOMEPAGE_MAX_ITEMS', 50),
        'default_category_limit' => 6,
        'max_category_limit' => 6,
        'max_product_limit' => 4,
    ],
];
