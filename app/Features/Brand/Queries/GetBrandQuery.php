<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class GetBrandQuery implements CacheableQuery
{
    public function __construct(
        public string $slug
    ) {
    }

    public function cacheKey(): string
    {
        return 'brands' . md5(json_encode([
                'slug' => $this->slug,
            ]));
    }
    
    public function cacheTags(): array
    {
        return ['brands'];
    }

    public function ttl(): int
    {
        return 300;
    }
}
