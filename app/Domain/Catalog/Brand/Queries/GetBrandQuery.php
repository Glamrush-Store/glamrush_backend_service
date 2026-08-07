<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Brand\Queries;

use App\Infrastructure\Caching\CacheTags;
use App\Shared\Contracts\Caching\CacheableQuery;

final class GetBrandQuery implements CacheableQuery
{
    public function __construct(
        public string $slug
    ) {}

    public function cacheKey(): string
    {
        return 'brands'.md5(json_encode([
            'slug' => $this->slug,
        ]));
    }

    public function cacheTags(): array
    {
        return [CacheTags::CATALOG, CacheTags::BRANDS];
    }

    public function ttl(): int
    {
        return 300;
    }
}
