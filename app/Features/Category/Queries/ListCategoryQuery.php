<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Category\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class ListCategoryQuery implements CacheableQuery
{
    public function __construct(
        public ?string $parent = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }


    public function cacheKey(): string
    {
        return 'categories' . md5(json_encode([
                'parent' => $this->parent,
                'perPage' => $this->perPage,
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
