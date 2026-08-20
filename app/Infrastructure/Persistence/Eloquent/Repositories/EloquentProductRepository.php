<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Product\Contracts\ProductRepository;
use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Infrastructure\Persistence\Eloquent\Enrichers\VariantAttributeEnricher;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\ProductMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Brand;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductAttribute;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepository
{
    private const SORT_COLUMNS = [
        'name' => 'products.name',
        'price' => 'products.price',
        'created_at' => 'products.created_at',
        'sort_order' => 'products.sort_order',
        'featured' => 'products.is_featured',
    ];

    public function __construct(
        private readonly VariantAttributeEnricher $enricher,
    ) {}

    public function findBySlug(GetProductQuery $query): ?ProductEntity
    {
        $model = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.short_description',
                'products.description',
                'products.type',
                'products.price',
                'products.sale_price',
                'products.sale_starts_at',
                'products.sale_ends_at',
                'products.manage_stock',
                'products.stock_quantity',
                'products.in_stock',
                'products.status',
                'products.meta_title',
                'products.meta_description',
                'products.is_featured',
                'products.sort_order',
                'products.published_at',
                'products.brand_id',
            ])
            ->where('slug', $query->slug)
            ->when($query->storefrontRootSlug, function (Builder $builder, string $rootSlug) {
                $root = Category::query()->where('slug', $rootSlug)->first();

                $builder->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                    ->whereIn('categories.id', $root ? $this->getCategoryDescendantIds($root, activeOnly: true) : []));
            })
            ->with([
                'categories:id,name,slug,is_active',
                'primaryCategory:id,name,slug,is_active',
                'brand:id,name,slug',
                'variants',
                'variants.media',
            ])
            ->first();

        if (! $model) {
            return null;
        }

        $this->enricher->enrich($model);

        return ProductMapper::toDomain($model);
    }

    public function paginate(ListProductsQuery $query): LengthAwarePaginator
    {
        $builder = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.type',
                'products.price',
                'products.sale_price',
                'products.sale_starts_at',
                'products.sale_ends_at',
                'products.manage_stock',
                'products.stock_quantity',
                'products.in_stock',
                'products.is_featured',
                'products.sort_order',
                'products.brand_id',
            ])
            ->with([
                'variants:id,product_id,price,sku,sale_price,sale_starts_at,is_default,sale_ends_at,manage_stock,stock_quantity,in_stock,attributes,sort_order,status',
                'categories:id,name,slug',
                'primaryCategory:id,name,slug',
                'brand:id,name,slug',
                'vendor:id,name,slug',
            ]);

        $this->constrainQuery($builder, $query);
        $this->applySorting($builder, $query);

        $paginator = $builder->paginate($query->perPage);

        $this->enricher->enrichMany($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn ($model) => ProductMapper::toDomain($model))
        );

        return $paginator;
    }

    private function constrainQuery(Builder $builder, ListProductsQuery $query, array $exclude = []): void
    {
        if ($query->storefrontRootSlug) {
            $root = Category::query()->where('slug', $query->storefrontRootSlug)->first();
            $builder->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                ->whereIn('categories.id', $root ? $this->getCategoryDescendantIds($root, activeOnly: true) : []));
        }

        if (! in_array('category', $exclude) && $query->categorySlug) {
            $category = Category::where('slug', $query->categorySlug)->first();
            if ($category) {
                $ids = $this->getCategoryDescendantIds($category);
                $builder->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $ids));
            }
        }

        if (! in_array('brand', $exclude) && $query->brandSlug) {
            $builder->whereHas('brand', fn ($q) => $q->where('slug', $query->brandSlug));
        }

        if (! in_array('collection', $exclude) && $query->collectionSlug) {
            $builder->whereHas('collections', fn ($q) => $q->where('collections.slug', $query->collectionSlug));
        }

        if (! in_array('featured', $exclude) && ! is_null($query->featured)) {
            $builder->where('is_featured', $query->featured);
        }

        if (! in_array('onSale', $exclude) && ! is_null($query->onSale)) {
            $this->constrainOnSale($builder, $query->onSale);
        }

        if (! in_array('price', $exclude) && ($query->minPrice || $query->maxPrice)) {
            $builder->whereHas('defaultVariant', function ($q) use ($query) {
                if ($query->minPrice) {
                    $q->where('price', '>=', $query->minPrice);
                }
                if ($query->maxPrice) {
                    $q->where('price', '<=', $query->maxPrice);
                }
            });
        }

        if (! in_array('search', $exclude) && $query->search) {
            $terms = preg_split('/\s+/', mb_strtolower(trim($query->search))) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = "%{$term}%";

                $builder->where(fn (Builder $searchQuery) => $searchQuery
                    ->whereRaw('LOWER(products.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(products.slug) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(products.sku, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(products.short_description, '')) LIKE ?", [$like])
                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery
                        ->whereRaw('LOWER(brands.name) LIKE ?', [$like]))
                    ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                        ->whereRaw('LOWER(categories.name) LIKE ?', [$like]))
                );
            }
        }

        if (! in_array('attributes', $exclude)) {
            // dd("attributes");
            $has = $query->filters['attributes']['$has'] ?? null;
            if (! empty($has)) {
                // Normalize: single object → wrap in array
                $conditions = isset($has['type']) ? [$has] : array_values($has);

                foreach ($conditions as $condition) {
                    if (empty($condition['type']) || empty($condition['value'])) {
                        continue;
                    }
                    $json = json_encode([['type' => $condition['type'], 'value' => $condition['value']]]);
                    $builder->whereHas('variants', fn ($q) => $q->whereRaw('attributes::jsonb @> ?::jsonb', [$json]));
                }
            }
        }
    }

    private function getCategoryDescendantIds(Category $category, bool $activeOnly = false): array
    {
        $ids = [$category->id];
        $category->load('childrenRecursive');

        $collect = function (Category $node) use (&$collect, &$ids, $activeOnly): void {
            foreach ($node->childrenRecursive as $child) {
                if ($activeOnly && ! $child->is_active) {
                    continue;
                }

                $ids[] = $child->id;
                $collect($child);
            }
        };

        $collect($category);

        return $ids;
    }

    private function constrainOnSale(Builder $builder, bool $onSale): void
    {
        $now = now();

        if ($onSale) {
            $builder->where(function (Builder $q) use ($now) {
                $q->whereNotNull('products.sale_price')
                    ->where('products.sale_price', '>', 0)
                    ->whereNotNull('products.sale_starts_at')
                    ->where('products.sale_starts_at', '<=', $now)
                    ->whereNotNull('products.sale_ends_at')
                    ->where('products.sale_ends_at', '>=', $now);
            });

            return;
        }

        $builder->where(function (Builder $q) use ($now) {
            $q->whereNull('products.sale_price')
                ->orWhere('products.sale_price', '<=', 0)
                ->orWhereNull('products.sale_starts_at')
                ->orWhere('products.sale_starts_at', '>', $now)
                ->orWhereNull('products.sale_ends_at')
                ->orWhere('products.sale_ends_at', '<', $now);
        });
    }

    private function applySorting(Builder $builder, ListProductsQuery $query): void
    {
        $direction = strtolower($query->direction) === 'desc' ? 'desc' : 'asc';
        $sortColumn = $query->sort ? (self::SORT_COLUMNS[$query->sort] ?? null) : null;

        if ($sortColumn) {
            $builder->orderBy($sortColumn, $direction);
        }

        $builder
            ->orderBy('products.sort_order')
            ->orderBy('products.name');
    }

    // ------------------------------------------------------------------
    // Shared filter constraint (used by paginate + all facet queries)
    // ------------------------------------------------------------------

    public function getFacets(ListProductsQuery $query): array
    {
        return [
            'price_range' => $this->getPriceRangeFacet($query),
            'brands' => $this->getBrandFacets($query),
            'categories' => $this->getCategoryFacets($query),
            'attributes' => $this->getAttributeFacets($query),
        ];
    }

    // ------------------------------------------------------------------
    // Facet sub-queries
    // ------------------------------------------------------------------

    private function getPriceRangeFacet(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn (Builder $q) => $this->constrainQuery($q, $query, ['price']))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return ['min' => 0.0, 'max' => 0.0];
        }

        $now = now();
        $priceExpression = $this->effectiveCatalogPriceExpression();
        $priceBindings = [$now, $now, $now, $now];

        $result = DB::table('products')
            ->leftJoin('product_variants as default_price_variant', function ($join) {
                $join->on('default_price_variant.product_id', '=', 'products.id')
                    ->where('default_price_variant.is_default', true);
            })
            ->whereIn('products.id', $productIds)
            ->selectRaw(
                "MIN({$priceExpression}) as min_price, MAX({$priceExpression}) as max_price",
                [...$priceBindings, ...$priceBindings],
            )
            ->first();

        return [
            'min' => (float) ($result->min_price ?? 0),
            'max' => (float) ($result->max_price ?? 0),
        ];
    }

    private function effectiveCatalogPriceExpression(): string
    {
        return <<<'SQL'
            CASE
                WHEN products.type = 'variable' THEN
                    CASE
                        WHEN default_price_variant.sale_price IS NOT NULL
                            AND default_price_variant.sale_price > 0
                            AND default_price_variant.sale_price < default_price_variant.price
                            AND default_price_variant.sale_starts_at IS NOT NULL
                            AND default_price_variant.sale_starts_at <= ?
                            AND default_price_variant.sale_ends_at IS NOT NULL
                            AND default_price_variant.sale_ends_at >= ?
                        THEN default_price_variant.sale_price
                        ELSE default_price_variant.price
                    END
                ELSE
                    CASE
                        WHEN products.sale_price IS NOT NULL
                            AND products.sale_price > 0
                            AND products.sale_price < products.price
                            AND products.sale_starts_at IS NOT NULL
                            AND products.sale_starts_at <= ?
                            AND products.sale_ends_at IS NOT NULL
                            AND products.sale_ends_at >= ?
                        THEN products.sale_price
                        ELSE products.price
                    END
            END
            SQL;
    }

    private function getBrandFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn (Builder $q) => $this->constrainQuery($q, $query, ['brand']))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return [];
        }

        return Brand::query()
            ->selectRaw('brands.id, brands.name, brands.slug, COUNT(products.id) as count')
            ->join('products', 'products.brand_id', '=', 'brands.id')
            ->whereIn('products.id', $productIds)
            ->groupBy('brands.id', 'brands.name', 'brands.slug')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();
    }

    private function getCategoryFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn (Builder $q) => $this->constrainQuery($q, $query, ['category']))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return [];
        }

        $categoryQuery = Category::query()
            ->selectRaw('categories.id, categories.name, categories.slug, COUNT(DISTINCT products.id) as count')
            ->join('category_product', 'category_product.category_id', '=', 'categories.id')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('products.id', $productIds)
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderByDesc('count');

        if ($query->categorySlug) {
            $parent = Category::where('slug', $query->categorySlug)->first();
            if ($parent) {
                $categoryQuery->where('categories.parent_id', $parent->id);
            }
        }

        return $categoryQuery->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();
    }

    private function getAttributeFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn (Builder $q) => $this->constrainQuery($q, $query))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return [];
        }

        // Collect unique type+value combos per product (deduplicated within each product)
        $counts = [];
        ProductVariant::whereIn('product_id', $productIds)
            ->select(['product_id', 'attributes'])
            ->get()
            ->groupBy('product_id')
            ->each(function ($variants) use (&$counts) {
                $seen = [];
                foreach ($variants as $variant) {
                    foreach ($variant->attributes ?? [] as $attr) {
                        if (! isset($attr['type'], $attr['value']) || ! is_string($attr['type']) || ! is_string(
                            $attr['value']
                        )) {
                            continue;
                        }
                        $seen[$attr['type'].':'.$attr['value']] = [
                            'type' => $attr['type'],
                            'value' => $attr['value'],
                        ];
                    }
                }
                foreach ($seen as $attr) {
                    $counts[$attr['type']][$attr['value']] = ($counts[$attr['type']][$attr['value']] ?? 0) + 1;
                }
            });

        if (empty($counts)) {
            return [];
        }

        $allTypes = array_keys($counts);
        $allValues = collect($counts)->flatMap(fn ($values) => array_keys($values))->unique()->values();

        $enrichment = ProductAttribute::whereIn('type', $allTypes)
            ->whereIn('value', $allValues)
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->keyBy('value'));

        $attributeTypes = DB::table('attribute_types')
            ->whereIn('value', $allTypes)
            ->get()
            ->keyBy('value');

        $facets = [];
        foreach ($counts as $type => $values) {
            $options = [];
            foreach ($values as $rawValue => $count) {
                $meta = $enrichment->get($type)?->get($rawValue);
                $options[] = [
                    'value' => $rawValue,
                    'label' => $meta?->value ?? $rawValue,
                    'code' => $meta?->code,
                    'meta' => $meta?->meta,
                    'count' => $count,
                ];
            }
            usort($options, fn ($a, $b) => $b['count'] - $a['count']);
            $facets[] = [
                'type' => $type,
                'display_type' => $attributeTypes->get($type)?->display_type,
                'options' => $options,
            ];
        }

        return $facets;
    }
}
