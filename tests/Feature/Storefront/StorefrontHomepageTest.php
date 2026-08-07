<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-05 12:00:00');
    config()->set('storefront.homepage.cache_ttl', 300);
    config()->set('storefront.homepage.max_item_limit', 3);
    Cache::flush();

    Schema::create('categories', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('parent_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('brands', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });
    Schema::create('vendors', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });
    Schema::create('products', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('sku')->nullable();
        $table->string('type')->default('simple');
        $table->string('status')->default('published');
        $table->timestamp('published_at')->nullable();
        $table->decimal('price', 12, 2)->default(0);
        $table->decimal('sale_price', 12, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
        $table->boolean('is_featured')->default(false);
        $table->integer('sort_order')->default(0);
        $table->string('category_id')->nullable();
        $table->string('brand_id')->nullable();
        $table->string('vendor_id')->nullable();
        $table->timestamps();
    });
    Schema::create('product_variants', function ($table) {
        $table->string('id')->primary();
        $table->string('product_id');
        $table->string('sku');
        $table->boolean('is_default')->default(false);
        $table->decimal('price', 12, 2);
        $table->decimal('sale_price', 12, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
        $table->json('attributes')->nullable();
        $table->integer('sort_order')->default(0);
        $table->string('status')->default('active');
        $table->timestamps();
    });
    Schema::create('collections', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('collection_product', function ($table) {
        $table->string('collection_id');
        $table->string('product_id');
        $table->integer('sort_order')->default(0);
    });
    Schema::create('storefront_campaigns', function ($table) {
        $table->string('id')->primary();
        $table->string('storefront_slug');
        $table->string('internal_name');
        $table->string('eyebrow')->nullable();
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('cta_label')->nullable();
        $table->string('cta_url')->nullable();
        $table->integer('priority')->default(0);
        $table->boolean('is_active')->default(false);
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();
        $table->timestamps();
    });
    Schema::create('storefront_homepage_sections', function ($table) {
        $table->string('id')->primary();
        $table->string('storefront_slug');
        $table->string('type');
        $table->string('title');
        $table->string('subtitle')->nullable();
        $table->json('config');
        $table->integer('display_order')->default(0);
        $table->boolean('is_active')->default(false);
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();
        $table->timestamps();
    });
    Schema::create('storefront_homepage_section_product', function ($table) {
        $table->string('section_id');
        $table->string('product_id');
        $table->integer('display_order');
    });
    Schema::create('media', function ($table) {
        $table->id();
        $table->string('model_type');
        $table->string('model_id');
        $table->uuid('uuid')->nullable();
        $table->string('collection_name');
        $table->string('name');
        $table->string('file_name');
        $table->string('mime_type')->nullable();
        $table->string('disk');
        $table->string('conversions_disk')->nullable();
        $table->unsignedBigInteger('size');
        $table->json('manipulations');
        $table->json('custom_properties');
        $table->json('generated_conversions');
        $table->json('responsive_images');
        $table->unsignedInteger('order_column')->nullable();
        $table->nullableTimestamps();
    });

    homepageCategory('Fragrances', 'fragrances');
});

afterEach(fn () => Carbon::setTestNow());

function homepageCategory(string $name, string $slug, ?string $parentId = null, bool $active = true): string
{
    $id = (string) Str::ulid();
    DB::table('categories')->insert(compact('id', 'name', 'slug') + [
        'parent_id' => $parentId,
        'is_active' => $active,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function homepageProduct(string $name, string $categoryId, array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('products')->insert(array_merge([
        'id' => $id,
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
        'sku' => 'SKU-'.Str::upper(Str::random(6)),
        'type' => 'simple',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'price' => 10000,
        'sale_price' => null,
        'sale_starts_at' => null,
        'sale_ends_at' => null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'is_featured' => false,
        'sort_order' => 0,
        'category_id' => $categoryId,
        'brand_id' => null,
        'vendor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

function homepageVariant(string $productId, array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('product_variants')->insert(array_merge([
        'id' => $id,
        'product_id' => $productId,
        'sku' => 'VAR-'.Str::upper(Str::random(6)),
        'is_default' => true,
        'price' => 12000,
        'sale_price' => null,
        'sale_starts_at' => null,
        'sale_ends_at' => null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'attributes' => '[]',
        'sort_order' => 0,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

function homepageCampaign(array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('storefront_campaigns')->insert(array_merge([
        'id' => $id,
        'storefront_slug' => 'fragrances',
        'internal_name' => 'Internal only',
        'eyebrow' => 'After-dark fragrances',
        'title' => 'Leave a trace.',
        'description' => 'A magnetic collection for after dark.',
        'cta_label' => 'Shop the campaign',
        'cta_url' => '/collections/midnight-edit',
        'priority' => 1,
        'is_active' => true,
        'starts_at' => null,
        'ends_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

function homepageSection(string $type, array $config = [], array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('storefront_homepage_sections')->insert(array_merge([
        'id' => $id,
        'storefront_slug' => 'fragrances',
        'type' => $type,
        'title' => Str::headline($type),
        'subtitle' => null,
        'config' => json_encode($config),
        'display_order' => 1,
        'is_active' => true,
        'starts_at' => null,
        'ends_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

it('returns a fully shaped homepage and handles empty content', function () {
    $response = $this->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'storefront' => ['slug' => 'fragrances', 'name' => 'Fragrances'],
                'campaign' => null,
                'sections' => [],
            ],
        ]);

    expect($response->headers->get('Cache-Control'))->toContain('public', 'max-age=60', 's-maxage=300')
        ->and($response->headers->get('ETag'))->not->toBeNull()
        ->and(Cache::tags(['storefronts', 'categories'])->has('storefront:context:fragrances:v1'))->toBeTrue();

    $this->withHeader('If-None-Match', $response->headers->get('ETag'))
        ->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertStatus(304);

    $this->getJson('/api/v1/storefronts/unknown/homepage')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('selects only the highest priority currently published campaign', function () {
    homepageCampaign(['title' => 'Lower', 'priority' => 10]);
    $winner = homepageCampaign(['title' => 'Winner', 'priority' => 20]);
    homepageCampaign(['title' => 'Future', 'priority' => 100, 'starts_at' => now()->addMinute()]);
    homepageCampaign(['title' => 'Expired', 'priority' => 100, 'ends_at' => now()->subMinute()]);
    homepageCampaign(['title' => 'Disabled', 'priority' => 100, 'is_active' => false]);

    $this->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertOk()
        ->assertJsonPath('data.campaign.id', $winner)
        ->assertJsonPath('data.campaign.title', 'Winner')
        ->assertJsonMissingPath('data.campaign.internal_name');
});

it('orders sections and excludes future expired and disabled sections', function () {
    homepageSection('newest_products', [], ['title' => 'Second', 'display_order' => 2]);
    homepageSection('featured_products', [], ['title' => 'First', 'display_order' => 1]);
    homepageSection('sale_products', [], ['starts_at' => now()->addMinute()]);
    homepageSection('sale_products', [], ['ends_at' => now()->subMinute()]);
    homepageSection('sale_products', [], ['is_active' => false]);

    $this->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertOk()
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonPath('data.sections.0.title', 'First')
        ->assertJsonPath('data.sections.1.title', 'Second');
});

it('hydrates featured category collection newest and manual product sections', function () {
    $root = DB::table('categories')->where('slug', 'fragrances')->value('id');
    $child = homepageCategory('Perfume Oils', 'perfume-oils', $root);
    $featured = homepageProduct('Featured', $child, ['is_featured' => true, 'sort_order' => 2]);
    $newest = homepageProduct('Newest', $child, ['created_at' => now()->addMinute()]);
    $outside = homepageCategory('Hair', 'hair');
    homepageProduct('Outside', $outside, ['is_featured' => true]);

    $collectionId = (string) Str::ulid();
    DB::table('collections')->insert(['id' => $collectionId, 'name' => 'Midnight', 'slug' => 'midnight', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('collection_product')->insert([
        ['collection_id' => $collectionId, 'product_id' => $newest, 'sort_order' => 1],
        ['collection_id' => $collectionId, 'product_id' => $featured, 'sort_order' => 2],
    ]);

    homepageSection('featured_products', ['sort' => 'name', 'direction' => 'asc'], ['display_order' => 1]);
    homepageSection('category_products', ['category_slug' => 'perfume-oils'], ['display_order' => 2]);
    homepageSection('collection_products', ['collection_slug' => 'midnight'], ['display_order' => 3]);
    homepageSection('newest_products', ['limit' => 1], ['display_order' => 4]);
    $manual = homepageSection('manual_products', ['limit' => 3], ['display_order' => 5]);
    DB::table('storefront_homepage_section_product')->insert([
        ['section_id' => $manual, 'product_id' => $newest, 'display_order' => 1],
        ['section_id' => $manual, 'product_id' => 'missing-product', 'display_order' => 2],
        ['section_id' => $manual, 'product_id' => $featured, 'display_order' => 3],
    ]);

    $response = $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk();
    expect($response->json('data.sections.0.items.*.name'))->toBe(['Featured']);
    expect($response->json('data.sections.1.items.*.name'))->toContain('Featured', 'Newest');
    expect($response->json('data.sections.2.items.*.name'))->toBe(['Newest', 'Featured']);
    expect($response->json('data.sections.3.items.*.name'))->toBe(['Newest']);
    expect($response->json('data.sections.4.items.*.name'))->toBe(['Newest', 'Featured']);
});

it('returns only valid available sales with simple and default variant prices', function () {
    $root = DB::table('categories')->where('slug', 'fragrances')->value('id');
    $simple = homepageProduct('Simple Sale', $root, [
        'price' => 10000,
        'sale_price' => 8000,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);
    $variable = homepageProduct('Variable Sale', $root, ['type' => 'variable', 'price' => 99999]);
    homepageVariant($variable, ['price' => 12000, 'sale_price' => 9000, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addDay()]);
    homepageProduct('Invalid Sale', $root, ['price' => 10000, 'sale_price' => 11000, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addDay()]);
    homepageProduct('Unavailable Sale', $root, ['price' => 10000, 'sale_price' => 8000, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addDay(), 'manage_stock' => true, 'stock_quantity' => 0]);
    homepageSection('sale_products');

    $items = $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk()->json('data.sections.0.items');
    expect(array_column($items, 'id'))->toContain($simple, $variable)->toHaveCount(2);
    expect(collect($items)->firstWhere('id', $simple))->toMatchArray(['price' => 10000, 'salePrice' => 8000, 'currentPrice' => 8000, 'isOnSale' => true]);
    expect(collect($items)->firstWhere('id', $variable))->toMatchArray(['price' => 12000, 'salePrice' => 9000, 'currentPrice' => 9000, 'isOnSale' => true]);
});

it('returns cached stable random categories with product counts and storefront isolation', function () {
    $fragrances = DB::table('categories')->where('slug', 'fragrances')->value('id');
    foreach (['Oils', 'Sprays', 'Mists', 'Candles'] as $name) {
        $category = homepageCategory($name, Str::slug($name), $fragrances);
        homepageProduct("{$name} Product", $category);
    }
    homepageSection('random_categories', ['limit' => 99, 'require_products' => true]);

    $first = $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk()->json('data.sections.0.items');
    DB::table('storefront_homepage_sections')->where('storefront_slug', 'fragrances')->delete();
    $second = $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk()->json('data.sections.0.items');
    expect($second)->toBe($first)->and($first)->toHaveCount(3);
    expect($first[0]['product_count'])->toBe(1);

    homepageCategory('Beauty', 'beauty');
    $this->getJson('/api/v1/storefronts/beauty/homepage')->assertOk()->assertJsonCount(0, 'data.sections');
});

it('contains malformed known sections and omits unsupported types without failing the homepage', function () {
    homepageSection('category_products', ['category_slug' => '../unsafe']);
    homepageSection('collection_products', ['collection_slug' => 'missing'], ['display_order' => 2]);
    homepageSection('arbitrary_php_class', [], ['display_order' => 3]);

    $this->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertOk()
        ->assertJsonCount(2, 'data.sections')
        ->assertJsonCount(0, 'data.sections.0.items')
        ->assertJsonCount(0, 'data.sections.1.items');
});

it('enforces item limits and sort allowlists', function () {
    $root = DB::table('categories')->where('slug', 'fragrances')->value('id');
    foreach (['Zulu', 'Alpha', 'Mike', 'Bravo'] as $index => $name) {
        homepageProduct($name, $root, ['is_featured' => true, 'sort_order' => $index]);
    }
    homepageSection('featured_products', ['limit' => 999, 'sort' => 'id; DROP TABLE products', 'direction' => 'sideways']);

    $items = $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk()->json('data.sections.0.items');
    expect($items)->toHaveCount(3)
        ->and(array_column($items, 'name'))->toBe(['Zulu', 'Alpha', 'Mike'])
        ->and(Schema::hasTable('products'))->toBeTrue();
});

it('limits every homepage product section to four products', function () {
    config()->set('storefront.homepage.max_item_limit', 20);
    config()->set('storefront.homepage.max_product_limit', 4);
    $root = DB::table('categories')->where('slug', 'fragrances')->value('id');

    foreach (range(1, 6) as $number) {
        homepageProduct("Featured {$number}", $root, ['is_featured' => true]);
    }

    homepageSection('featured_products', ['limit' => 20]);

    $this->getJson('/api/v1/storefronts/fragrances/homepage')
        ->assertOk()
        ->assertJsonCount(4, 'data.sections.0.items');
});

it('hydrates products without per-product query growth', function () {
    config()->set('storefront.homepage.cache_ttl', 0);
    config()->set('storefront.homepage.max_item_limit', 20);
    $root = DB::table('categories')->where('slug', 'fragrances')->value('id');
    homepageSection('featured_products', ['limit' => 20]);
    homepageProduct('One', $root, ['is_featured' => true]);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk();
    $single = $queries;

    foreach (range(2, 10) as $number) {
        homepageProduct("Product {$number}", $root, ['is_featured' => true]);
    }
    $queries = 0;
    $this->getJson('/api/v1/storefronts/fragrances/homepage')->assertOk();

    expect($queries)->toBeLessThanOrEqual($single + 1);
});
