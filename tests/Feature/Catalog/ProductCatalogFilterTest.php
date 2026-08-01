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
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('type')->default('simple');
        $table->decimal('price', 10, 2)->default(0);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->integer('sort_order')->default(0);
        $table->string('category_id')->nullable();
        $table->string('brand_id')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
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

    DB::table('products')->insert(array_merge([
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
        'category_id' => null,
        'brand_id' => null,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'in_stock' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides));

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
