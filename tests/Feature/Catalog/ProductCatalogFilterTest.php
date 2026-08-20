<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

    Schema::create('products', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('sku')->nullable();
        $table->text('short_description')->nullable();
        $table->text('description')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('type')->default('simple');
        $table->decimal('price', 10, 2)->default(0);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->integer('sort_order')->default(0);
        $table->string('brand_id')->nullable();
        $table->string('vendor_id')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
        $table->string('meta_title')->nullable();
        $table->string('meta_description')->nullable();
        $table->timestamps();
    });

    Schema::create('product_variants', function ($table) {
        $table->string('id')->primary();
        $table->string('product_id');
        $table->string('sku');
        $table->boolean('is_default')->default(false);
        $table->decimal('price', 10, 2)->default(0);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
        $table->json('attributes')->nullable();
        $table->integer('sort_order')->default(0);
        $table->string('status')->default('published');
        $table->timestamps();
    });

    Schema::create('brands', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('categories', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('parent_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('category_product', function ($table) {
        $table->string('id')->primary();
        $table->string('product_id');
        $table->string('category_id');
        $table->boolean('is_primary')->default(false);
        $table->unsignedInteger('sequence')->default(0);
        $table->timestamps();

        $table->unique(['product_id', 'category_id']);
    });

    Schema::create('media', function ($table) {
        $table->id();
        $table->morphs('model');
        $table->uuid()->nullable()->unique();
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
        $table->unsignedInteger('order_column')->nullable()->index();
        $table->nullableTimestamps();
    });
});

afterEach(function () {
    Carbon::setTestNow();
});

function catalogFilterProduct(array $overrides = []): string
{
    $id = (string) Str::ulid();
    $name = $overrides['name'] ?? 'Catalog Product';
    $now = now();

    $productData = array_merge([
        'id' => $id,
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
        'sku' => null,
        'status' => 'published',
        'published_at' => $now->copy()->subDay(),
        'type' => 'simple',
        'price' => 10000,
        'sale_price' => null,
        'sale_starts_at' => null,
        'sale_ends_at' => null,
        'is_featured' => false,
        'sort_order' => 0,
        'brand_id' => null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides);
    unset($productData['category_id']);
    DB::table('products')->insert($productData);

    $categoryId = $overrides['category_id'] ?? null;
    if ($categoryId) {
        DB::table('category_product')->insert([
            'id' => (string) Str::ulid(),
            'product_id' => $id,
            'category_id' => $categoryId,
            'is_primary' => true,
            'sequence' => (int) ($overrides['sort_order'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    DB::table('product_variants')->insert([
        'id' => (string) Str::ulid(),
        'product_id' => $id,
        'sku' => 'SKU-'.Str::upper(Str::random(6)),
        'is_default' => true,
        'price' => $overrides['price'] ?? 10000,
        'sale_price' => $overrides['sale_price'] ?? null,
        'sale_starts_at' => $overrides['sale_starts_at'] ?? null,
        'sale_ends_at' => $overrides['sale_ends_at'] ?? null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'attributes' => json_encode([]),
        'sort_order' => 0,
        'status' => 'published',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $id;
}

it('filters catalog products currently on sale', function () {
    catalogFilterProduct([
        'name' => 'Active Sale',
        'sale_price' => 8000,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);
    catalogFilterProduct(['name' => 'Regular Price']);
    catalogFilterProduct([
        'name' => 'Expired Sale',
        'sale_price' => 8000,
        'sale_starts_at' => now()->subDays(3),
        'sale_ends_at' => now()->subDay(),
    ]);

    $this->getJson('/api/v1/products?onSale=true')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Sale')
        ->assertJsonPath('data.0.isOnSale', true);
});

it('returns price facet bounds from the lowest to highest displayed product price', function () {
    catalogFilterProduct([
        'name' => 'Lowest Active Sale',
        'price' => 12000,
        'sale_price' => 7000,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);

    $variableProductId = catalogFilterProduct([
        'name' => 'Variable Product',
        'type' => 'variable',
        'price' => 99999,
    ]);

    DB::table('product_variants')
        ->where('product_id', $variableProductId)
        ->where('is_default', true)
        ->update(['price' => 25000]);

    DB::table('product_variants')->insert([
        'id' => (string) Str::ulid(),
        'product_id' => $variableProductId,
        'sku' => 'NON-DEFAULT-LOW',
        'is_default' => false,
        'price' => 100,
        'sale_price' => null,
        'sale_starts_at' => null,
        'sale_ends_at' => null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'attributes' => json_encode([]),
        'sort_order' => 1,
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    catalogFilterProduct([
        'name' => 'Highest Regular Price',
        'price' => 30000,
    ]);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonPath('facets.price_range.min', 7000)
        ->assertJsonPath('facets.price_range.max', 30000);
});

it('filters catalog products not currently on sale', function () {
    catalogFilterProduct([
        'name' => 'Active Sale',
        'sale_price' => 8000,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);
    catalogFilterProduct(['name' => 'Regular Price']);
    catalogFilterProduct([
        'name' => 'Future Sale',
        'sale_price' => 8000,
        'sale_starts_at' => now()->addDay(),
        'sale_ends_at' => now()->addDays(3),
    ]);

    $response = $this->getJson('/api/v1/products?onSale=false')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($response->json('data.*.name'))
        ->toContain('Regular Price', 'Future Sale')
        ->not->toContain('Active Sale');
});

it('scopes a storefront catalog to its active root category tree', function () {
    $rootId = (string) Str::ulid();
    $childId = (string) Str::ulid();
    $inactiveChildId = (string) Str::ulid();
    $otherRootId = (string) Str::ulid();

    DB::table('categories')->insert([
        [
            'id' => $rootId,
            'name' => 'Hair',
            'slug' => 'hair',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $childId,
            'name' => 'Wigs',
            'slug' => 'wigs',
            'parent_id' => $rootId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $inactiveChildId,
            'name' => 'Hidden Hair',
            'slug' => 'hidden-hair',
            'parent_id' => $rootId,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $otherRootId,
            'name' => 'Beauty',
            'slug' => 'beauty',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    catalogFilterProduct(['name' => 'Root Product', 'category_id' => $rootId]);
    catalogFilterProduct(['name' => 'Child Product', 'category_id' => $childId]);
    catalogFilterProduct(['name' => 'Hidden Product', 'category_id' => $inactiveChildId]);
    catalogFilterProduct(['name' => 'Other Product', 'category_id' => $otherRootId]);

    $response = $this->getJson('/api/v1/storefronts/hair/products')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($response->json('data.*.name'))
        ->toContain('Root Product', 'Child Product')
        ->not->toContain('Hidden Product', 'Other Product');

    $this->getJson('/api/v1/storefronts/hair/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'hair')
        ->assertJsonCount(1, 'data.0.children')
        ->assertJsonPath('data.0.children.0.slug', 'wigs');
});

it('returns 404 for a product outside the storefront category tree', function () {
    $rootId = (string) Str::ulid();
    $otherRootId = (string) Str::ulid();

    DB::table('categories')->insert([
        [
            'id' => $rootId,
            'name' => 'Hair',
            'slug' => 'hair',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $otherRootId,
            'name' => 'Beauty',
            'slug' => 'beauty',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    catalogFilterProduct([
        'name' => 'Beauty Product',
        'slug' => 'beauty-product',
        'category_id' => $otherRootId,
    ]);

    $this->getJson('/api/v1/storefronts/hair/products/beauty-product')
        ->assertNotFound();
});

it('returns a product detail from inside the storefront category tree', function () {
    $rootId = (string) Str::ulid();
    $childId = (string) Str::ulid();

    DB::table('categories')->insert([
        [
            'id' => $rootId,
            'name' => 'Fragrances',
            'slug' => 'fragrances',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $childId,
            'name' => 'Perfumes',
            'slug' => 'perfumes',
            'parent_id' => $rootId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    catalogFilterProduct([
        'name' => 'Midnight Perfume',
        'slug' => 'midnight-perfume',
        'category_id' => $childId,
        'short_description' => 'Warm amber and soft woods.',
        'description' => 'A deep evening fragrance with amber, spice, and a lingering woody finish.',
    ]);

    $this->getJson('/api/v1/storefronts/fragrances/products/midnight-perfume')
        ->assertOk()
        ->assertJsonPath('data.slug', 'midnight-perfume')
        ->assertJsonPath('data.category.slug', 'perfumes')
        ->assertJsonPath('data.shortDescription', 'Warm amber and soft woods.')
        ->assertJsonPath('data.description', 'A deep evening fragrance with amber, spice, and a lingering woody finish.');
});

it('reads multiple categories and the primary category from category_product', function () {
    $rootId = (string) Str::ulid();
    $perfumesId = (string) Str::ulid();
    $oilsId = (string) Str::ulid();

    DB::table('categories')->insert([
        ['id' => $rootId, 'name' => 'Fragrances', 'slug' => 'fragrances', 'parent_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => $perfumesId, 'name' => 'Perfumes', 'slug' => 'perfumes', 'parent_id' => $rootId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => $oilsId, 'name' => 'Perfume Oils', 'slug' => 'perfume-oils', 'parent_id' => $rootId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $productId = catalogFilterProduct([
        'name' => 'Layered Amber',
        'slug' => 'layered-amber',
        'category_id' => $perfumesId,
    ]);

    DB::table('category_product')->insert([
        'id' => (string) Str::ulid(),
        'product_id' => $productId,
        'category_id' => $oilsId,
        'is_primary' => false,
        'sequence' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/products?category=fragrances')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.primary_category.slug', 'perfumes')
        ->assertJsonCount(2, 'data.0.categories');

    expect(collect($response->json('facets.categories'))->pluck('slug')->all())
        ->toContain('perfumes', 'perfume-oils');
});

it('searches storefront products case-insensitively using multiple words', function () {
    $rootId = (string) Str::ulid();
    $childId = (string) Str::ulid();

    DB::table('categories')->insert([
        [
            'id' => $rootId,
            'name' => 'Fragrances',
            'slug' => 'fragrances',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $childId,
            'name' => 'Perfume Oils',
            'slug' => 'perfume-oils',
            'parent_id' => $rootId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    catalogFilterProduct(['name' => 'Midnight Amber Perfume Oil', 'category_id' => $childId]);
    catalogFilterProduct(['name' => 'Midnight Musk Perfume Oil', 'category_id' => $childId]);

    $this->getJson('/api/v1/storefronts/fragrances/products?category=fragrances&search=midnight+amber')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Midnight Amber Perfume Oil');
});

it('returns 404 for an unknown or non-root storefront', function () {
    $rootId = (string) Str::ulid();

    DB::table('categories')->insert([
        'id' => (string) Str::ulid(),
        'name' => 'Wigs',
        'slug' => 'wigs',
        'parent_id' => $rootId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson('/api/v1/storefronts/wigs/products')->assertNotFound();
    $this->getJson('/api/v1/storefronts/missing/products')->assertNotFound();
});
