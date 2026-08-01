<?php

namespace App\Domain\Catalog\Product\Queries;

use App\Shared\Contracts\Caching\CacheableQuery;

final class ListFacetsQuery implements CacheableQuery
{
    public function __construct(public readonly ListProductsQuery $inner) {}

    public function cacheKey(): string
    {
        return 'catalog:facets:'.md5(json_encode([
            'category' => $this->inner->categorySlug,
            'brand' => $this->inner->brandSlug,
            'featured' => $this->inner->featured,
            'onSale' => $this->inner->onSale,
            'minPrice' => $this->inner->minPrice,
            'maxPrice' => $this->inner->maxPrice,
            'search' => $this->inner->search,
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
