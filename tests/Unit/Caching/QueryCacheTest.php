<?php

use App\Domain\Catalog\Product\Queries\ListFacetsQuery;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('caches tagged values and only executes the callback once', function () {
    $calls = 0;

    $first = QueryCache::rememberTagged('test:value', [CacheTags::CATALOG], 60, function () use (&$calls) {
        $calls++;

        return ['value' => 'cached'];
    });
    $second = QueryCache::rememberTagged('test:value', [CacheTags::CATALOG], 60, function () use (&$calls) {
        $calls++;

        return ['value' => 'fresh'];
    });

    expect($first)->toBe(['value' => 'cached'])
        ->and($second)->toBe($first)
        ->and($calls)->toBe(1);
});

it('never swallows or retries callback exceptions', function () {
    $calls = 0;

    $operation = function () use (&$calls) {
        return QueryCache::rememberTagged(
            'test:exception',
            [CacheTags::CATALOG],
            60,
            function () use (&$calls): never {
                $calls++;

                throw new \RuntimeException('database failed');
            },
        );
    };

    expect($operation)->toThrow(\RuntimeException::class, 'database failed');

    expect($calls)->toBe(1);
});

it('includes collection and attribute filters in facet cache keys', function () {
    $base = new ListProductsQuery(null, null, storefrontRootSlug: 'fragrances');
    $collection = new ListProductsQuery(null, null, collectionSlug: 'summer', storefrontRootSlug: 'fragrances');
    $filtered = new ListProductsQuery(
        null,
        null,
        filters: ['attributes' => ['$has' => ['type' => 'size', 'value' => '50ml']]],
        storefrontRootSlug: 'fragrances',
    );

    expect((new ListFacetsQuery($base))->cacheKey())
        ->not->toBe((new ListFacetsQuery($collection))->cacheKey())
        ->not->toBe((new ListFacetsQuery($filtered))->cacheKey())
        ->and((new ListFacetsQuery($base))->cacheTags())
        ->toBe([CacheTags::CATALOG, CacheTags::PRODUCTS]);
});
