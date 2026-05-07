<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Services;

use App\Domain\Catalog\Product\Contracts\ProductRepository;
use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Queries\ListFacetsQuery;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Infrastructure\Caching\QueryCache;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

final class CatalogService
{
    public function __construct(
        private ProductRepository $products
    ) {
    }

    public function getProduct(GetProductQuery $query): ?ProductEntity
    {
        return QueryCache::remember($query, function () use ($query) {
            $product = $this->products->findBySlug($query->slug);

            if (!$product) {
                throw new RuntimeException('Product not found');
            }

            return $product;
        });
    }

    public function paginateCatalog(
        ListProductsQuery $query
    ): LengthAwarePaginator {
        return QueryCache::remember($query, function () use ($query) {
            return $this->products->paginate($query);
        });
    }

    public function getFacets(ListProductsQuery $query): array
    {
        return QueryCache::remember(new ListFacetsQuery($query), function () use ($query) {
            return $this->products->getFacets($query);
        });
    }


    private function applyFilters($builder, ListProductsQuery $query): void
    {
        if ($query->categorySlug) {
            $builder->whereHas(
                'category',
                fn($q) => $q->where('slug', $query->categorySlug)
            );
        }

        if ($query->brandSlug) {
            $builder->whereHas(
                'brand',
                fn($q) => $q->where('slug', $query->brandSlug)
            );
        }

        if (!is_null($query->featured)) {
            $builder->where('is_featured', $query->featured);
        }

        if ($query->minPrice || $query->maxPrice) {
            $builder->whereHas('defaultVariant', function ($q) use ($query) {
                if ($query->minPrice) {
                    $q->where('price', '>=', $query->minPrice);
                }
                if ($query->maxPrice) {
                    $q->where('price', '<=', $query->maxPrice);
                }
            });
        }

        if ($query->filters) {
            $builder->filter($query->filters);
        }
    }
}
