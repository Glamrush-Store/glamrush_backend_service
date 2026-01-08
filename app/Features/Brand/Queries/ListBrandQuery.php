<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class ListBrandQuery implements CacheableQuery
{
    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }

    public function cacheKey(): string
    {
        return 'brands' . md5(json_encode([
                'search' => $this->search,
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
