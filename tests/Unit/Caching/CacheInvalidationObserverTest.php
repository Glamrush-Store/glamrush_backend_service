<?php

use App\Infrastructure\Caching\CacheInvalidationObserver;
use App\Infrastructure\Caching\CacheTags;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(fn () => Cache::flush());

it('is configured to run model invalidation after committed transactions', function () {
    expect(new CacheInvalidationObserver)->toBeInstanceOf(ShouldHandleEventsAfterCommit::class);
});

it('keeps cached data until the database transaction commits', function () {
    Schema::create('payment_methods', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('code');
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->json('public_config')->nullable();
        $table->timestamps();
    });

    Cache::tags([CacheTags::PAYMENT_METHODS])->put('test:payment-methods', 'cached', 60);

    DB::beginTransaction();
    PaymentMethod::query()->create([
        'name' => 'Paystack',
        'code' => 'paystack',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect(Cache::tags([CacheTags::PAYMENT_METHODS])->get('test:payment-methods'))->toBe('cached');

    DB::commit();

    expect(Cache::tags([CacheTags::PAYMENT_METHODS])->get('test:payment-methods'))->toBeNull();
});

it('invalidates every cache family affected by model changes', function () {
    $productMedia = (new Media)->forceFill(['model_type' => 'product']);
    $categoryMedia = (new Media)->forceFill(['model_type' => 'category']);
    $campaignMedia = (new Media)->forceFill(['model_type' => 'storefront_campaign']);

    $cases = [
        [new Product, [CacheTags::CATALOG, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new ProductVariant, [CacheTags::CATALOG, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new ProductAttribute, [CacheTags::CATALOG, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new AttributeType, [CacheTags::CATALOG, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new Category, [CacheTags::CATEGORIES, CacheTags::PRODUCTS, CacheTags::HOMEPAGE, CacheTags::STOREFRONTS]],
        [new Brand, [CacheTags::BRANDS, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new ProductCollection, [CacheTags::COLLECTIONS, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new CollectionProduct, [CacheTags::COLLECTIONS, CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [new StorefrontCampaign, [CacheTags::HOMEPAGE]],
        [new StorefrontHomepageSection, [CacheTags::HOMEPAGE]],
        [new StorefrontHomepageSectionProduct, [CacheTags::HOMEPAGE]],
        [$productMedia, [CacheTags::PRODUCTS, CacheTags::HOMEPAGE]],
        [$categoryMedia, [CacheTags::CATEGORIES, CacheTags::HOMEPAGE, CacheTags::STOREFRONTS]],
        [$campaignMedia, [CacheTags::HOMEPAGE]],
        [new PaymentMethod, [CacheTags::PAYMENT_METHODS]],
        [new ShippingMethod, [CacheTags::SHIPPING]],
        [new ShippingRate, [CacheTags::SHIPPING]],
        [new ShippingZone, [CacheTags::SHIPPING]],
    ];

    $observer = new CacheInvalidationObserver;

    foreach ($cases as [$model, $tags]) {
        Cache::flush();

        foreach ($tags as $tag) {
            Cache::tags([$tag])->put("test:{$tag}", 'cached', 60);
        }
        Cache::tags(['unrelated'])->put('test:unrelated', 'cached', 60);

        $observer->updated($model);

        foreach ($tags as $tag) {
            expect(Cache::tags([$tag])->get("test:{$tag}"))->toBeNull();
        }
        expect(Cache::tags(['unrelated'])->get('test:unrelated'))->toBe('cached');
    }
});
