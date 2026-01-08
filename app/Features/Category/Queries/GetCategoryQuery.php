<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Category\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class GetCategoryQuery implements CacheableQuery
{
    public function __construct(
        public string $slug
    ) {
    }

    public function cacheKey(): string
    {
        return 'categories' . md5(json_encode([
                'slug' => $this->slug,
            ]));
    }

    public function cacheTags(): array
    {
        return ['categories'];
    }

    public function ttl(): int
    {
        return 300;
    }


}
