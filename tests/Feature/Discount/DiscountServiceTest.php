<?php

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Discount\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('shipping_rates')) {
        Schema::create('shipping_rates', function ($table) {
            $table->string('id')->primary();
        });
    }
    Schema::create('categories', function ($table) {
        $table->ulid('id')->primary();
        $table->ulid('parent_id')->nullable();
        $table->string('name');
        $table->string('slug');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('discount_codes', function ($table) {
        $table->ulid('id')->primary();
        $table->string('code')->unique();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('type');
        $table->decimal('value', 15, 2)->nullable();
        $table->char('currency', 3)->nullable();
        $table->decimal('maximum_discount_amount', 15, 2)->nullable();
        $table->decimal('minimum_subtotal', 15, 2)->nullable();
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('total_usage_limit')->nullable();
        $table->integer('per_customer_usage_limit')->nullable();
        $table->boolean('first_order_only')->default(false);
        $table->boolean('applies_to_sale_items')->default(false);
        $table->boolean('applies_to_all_storefronts')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('discount_code_storefronts', function ($table) {
        $table->ulid('discount_code_id');
        $table->ulid('category_id');
        $table->timestamps();
    });
    Schema::create('discount_code_targets', function ($table) {
        $table->ulid('id')->primary();
        $table->ulid('discount_code_id');
        $table->string('target_type');
        $table->ulid('target_id');
        $table->string('mode');
        $table->timestamps();
    });
});

it('calculates capped percentage discounts and allocates them only to eligible lines', function () {
    [$service, $root] = discountService();
    $discountId = insertDiscount([
        'code' => 'SCENT20',
        'type' => 'percentage',
        'value' => 20,
        'maximum_discount_amount' => 1500,
        'applies_to_all_storefronts' => false,
    ]);
    DB::table('discount_code_storefronts')->insert(['discount_code_id' => $discountId, 'category_id' => $root, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('discount_code_targets')->insert([
        'id' => (string) Str::ulid(), 'discount_code_id' => $discountId, 'target_type' => 'brand',
        'target_id' => '01BRAND0000000000000000001', 'mode' => 'include', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $quote = $service->calculate('scent20', [
        discountLine(10000, brand: '01BRAND0000000000000000001'),
        discountLine(5000, brand: '01BRAND0000000000000000002'),
    ], 2000, null, 'guest-one', 'shopper@example.com', false);

    expect($quote['discount_amount'])->toBe(1500.0)
        ->and($quote['total'])->toBe(15500.0)
        ->and($quote['line_discounts'])->toBe([1500.0, 0.0]);
});

it('counts active reservations when enforcing the total usage limit', function () {
    [$service] = discountService();
    $discountId = insertDiscount(['code' => 'ONLYONE', 'type' => 'fixed_amount', 'value' => 1000, 'currency' => 'NGN', 'total_usage_limit' => 1]);
    $orderId = (string) Str::ulid();
    DB::table('orders')->insert([
        'id' => $orderId, 'order_number' => 'GR-TEST-DISCOUNT', 'status' => 'pending_payment',
        'subtotal' => 5000, 'discount_amount' => 1000, 'shipping_amount' => 0, 'shipping_discount_amount' => 0,
        'total' => 4000, 'currency' => 'NGN', 'shipping_method_name' => 'Test', 'shipping_zone_name' => 'Test',
        'shipping_address' => '{}', 'placed_at' => now(), 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('discount_redemptions')->insert([
        'id' => (string) Str::ulid(), 'discount_code_id' => $discountId, 'order_id' => $orderId,
        'customer_key' => 'email:used', 'code' => 'ONLYONE', 'type' => 'fixed_amount', 'discount_amount' => 1000,
        'shipping_discount_amount' => 0, 'currency' => 'NGN', 'status' => 'reserved', 'snapshot' => '{}',
        'reserved_at' => now(), 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(fn () => $service->calculate('ONLYONE', [discountLine(5000)], 0, null, 'guest-two', 'new@example.com', false))
        ->toThrow(RuntimeException::class, 'usage limit');
});

it('matches category discount targets against every product category and its ancestors', function () {
    [$service, $root] = discountService();
    $child = (string) Str::ulid();
    DB::table('categories')->insert([
        'id' => $child,
        'parent_id' => $root,
        'name' => 'Perfume Oils',
        'slug' => 'perfume-oils',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $discountId = insertDiscount(['code' => 'CATEGORY10']);
    DB::table('discount_code_targets')->insert([
        'id' => (string) Str::ulid(),
        'discount_code_id' => $discountId,
        'target_type' => 'category',
        'target_id' => $root,
        'mode' => 'include',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $line = discountLine(10000);
    $line['category_id'] = null;
    $line['category_ids'] = [$child];

    expect($service->calculate('CATEGORY10', [$line], 0, null, 'guest-category', null, false)['discount_amount'])
        ->toBe(1000.0);
});

function discountService(): array
{
    $root = (string) Str::ulid();
    DB::table('categories')->insert(['id' => $root, 'name' => 'Fragrances', 'slug' => 'fragrances', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    $context = app(StorefrontContext::class);
    $context->activate('fragrances', [$root]);

    return [app(DiscountService::class), $root];
}

function insertDiscount(array $overrides): string
{
    $id = (string) Str::ulid();
    DB::table('discount_codes')->insert(array_merge([
        'id' => $id, 'code' => 'WELCOME10', 'name' => 'Welcome offer', 'type' => 'percentage', 'value' => 10,
        'is_active' => true, 'first_order_only' => false, 'applies_to_sale_items' => true,
        'applies_to_all_storefronts' => true, 'created_at' => now(), 'updated_at' => now(),
    ], $overrides));

    return $id;
}

function discountLine(float $subtotal, string $brand = '01BRAND0000000000000000001'): array
{
    return [
        'product_id' => (string) Str::ulid(), 'variant_id' => (string) Str::ulid(),
        'category_id' => (string) Str::ulid(), 'brand_id' => $brand, 'collection_ids' => [],
        'quantity' => 1, 'line_subtotal' => $subtotal, 'is_on_sale' => false,
    ];
}
