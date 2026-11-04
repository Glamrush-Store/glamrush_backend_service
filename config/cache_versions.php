<?php

use App\Infrastructure\Caching\CacheTags;

return [
    /*
    | The storefront briefly caches the shared database versions in its own
    | Redis namespace. Admin changes therefore become visible within this
    | bounded interval without either application touching the other's keys.
    */
    'check_ttl_seconds' => (int) env('CACHE_VERSION_CHECK_TTL_SECONDS', 10),

    'tag_namespaces' => [
        CacheTags::CATALOG => 'catalog',
        CacheTags::PRODUCTS => 'catalog',
        CacheTags::CATEGORIES => 'catalog',
        CacheTags::BRANDS => 'catalog',
        CacheTags::COLLECTIONS => 'catalog',
        CacheTags::STOREFRONTS => 'catalog',
        CacheTags::HOMEPAGE => 'homepage',
        CacheTags::PAYMENT_METHODS => 'payment-methods',
        CacheTags::SHIPPING => 'shipping',
    ],
];
