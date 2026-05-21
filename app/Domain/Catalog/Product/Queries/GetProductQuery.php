<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Queries;


use App\Shared\Contracts\Caching\CacheableQuery;

final class GetProductQuery implements CacheableQuery
{
    public function __construct(
        public string $slug
    ) {
    }

    public function cacheKey(): string
    {
        return 'products' . md5(json_encode([
                'slug' => $this->slug,
            ]));
    }

    public function cacheTags(): array
    {
        return ['products:catalog'];
    }

    public function ttl(): int
    {
        return 300;
    }

}
