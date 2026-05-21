<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class ListProductsQuery implements CacheableQuery
{


    public function __construct(
        public ?string $categorySlug,
        public ?string $brandSlug,
        public ?string $sort = null,
        public string $direction = 'asc',
        public ?array $filters = [],
        public ?bool $featured,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $search = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }

    public function cacheKey(): string
    {
        return 'catalog:products:' . md5(json_encode([
                'category' => $this->categorySlug,
                'brand' => $this->brandSlug,
                'page' => $this->page,
                'sort' => $this->sort,
                'direction' => $this->direction,
                'filters' => $this->filters,
                'featured' => $this->featured,
                'minPrice' => $this->minPrice,
                'maxPrice' => $this->maxPrice,
                'search' => $this->search,
                'perPage' => $this->perPage,
            ]));
    }

    public function cacheTags(): array
    {
        return ['catalog', 'products'];
    }

    public function ttl(): int
    {
        return 300;
    }


}
