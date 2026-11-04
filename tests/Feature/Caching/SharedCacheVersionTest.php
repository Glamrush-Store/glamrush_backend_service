<?php

use App\Infrastructure\Caching\CacheInvalidationObserver;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\CacheVersionManager;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('cache_versions.check_ttl_seconds', 10);
});

it('uses shared database versions in tagged cache keys', function () {
    $source = 'first';
    $calls = 0;
    $read = function () use (&$source, &$calls): string {
        return QueryCache::rememberTagged(
            'catalog:test-product',
            [CacheTags::CATALOG, CacheTags::PRODUCTS],
            300,
            function () use (&$source, &$calls): string {
                $calls++;

                return $source;
            },
        );
    };

    expect($read())->toBe('first');

    $source = 'second';
    DB::table('cache_versions')->where('namespace', 'catalog')->increment('version');

    // The storefront's own version snapshot deliberately bounds database
    // reads, so an admin change becomes visible after its short TTL.
    expect($read())->toBe('first');

    $this->travel(11)->seconds();

    expect($read())->toBe('second')
        ->and($calls)->toBe(2);
});

it('makes local version bumps visible immediately', function () {
    $source = 'first';
    $read = function () use (&$source): string {
        return QueryCache::rememberTagged(
            'catalog:immediate',
            [CacheTags::CATALOG],
            300,
            fn (): string => $source,
        );
    };

    expect($read())->toBe('first');

    $source = 'second';
    app(CacheVersionManager::class)->bumpForTags([CacheTags::PRODUCTS]);

    expect($read())->toBe('second')
        ->and(DB::table('cache_versions')->where('namespace', 'catalog')->value('version'))->toBe(2);
});

it('maps related cache tags to a small set of shared namespaces', function () {
    $manager = app(CacheVersionManager::class);

    expect($manager->namespacesForTags([
        CacheTags::PRODUCTS,
        CacheTags::CATEGORIES,
        CacheTags::HOMEPAGE,
        CacheTags::STOREFRONTS,
    ]))->toBe(['catalog', 'homepage'])
        ->and($manager->namespacesForTags([CacheTags::PAYMENT_METHODS, CacheTags::SHIPPING]))
        ->toBe(['payment-methods', 'shipping']);
});

it('seeds every shared cache namespace', function () {
    expect(DB::table('cache_versions')->orderBy('namespace')->pluck('version', 'namespace')->all())
        ->toBe([
            'catalog' => 1,
            'homepage' => 1,
            'payment-methods' => 1,
            'shipping' => 1,
        ]);
});

it('bumps shared versions when a local observed model changes', function () {
    (new CacheInvalidationObserver)->updated(new Product);

    expect(DB::table('cache_versions')->where('namespace', 'catalog')->value('version'))->toBe(2)
        ->and(DB::table('cache_versions')->where('namespace', 'homepage')->value('version'))->toBe(2)
        ->and(DB::table('cache_versions')->where('namespace', 'shipping')->value('version'))->toBe(1);
});
