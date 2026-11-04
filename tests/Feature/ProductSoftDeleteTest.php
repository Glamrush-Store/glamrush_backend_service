<?php

use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('products', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('status');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

it('excludes soft-deleted products from public product queries', function () {
    DB::table('products')->insert([
        [
            'id' => '01VISIBLEPRODUCT00000000001',
            'name' => 'Visible Product',
            'slug' => 'visible-product',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ],
        [
            'id' => '01DELETEDPRODUCT00000000001',
            'name' => 'Deleted Product',
            'slug' => 'deleted-product',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ],
    ]);

    expect(Product::query()->pluck('slug')->all())->toBe(['visible-product'])
        ->and(Product::withTrashed()->count())->toBe(2);
});

it('uses fresh cache keys and product tags for catalog reads', function () {
    $list = new ListProductsQuery(
        categorySlug: null,
        brandSlug: null,
        sort: null,
        direction: 'asc',
        filters: [],
        featured: null,
    );
    $detail = new GetProductQuery('deleted-product');

    expect($list->cacheKey())->toStartWith('catalog:products:v3:')
        ->and($detail->cacheKey())->toStartWith('catalog:product:v2:')
        ->and($detail->cacheTags())->toBe(['catalog', 'products']);
});
