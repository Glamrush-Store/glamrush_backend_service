<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent;

use App\Features\Product\Contracts\ProductRepository;
use App\Features\Product\DTOs\ProductDetailDto;
use App\Features\Product\Mappers\ProductCatalogMapper;
use App\Features\Product\Mappers\ProductDetailsMapper;
use App\Features\Product\Queries\GetProductQuery;
use App\Features\Product\Queries\ListProductsQuery;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Models\Product as ProductModel;
use Illuminate\Pagination\LengthAwarePaginator;


final class EloquentProductRepository implements ProductRepository
{

    public function getProductDetails($query): ProductDetailDto
    {
        return QueryCache::remember($query, fn() => $this->ProductDetails($query));
    }

    public function ProductDetails(GetProductQuery $query): ProductDetailDto
    {
        $product = ProductModel::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.short_description',
                'products.description',
                'products.type',
                'products.status',
                'products.meta_title',
                'products.meta_description',
                'products.is_featured',
                'products.sort_order',
                'products.published_at',
                'products.category_id',
                'products.brand_id'
            ])
            ->where('slug', $query->slug)
            ->with([
                'category:id,name,slug,is_active',
                'brand:id,name,slug',
                'variants',
            ])->firstOrFail();


        return productDetailsMapper::fromModel($product);
    }


    public function paginateForCatalog(
        ListProductsQuery $query,
        int $perPage = 20
    ): LengthAwarePaginator {
        return QueryCache::remember(
            $query,
            fn() => $this->queryCatalog($query, $perPage)
        );
    }


    public function QueryCatalog(ListProductsQuery $query): LengthAwarePaginator
    {
        $builder = ProductModel::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.short_description',
                'products.description',
                'products.type',
                'products.status',
                'products.meta_title',
                'products.meta_description',
                'products.is_featured',
                'products.sort_order',
                'products.published_at',
                'products.category_id',
                'products.brand_id'
            ])
            ->with([
                'defaultVariant:id,product_id,price,sku,sale_price',
                'category:id,name,slug',
                'brand:id,name,slug'
            ])
            ->whereHas('defaultVariant');

        // CATEGORY FILTER
        if ($query->categorySlug) {
            $builder->whereHas('category', fn($q) => $q->where('slug', $query->categorySlug)
            );
        }

        // BRAND FILTER
        if ($query->brandSlug) {
            $builder->whereHas('brand', fn($q) => $q->where('slug', $query->brandSlug)
            );
        }

        // FEATURED FILTER
        if (!is_null($query->featured)) {
            $builder->where('is_featured', $query->featured);
        }

        // PRICE RANGE FILTER (variant-based)
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

        //SEARCH FILTER
        if ($query->filters) {
            $builder->filter($query->filters);
        }

        return $builder->paginate($query->perPage)
            ->through(fn(ProductModel $product) => ProductCatalogMapper::fromModel($product));
    }


}
