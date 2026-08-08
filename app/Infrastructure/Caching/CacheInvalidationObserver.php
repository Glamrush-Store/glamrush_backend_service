<?php

namespace App\Infrastructure\Caching;

use App\Infrastructure\Persistence\Eloquent\Models\AttributeType;
use App\Infrastructure\Persistence\Eloquent\Models\Brand;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\CollectionProduct;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductAttribute;
use App\Infrastructure\Persistence\Eloquent\Models\ProductCollection;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingMethod;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingRate;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;
use App\Infrastructure\Persistence\Eloquent\Models\StorefrontCampaign;
use App\Infrastructure\Persistence\Eloquent\Models\StorefrontHomepageSection;
use App\Infrastructure\Persistence\Eloquent\Models\StorefrontHomepageSectionProduct;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CacheInvalidationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->invalidate($model);
    }

    public function updated(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    public function restored(Model $model): void
    {
        $this->invalidate($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $tags = match (true) {
            $model instanceof Product,
            $model instanceof ProductVariant,
            $model instanceof ProductAttribute,
            $model instanceof AttributeType => [
                CacheTags::CATALOG,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
            ],
            $model instanceof Category => [
                CacheTags::CATALOG,
                CacheTags::CATEGORIES,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
                CacheTags::STOREFRONTS,
            ],
            $model instanceof Brand => [
                CacheTags::CATALOG,
                CacheTags::BRANDS,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
            ],
            $model instanceof ProductCollection => [
                CacheTags::CATALOG,
                CacheTags::COLLECTIONS,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
            ],
            $model instanceof CollectionProduct => [
                CacheTags::CATALOG,
                CacheTags::COLLECTIONS,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
            ],
            $model instanceof StorefrontCampaign,
            $model instanceof StorefrontHomepageSection => [CacheTags::HOMEPAGE],
            $model instanceof StorefrontHomepageSectionProduct => [CacheTags::HOMEPAGE],
            $model instanceof PaymentMethod => [CacheTags::PAYMENT_METHODS],
            $model instanceof ShippingMethod,
            $model instanceof ShippingRate,
            $model instanceof ShippingZone => [CacheTags::SHIPPING],
            $model instanceof Media => $this->mediaTags($model),
            default => [],
        };

        if ($tags !== []) {
            $tags = array_values(array_unique($tags));
            app(CacheVersionManager::class)->bumpForTags($tags);
            QueryCache::forget($tags);
        }
    }

    private function mediaTags(Media $media): array
    {
        return match ($media->model_type) {
            'product', Product::class,
            'product_variant', ProductVariant::class => [
                CacheTags::CATALOG,
                CacheTags::PRODUCTS,
                CacheTags::HOMEPAGE,
            ],
            'category', Category::class => [
                CacheTags::CATALOG,
                CacheTags::CATEGORIES,
                CacheTags::HOMEPAGE,
                CacheTags::STOREFRONTS,
            ],
            'storefront_campaign', StorefrontCampaign::class => [CacheTags::HOMEPAGE],
            default => [],
        };
    }
}
