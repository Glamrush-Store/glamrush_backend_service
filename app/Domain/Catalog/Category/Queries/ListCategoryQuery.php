<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Category\Queries;

use App\Infrastructure\Caching\CacheTags;
use App\Shared\Contracts\Caching\CacheableQuery;

final class ListCategoryQuery implements CacheableQuery
{
    public function __construct(
        public ?string $parent = null,
        public ?bool $deep = true,
        public int $page = 1,
        public int $perPage = 20,
        public ?string $storefrontRootSlug = null,
    ) {}

    public function cacheKey(): string
    {
        return 'categories'.md5(json_encode([
            'parent' => $this->parent,
            'perPage' => $this->perPage,
            'deep' => $this->deep,
            'storefrontRootSlug' => $this->storefrontRootSlug,
        ]));
    }

    public function cacheTags(): array
    {
        return [CacheTags::CATALOG, CacheTags::CATEGORIES];
    }

    public function ttl(): int
    {
        return 300;
    }
}
