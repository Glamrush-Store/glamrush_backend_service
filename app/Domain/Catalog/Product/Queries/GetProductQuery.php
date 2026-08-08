<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Queries;

use App\Infrastructure\Caching\CacheTags;
use App\Shared\Contracts\Caching\CacheableQuery;

final class GetProductQuery implements CacheableQuery
{
    public function __construct(
        public string $slug,
        public ?string $storefrontRootSlug = null,
    ) {}

    public function cacheKey(): string
    {
        return 'products'.md5(json_encode([
            'slug' => $this->slug,
            'storefrontRootSlug' => $this->storefrontRootSlug,
        ]));
    }

    public function cacheTags(): array
    {
        return [CacheTags::CATALOG, CacheTags::PRODUCTS];
    }

    public function ttl(): int
    {
        return 300;
    }
}
