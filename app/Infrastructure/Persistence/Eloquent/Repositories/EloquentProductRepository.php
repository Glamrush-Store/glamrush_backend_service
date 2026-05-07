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
    public function __construct(
        private readonly VariantAttributeEnricher $enricher,
    ) {
    }

    public function findBySlug(string $slug): ?ProductEntity
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
                'products.category_id',
                'products.brand_id',
            ])
            ->with([
                'variants:id,product_id,price,sku,sale_price,sale_starts_at,is_default,sale_ends_at,manage_stock,stock_quantity,in_stock,attributes,sort_order,status',
                'category:id,name,slug',
                'brand:id,name,slug',
                'vendor:id,name,slug',
            ]);

        $this->constrainQuery($builder, $query);

        $paginator = $builder->paginate($query->perPage);

        $this->enricher->enrichMany($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn($model) => ProductMapper::toDomain($model))
        );

        return $paginator;
    }

    private function constrainQuery(Builder $builder, ListProductsQuery $query, array $exclude = []): void
    {
        if (!in_array('category', $exclude) && $query->categorySlug) {
            $category = Category::where('slug', $query->categorySlug)->first();
            if ($category) {
                $ids = $this->getCategoryDescendantIds($category);
                $builder->whereIn('products.category_id', $ids);
            }
        }

        if (!in_array('brand', $exclude) && $query->brandSlug) {
            $builder->whereHas('brand', fn($q) => $q->where('slug', $query->brandSlug));
        }

        if (!in_array('featured', $exclude) && !is_null($query->featured)) {
            $builder->where('is_featured', $query->featured);
        }

        if (!in_array('price', $exclude) && ($query->minPrice || $query->maxPrice)) {
            $builder->whereHas('defaultVariant', function ($q) use ($query) {
                if ($query->minPrice) {
                    $q->where('price', '>=', $query->minPrice);
                }
                if ($query->maxPrice) {
                    $q->where('price', '<=', $query->maxPrice);
                }
            });
        }

        if (!in_array('search', $exclude) && $query->search) {
            $search = $query->search;
            $builder->where(fn($q) => $q
                ->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.slug', 'like', "%{$search}%")
            );
        }

        if (!in_array('attributes', $exclude)) {
            //dd("attributes");
            $has = $query->filters['attributes']['$has'] ?? null;
            if (!empty($has)) {
                // Normalize: single object → wrap in array
                $conditions = isset($has['type']) ? [$has] : array_values($has);

                foreach ($conditions as $condition) {
                    if (empty($condition['type']) || empty($condition['value'])) {
                        continue;
                    }
                    $json = json_encode([['type' => $condition['type'], 'value' => $condition['value']]]);
                    $builder->whereHas('variants', fn($q) => $q->whereRaw("attributes::jsonb @> ?::jsonb", [$json]));
                }
            }
        }
    }

    private function getCategoryDescendantIds(Category $category): array
    {
        $ids = [$category->id];
        $category->load('childrenRecursive');

        $collect = function (Category $node) use (&$collect, &$ids): void {
            foreach ($node->childrenRecursive as $child) {
                $ids[] = $child->id;
                $collect($child);
            }
        };

        $collect($category);

        return $ids;
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
            ->tap(fn(Builder $q) => $this->constrainQuery($q, $query, ['price']))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return ['min' => 0.0, 'max' => 0.0];
        }

        $result = ProductVariant::whereIn('product_id', $productIds)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return [
            'min' => (float)($result->min_price ?? 0),
            'max' => (float)($result->max_price ?? 0),
        ];
    }

    private function getBrandFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn(Builder $q) => $this->constrainQuery($q, $query, ['brand']))
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
            ->map(fn($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'count' => (int)$row->count,
            ])
            ->values()
            ->toArray();
    }

    private function getCategoryFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn(Builder $q) => $this->constrainQuery($q, $query, ['category']))
            ->pluck('products.id');

        if ($productIds->isEmpty()) {
            return [];
        }

        $categoryQuery = Category::query()
            ->selectRaw('categories.id, categories.name, categories.slug, COUNT(products.id) as count')
            ->join('products', 'products.category_id', '=', 'categories.id')
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
            ->map(fn($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'count' => (int)$row->count,
            ])
            ->values()
            ->toArray();
    }

    private function getAttributeFacets(ListProductsQuery $query): array
    {
        $productIds = Product::query()
            ->tap(fn(Builder $q) => $this->constrainQuery($q, $query))
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
                        if (!isset($attr['type'], $attr['value']) || !is_string($attr['type']) || !is_string(
                                $attr['value']
                            )) {
                            continue;
                        }
                        $seen[$attr['type'] . ':' . $attr['value']] = [
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
        $allValues = collect($counts)->flatMap(fn($values) => array_keys($values))->unique()->values();

        $enrichment = ProductAttribute::whereIn('type', $allTypes)
            ->whereIn('value', $allValues)
            ->get()
            ->groupBy('type')
            ->map(fn($group) => $group->keyBy('value'));

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
            usort($options, fn($a, $b) => $b['count'] - $a['count']);
            $facets[] = [
                'type' => $type,
                'display_type' => $attributeTypes->get($type)?->display_type,
                'options' => $options,
            ];
        }

        return $facets;
    }
}
