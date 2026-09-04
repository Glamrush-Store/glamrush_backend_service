<?php

namespace App\Domain\Storefront\Services;

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Storefront\Enums\HomepageSectionType;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\CategoryMapper;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\ProductMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductCollection;
use App\Infrastructure\Persistence\Eloquent\Models\StorefrontCampaign;
use App\Infrastructure\Persistence\Eloquent\Models\StorefrontHomepageSection;
use App\Presentation\Http\Resources\Catalog\CategoryResource;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use App\Support\Media\SafeMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class StorefrontHomepageService
{
    private const SORT_COLUMNS = [
        'created_at' => 'products.created_at',
        'price' => 'products.price',
        'sort_order' => 'products.sort_order',
        'name' => 'products.name',
    ];

    /** @var Collection<string, Category>|null */
    private ?Collection $storefrontCategories = null;

    public function __construct(
        private readonly StorefrontContext $storefrontContext,
    ) {}

    public function get(string $storefront): array
    {
        $ttl = max(0, (int) config('storefront.homepage.cache_ttl', 300));
        $key = "storefront:{$storefront}:homepage:v7";

        if ($ttl === 0) {
            return $this->build($storefront);
        }

        return QueryCache::rememberTagged(
            $key,
            [CacheTags::HOMEPAGE, CacheTags::STOREFRONTS],
            $ttl,
            fn (): array => $this->build($storefront),
        );
    }

    private function build(string $storefront): array
    {
        $root = Category::query()
            ->where('slug', $storefront)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->firstOrFail([
                'id',
                'name',
                'slug',
                'announcement_primary_text',
                'announcement_secondary_text',
            ]);

        $campaign = StorefrontCampaign::query()
            ->forStorefront($storefront)
            ->published()
            ->with('media')
            ->byPriority()
            ->first();

        $sections = StorefrontHomepageSection::query()
            ->forStorefront($storefront)
            ->published()
            ->inDisplayOrder()
            ->get();

        return [
            'storefront' => [
                'slug' => $root->slug,
                'name' => $root->name,
                'announcement' => [
                    'primary_text' => $root->announcement_primary_text,
                    'secondary_text' => $root->announcement_secondary_text,
                ],
            ],
            'campaign' => $this->campaignData($campaign),
            'sections' => $sections
                ->map(fn (StorefrontHomepageSection $section): ?array => $this->hydrateSafely($section))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function campaignData(?StorefrontCampaign $campaign): ?array
    {
        if (! $campaign) {
            return null;
        }

        $desktopImage = $campaign->getFirstMedia('desktop-image');
        $mobileImage = $campaign->getFirstMedia('mobile-image');

        return [
            'id' => $campaign->id,
            'eyebrow' => $campaign->eyebrow,
            'title' => $campaign->title,
            'description' => $campaign->description,
            'desktop_image' => $desktopImage ? SafeMediaUrl::get($desktopImage) : '',
            'mobile_image' => $mobileImage ? SafeMediaUrl::get($mobileImage) : '',
            'cta_label' => $campaign->cta_label,
            'cta_url' => $campaign->cta_url,
            'starts_at' => $campaign->starts_at?->toISOString(),
            'ends_at' => $campaign->ends_at?->toISOString(),
        ];
    }

    private function hydrateSafely(StorefrontHomepageSection $section): ?array
    {
        $type = HomepageSectionType::tryFrom((string) $section->type);
        if (! $type) {
            Log::warning('Unsupported storefront homepage section type.', [
                'section_id' => $section->id,
                'storefront_slug' => $section->storefront_slug,
                'type' => $section->type,
            ]);

            return null;
        }

        try {
            $items = match ($type) {
                HomepageSectionType::FeaturedProducts => $this->featuredProducts($section),
                HomepageSectionType::SaleProducts => $this->saleProducts($section),
                HomepageSectionType::CategoryProducts => $this->categoryProducts($section),
                HomepageSectionType::CollectionProducts => $this->collectionProducts($section),
                HomepageSectionType::NewestProducts => $this->newestProducts($section),
                HomepageSectionType::RandomCategories => $this->randomCategories($section),
                HomepageSectionType::ManualProducts => $this->manualProducts($section),
            };
        } catch (Throwable $exception) {
            Log::warning('Unable to hydrate storefront homepage section.', [
                'section_id' => $section->id,
                'storefront_slug' => $section->storefront_slug,
                'type' => $section->type,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $items = [];
        }

        return [
            'id' => $section->id,
            'type' => $type->value,
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'display_order' => $section->display_order,
            'items' => $items,
        ];
    }

    private function featuredProducts(StorefrontHomepageSection $section): array
    {
        $config = $this->config($section);
        $query = $this->productQuery()->where('products.is_featured', true);
        $this->applySafeSort($query, $config);

        return $this->productData($query->limit($this->productLimit($config))->get());
    }

    private function saleProducts(StorefrontHomepageSection $section): array
    {
        $now = now();
        $query = $this->productQuery()
            ->where(function (Builder $builder) use ($now): void {
                $builder->where(function (Builder $simple) use ($now): void {
                    $simple->where('products.type', '!=', 'variable')
                        ->whereNotNull('products.sale_price')
                        ->whereColumn('products.sale_price', '<', 'products.price')
                        ->where('products.sale_price', '>', 0)
                        ->whereNotNull('products.sale_starts_at')
                        ->where('products.sale_starts_at', '<=', $now)
                        ->whereNotNull('products.sale_ends_at')
                        ->where('products.sale_ends_at', '>=', $now);
                })->orWhere(function (Builder $variable) use ($now): void {
                    $variable->where('products.type', 'variable')
                        ->whereHas('defaultVariant', fn (Builder $variant) => $this->validSaleVariant($variant, $now));
                });
            });
        $this->available($query);

        return $this->productData($query->orderBy('products.sort_order')->limit($this->productLimit($this->config($section)))->get());
    }

    private function categoryProducts(StorefrontHomepageSection $section): array
    {
        $config = $this->config($section);
        $slug = $this->safeSlug($config['category_slug'] ?? null);
        if (! $slug) {
            return [];
        }

        $category = $this->categories()->firstWhere('slug', $slug);
        if (! $category) {
            return [];
        }

        $query = $this->productQuery()
            ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $this->descendantIds($category->id)));
        $this->applySafeSort($query, $config);

        return $this->productData($query->limit($this->productLimit($config))->get());
    }

    private function collectionProducts(StorefrontHomepageSection $section): array
    {
        $config = $this->config($section);
        $slug = $this->safeSlug($config['collection_slug'] ?? null);
        if (! $slug) {
            return [];
        }

        $collection = ProductCollection::query()->where('slug', $slug)->where('is_active', true)->first(['id']);
        if (! $collection) {
            return [];
        }

        $products = $this->productQuery()
            ->join('collection_product', 'collection_product.product_id', '=', 'products.id')
            ->where('collection_product.collection_id', $collection->id)
            ->orderBy('collection_product.sort_order')
            ->orderBy('products.name')
            ->limit($this->productLimit($config))
            ->get();

        return $this->productData($products);
    }

    private function newestProducts(StorefrontHomepageSection $section): array
    {
        $query = $this->productQuery();
        $this->available($query);

        return $this->productData(
            $query->orderByDesc('products.created_at')->limit($this->productLimit($this->config($section)))->get()
        );
    }

    private function manualProducts(StorefrontHomepageSection $section): array
    {
        $products = $this->productQuery()
            ->join('storefront_homepage_section_product as section_product', 'section_product.product_id', '=', 'products.id')
            ->where('section_product.section_id', $section->id)
            ->orderBy('section_product.display_order')
            ->limit($this->productLimit($this->config($section)))
            ->get();

        return $this->productData($products);
    }

    private function randomCategories(StorefrontHomepageSection $section): array
    {
        $config = $this->config($section);
        $rootSlug = $this->storefrontContext->rootCategorySlug();
        $root = $this->categories()->firstWhere('slug', $rootSlug);
        if (! $root) {
            return [];
        }

        $categories = $this->categories()
            ->where('parent_id', $root->id)
            ->values();

        $productCounts = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('category_product.category_id', $this->storefrontContext->categoryIds())
            ->selectRaw('category_product.category_id, COUNT(DISTINCT products.id) as aggregate')
            ->groupBy('category_product.category_id')
            ->pluck('aggregate', 'category_product.category_id');

        $items = $categories->map(function (Category $category) use ($productCounts): array {
            $count = collect($this->descendantIds($category->id))
                ->sum(fn (string $id): int => (int) ($productCounts[$id] ?? 0));
            $data = (new CategoryResource(CategoryMapper::toDomain($category)))->resolve(request());
            $data['product_count'] = $count;

            return $data;
        });

        if (filter_var($config['require_products'] ?? false, FILTER_VALIDATE_BOOL)) {
            $items = $items
                ->where('product_count', '>', 0)
                ->shuffle()
                ->concat($items->where('product_count', '<=', 0)->shuffle());
        } else {
            $items = $items->shuffle();
        }

        return $items->take($this->categoryLimit($config))->values()->all();
    }

    private function productQuery(): Builder
    {
        return Product::query()
            ->select('products.*')
            ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $this->storefrontContext->categoryIds()))
            ->with([
                'media',
                'categories:id,name,slug',
                'primaryCategory:id,name,slug',
                'brand:id,name,slug',
                'variants',
                'variants.media',
            ]);
    }

    private function available(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->where(function (Builder $simple): void {
                $simple->where('products.type', '!=', 'variable')
                    ->where(fn (Builder $stock) => $stock
                        ->where('products.manage_stock', false)
                        ->orWhere(fn (Builder $managed) => $managed
                            ->where('products.in_stock', true)
                            ->where('products.stock_quantity', '>', 0)));
            })->orWhere(function (Builder $variable): void {
                $variable->where('products.type', 'variable')
                    ->whereHas('variants', fn (Builder $variant) => $this->availableVariant($variant));
            });
        });
    }

    private function availableVariant(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['active', 'published'])
            ->where(fn (Builder $stock) => $stock
                ->where('manage_stock', false)
                ->orWhere(fn (Builder $managed) => $managed->where('in_stock', true)->where('stock_quantity', '>', 0)));
    }

    private function validSaleVariant(Builder $query, mixed $now): Builder
    {
        return $this->availableVariant($query)
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->where('sale_price', '>', 0)
            ->whereNotNull('sale_starts_at')
            ->where('sale_starts_at', '<=', $now)
            ->whereNotNull('sale_ends_at')
            ->where('sale_ends_at', '>=', $now);
    }

    private function applySafeSort(Builder $query, array $config): void
    {
        $sort = is_string($config['sort'] ?? null) ? $config['sort'] : 'sort_order';
        $direction = strtolower(is_string($config['direction'] ?? null) ? $config['direction'] : 'asc');
        $query->orderBy(self::SORT_COLUMNS[$sort] ?? self::SORT_COLUMNS['sort_order'], $direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('products.name');
    }

    private function productData(Collection $products): array
    {
        return $products
            ->map(fn (Product $product) => ProductMapper::toDomain($product))
            ->map(fn ($product): array => (new ProductResource($product))->resolve(request()))
            ->values()
            ->all();
    }

    /** @return Collection<string, Category> */
    private function categories(): Collection
    {
        return $this->storefrontCategories ??= Category::query()
            ->whereIn('id', $this->storefrontContext->categoryIds())
            ->where('is_active', true)
            ->with('media')
            ->get(['id', 'name', 'slug', 'parent_id', 'is_active'])
            ->keyBy('id');
    }

    /** @return list<string> */
    private function descendantIds(string $categoryId): array
    {
        $ids = [$categoryId];
        $pending = [$categoryId];

        while ($pending !== []) {
            $children = $this->categories()->whereIn('parent_id', $pending)->keys()->values()->all();
            $ids = [...$ids, ...$children];
            $pending = $children;
        }

        return array_values(array_unique($ids));
    }

    private function config(StorefrontHomepageSection $section): array
    {
        return is_array($section->config) ? $section->config : [];
    }

    private function limit(array $config): int
    {
        $default = max(1, (int) config('storefront.homepage.default_item_limit', 8));
        $maximum = max(1, (int) config('storefront.homepage.max_item_limit', 50));
        $value = filter_var($config['limit'] ?? $default, FILTER_VALIDATE_INT);

        return min(max($value === false ? $default : $value, 1), $maximum);
    }

    private function productLimit(array $config): int
    {
        $maximum = max(1, (int) config('storefront.homepage.max_product_limit', 4));

        return min($this->limit($config), $maximum);
    }

    private function categoryLimit(array $config): int
    {
        $default = max(1, (int) config('storefront.homepage.default_category_limit', 6));
        $maximum = max(1, (int) config('storefront.homepage.max_category_limit', 6));
        $value = filter_var($config['limit'] ?? $default, FILTER_VALIDATE_INT);

        return min(max($value === false ? $default : $value, $default), $maximum);
    }

    private function safeSlug(mixed $slug): ?string
    {
        if (! is_string($slug) || strlen($slug) > 255 || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return null;
        }

        return $slug;
    }
}
