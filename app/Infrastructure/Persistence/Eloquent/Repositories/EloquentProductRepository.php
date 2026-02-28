<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Product\Contracts\ProductRepository;
use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\ProductMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductAttribute;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentProductRepository implements ProductRepository
{
    public function findBySlug(string $slug): ?ProductEntity
    {
        $model = Product::query()
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
            ->where('slug', $slug)
            ->with([
                'category:id,name,slug,is_active',
                'brand:id,name,slug',
                'variants',
                'variants.media',
            ])
            ->first();


        if (!$model) {
            return null;
        }

        return ProductMapper::toDomain($model);
    }

//    public function paginate(ListProductsQuery $query): LengthAwarePaginator
//    {
//        $builder = Product::query()
//            ->select([
//                'products.id',
//                'products.name',
//                'products.slug',
//                'products.type',
//                'products.is_featured',
//                'products.sort_order',
//                'products.category_id',
//                'products.brand_id'
//            ])
//            ->with([
//                'variants:id,product_id,price,sku,sale_price,sale_starts_at,sale_ends_at,stock_quantity,in_stock,attributes,sort_order,status',
//                'category:id,name,slug',
//                'brand:id,name,slug',
//                'vendor:id,name,slug'
//            ])
//            ->whereHas('defaultVariant');
//
//
//        // apply filters
//        if ($query->categorySlug) {
//            $builder->whereHas('category', fn($q) => $q->where('slug', $query->categorySlug)
//            );
//        }
//
//        if ($query->brandSlug) {
//            $builder->whereHas('brand', fn($q) => $q->where('slug', $query->brandSlug)
//            );
//        }
//
//        if (!is_null($query->featured)) {
//            $builder->where('is_featured', $query->featured);
//        }
//
//        $paginator = $builder->paginate($query->perPage);
//
//        $paginator->setCollection(
//            $paginator->getCollection()
//                ->map(fn($model) => ProductMapper::toDomain($model)
//                )
//        );
//
//        return $paginator;
//    }


    public function paginate(ListProductsQuery $query): LengthAwarePaginator
    {
        $builder = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.type',
                'products.is_featured',
                'products.sort_order',
                'products.category_id',
                'products.brand_id'
            ])
            ->with([
                'variants:id,product_id,price,sku,sale_price,sale_starts_at,is_default,sale_ends_at,stock_quantity,in_stock,attributes,sort_order,status',
                'category:id,name,slug',
                'brand:id,name,slug',
                'vendor:id,name,slug'
            ]);
        //->whereHas('defaultVariant');

        $builder->where('is_featured', true);

        if ($query->categorySlug) {
            $builder->whereHas('category', fn($q) => $q->where('slug', $query->categorySlug));
        }

        if ($query->brandSlug) {
            $builder->whereHas('brand', fn($q) => $q->where('slug', $query->brandSlug));
        }

        if (!is_null($query->featured)) {
            $builder->where('is_featured', $query->featured);
        }

        $paginator = $builder->paginate($query->perPage);

        // Collect all attribute codes+types from every variant on this page
        $allCodes = collect();
        $allTypes = collect();


        $paginator->getCollection()->each(function ($product) use (&$allCodes, &$allTypes) {
            $product->variants->each(function ($variant) use (&$allCodes, &$allTypes) {
                foreach ($variant->attributes ?? [] as $attr) {
                    $allTypes->push($attr['type']);
                    $allCodes->push($attr['value']); // 'value' here is the code e.g. 'BLACK'
                }
            });
        });


        $productAttributes = ProductAttribute::whereIn('value', $allCodes->unique()->values())
            ->whereIn('type', $allTypes->unique()->values())
            ->get()
            ->groupBy('type')
            ->map(fn($group) => $group->keyBy('value'));


        $paginator->getCollection()->each(function ($product) use ($productAttributes) {
            $product->variants->each(function ($variant) use ($productAttributes) {
                $enriched = [];

                foreach ($variant->attributes ?? [] as $attr) {
                    $type = $attr['type'];
                    $label = $attr['value'];

                    $match = $productAttributes->get($type)?->get($label);

                    $enriched[] = [
                        'type' => $type,
                        'code' => $match?->code,
                        'value' => $match?->value,
                        'display_type' => $match?->display_type,
                        'meta' => $match?->meta,
                    ];
                }

                $variant->setAttribute('attributes', $enriched);
            });
        });

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn($model) => ProductMapper::toDomain($model))
        );

        return $paginator;
    }


}

