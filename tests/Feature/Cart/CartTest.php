<?php

use App\Domain\Order\Events\OrderPlaced;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('products', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('type')->default('simple');
        $table->decimal('price', 10, 2)->default(0);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->timestamp('sale_starts_at')->nullable();
        $table->timestamp('sale_ends_at')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->integer('stock_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
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
        $table->integer('reserved_quantity')->default(0);
        $table->boolean('in_stock')->default(true);
        $table->json('attributes')->nullable();
        $table->integer('sort_order')->default(0);
        $table->string('status')->default('published');
        $table->timestamps();
    });

    Schema::create('shipping_zones', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('country');
        $table->string('state')->nullable();
        $table->string('city')->nullable();
        $table->string('postal_code_pattern')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('shipping_methods', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('code')->unique();
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('shipping_rates', function ($table) {
        $table->string('id')->primary();
        $table->string('shipping_zone_id')->nullable();
        $table->string('shipping_method_id')->nullable();
        $table->string('rate_type')->default('flat');
        $table->decimal('amount', 12, 2)->default(0);
        $table->decimal('free_over_amount', 12, 2)->nullable();
        $table->decimal('min_order_amount', 12, 2)->nullable();
        $table->decimal('max_order_amount', 12, 2)->nullable();
        $table->integer('estimated_days_min')->nullable();
        $table->integer('estimated_days_max')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    if (! Schema::hasTable('orders')) {
        Schema::create('orders', function ($table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_id')->nullable();
            $table->string('order_number')->unique();
            $table->string('status');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3);
            $table->string('shipping_rate_id')->nullable();
            $table->string('shipping_method_name');
            $table->string('shipping_zone_name');
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('order_items')) {
        Schema::create('order_items', function ($table) {
            $table->string('id')->primary();
            $table->string('order_id');
            $table->string('product_id');
            $table->string('product_variant_id')->nullable();
            $table->string('product_name');
            $table->string('product_slug');
            $table->string('sku');
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('line_total', 12, 2);
            $table->json('product_snapshot')->nullable();
            $table->timestamps();
        });
    }

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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function cartUser(): User
{
    static $counter = 0;
    $counter++;

    return User::create([
        'name' => 'Cart User',
        'email' => "cart{$counter}@example.com",
        'password' => bcrypt('password'),
    ]);
}

function cartProduct(array $overrides = []): Product
{
    $product = Product::create(array_merge([
        'name' => 'Cart Product',
        'slug' => 'cart-product-'.uniqid(),
        'status' => 'published',
        'published_at' => now()->subDay(),
        'type' => 'simple',
    ], $overrides));

    $categoryId = $overrides['category_id'] ?? null;
    if ($categoryId) {
        DB::table('category_product')->insert([
            'id' => (string) Str::ulid(),
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'is_primary' => true,
            'sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    cartVariant($product, [
        'is_default' => true,
        'price' => $overrides['price'] ?? 0,
        'manage_stock' => $overrides['manage_stock'] ?? false,
        'stock_quantity' => $overrides['stock_quantity'] ?? 0,
        'in_stock' => $overrides['in_stock'] ?? true,
    ]);

    return $product;
}

function cartVariant(Product $product, array $overrides = []): ProductVariant
{
    return ProductVariant::create(array_merge([
        'id' => (string) Str::ulid(),
        'product_id' => $product->id,
        'sku' => 'SKU-'.Str::upper(Str::random(8)),
        'is_default' => false,
        'price' => 1000,
        'manage_stock' => false,
        'stock_quantity' => 0,
        'reserved_quantity' => 0,
        'in_stock' => true,
        'attributes' => [],
        'sort_order' => 0,
        'status' => 'published',
    ], $overrides));
}

function guestHeaders(string $token): array
{
    return ['X-Cart-Token' => $token];
}

// ---------------------------------------------------------------------------
// GET /api/v1/cart
// ---------------------------------------------------------------------------

test('get cart without auth or token returns 401', function () {
    $this->getJson('/api/v1/cart')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

test('guest get with token returns their items', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['name' => 'Token Product', 'slug' => 'token-product']);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 2,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/cart')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product_id', $product->id)
        ->assertJsonPath('data.0.name', 'Token Product')
        ->assertJsonPath('data.0.quantity', 2)
        ->assertJsonPath('cart_token', $token);
});

// ---------------------------------------------------------------------------
// POST /api/v1/cart — Guest
// ---------------------------------------------------------------------------

test('guest add generates new cart_token', function () {
    $product = cartProduct();

    $response = $this->postJson('/api/v1/cart', ['product_id' => $product->id]);

    $response->assertStatus(201);

    $token = $response->json('cart_token');
    expect($token)->not->toBeNull();

    $item = CartItem::withoutGlobalScopes()
        ->where('cart_token', $token)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($item->expires_at->greaterThan(now()->addDays(6)))->toBeTrue();
});

test('guest add with existing token reuses token', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    $response = $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', ['product_id' => $product->id]);

    $response->assertStatus(201)
        ->assertJsonPath('cart_token', $token);
});

test('storefront cart accepts descendants and rejects products outside its category tree', function () {
    $rootId = (string) Str::ulid();
    $childId = (string) Str::ulid();
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
            'id' => $otherRootId,
            'name' => 'Beauty',
            'slug' => 'beauty',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $allowed = cartProduct(['name' => 'Allowed Wig', 'category_id' => $childId]);
    $outside = cartProduct(['name' => 'Outside Product', 'category_id' => $otherRootId]);

    $response = $this->postJson('/api/v1/storefronts/hair/cart', [
        'product_id' => $allowed->id,
    ])->assertCreated();

    $token = $response->json('cart_token');

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/storefronts/hair/cart', ['product_id' => $outside->id])
        ->assertNotFound();

    $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/storefronts/hair/cart')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product_id', $allowed->id);
});

test('storefront checkout ignores cart products outside its category tree', function () {
    $rootId = (string) Str::ulid();
    $otherRootId = (string) Str::ulid();
    $shippingRateId = (string) Str::ulid();
    $token = Str::uuid()->toString();

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
    DB::table('shipping_rates')->insert(['id' => $shippingRateId]);

    $outside = cartProduct(['name' => 'Outside Product', 'category_id' => $otherRootId]);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $outside->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token) + ['Idempotency-Key' => 'checkout-storefront-scope-test'])
        ->postJson('/api/v1/storefronts/hair/checkout/cart', [
            'shipping_rate_id' => $shippingRateId,
            'payment_method' => 'paystack',
            'shipping_address' => [
                'full_name' => 'Storefront Customer',
                'email' => 'customer@example.com',
                'phone' => '+2348000000000',
                'country' => 'Nigeria',
                'state' => 'Lagos',
                'city' => 'Lagos',
                'line1' => '1 Test Street',
            ],
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Cart is empty.');
});

test('duplicate add increments quantity', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1])
        ->assertStatus(201)
        ->assertJsonPath('data.quantity', 2);

    expect(
        CartItem::withoutGlobalScopes()
            ->where('cart_token', $token)
            ->where('product_id', $product->id)
            ->count()
    )->toBe(1);
});

// ---------------------------------------------------------------------------
// POST /api/v1/cart — Auth
// ---------------------------------------------------------------------------

test('auth add returns null cart_token', function () {
    $user = cartUser();
    $product = cartProduct();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id])
        ->assertStatus(201)
        ->assertJsonPath('cart_token', null);
});

// ---------------------------------------------------------------------------
// PATCH /api/v1/cart/{productId}
// ---------------------------------------------------------------------------

test('update changes quantity and resets expires_at', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();
    $oldExpiry = now()->addMinutes(30);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => $oldExpiry,
    ]);

    $response = $this->withHeaders(guestHeaders($token))
        ->patchJson("/api/v1/cart/{$product->id}", ['quantity' => 5]);

    $response->assertStatus(200)
        ->assertJsonPath('data.quantity', 5);

    $item = CartItem::withoutGlobalScopes()
        ->where('cart_token', $token)
        ->where('product_id', $product->id)
        ->first();

    expect($item->expires_at->greaterThan($oldExpiry))->toBeTrue();
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/cart/{productId}
// ---------------------------------------------------------------------------

test('remove deletes item and returns 204', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->deleteJson("/api/v1/cart/{$product->id}")
        ->assertStatus(204);

    expect(
        CartItem::withoutGlobalScopes()
            ->where('cart_token', $token)
            ->where('product_id', $product->id)
            ->exists()
    )->toBeFalse();
});

test('remove non-existent item returns 404', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    $this->withHeaders(guestHeaders($token))
        ->deleteJson("/api/v1/cart/{$product->id}")
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/cart
// ---------------------------------------------------------------------------

test('clear empties the cart and returns 204', function () {
    $token = Str::uuid()->toString();
    $product1 = cartProduct();
    $product2 = cartProduct();

    CartItem::create(
        ['cart_token' => $token, 'product_id' => $product1->id, 'quantity' => 1, 'expires_at' => now()->addHour()]
    );
    CartItem::create(
        ['cart_token' => $token, 'product_id' => $product2->id, 'quantity' => 2, 'expires_at' => now()->addHour()]
    );

    $this->withHeaders(guestHeaders($token))
        ->deleteJson('/api/v1/cart')
        ->assertStatus(204);

    expect(CartItem::withoutGlobalScopes()->where('cart_token', $token)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Expiry
// ---------------------------------------------------------------------------

test('expired items are excluded from get', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::withoutGlobalScopes()->create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->subMinute(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/cart')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('re-adding an expired item refreshes it', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::withoutGlobalScopes()->create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->subMinute(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', ['product_id' => $product->id])
        ->assertStatus(201);

    $item = CartItem::withoutGlobalScopes()
        ->where('cart_token', $token)
        ->where('product_id', $product->id)
        ->first();

    expect($item->expires_at->isFuture())->toBeTrue();
});

// ---------------------------------------------------------------------------
// POST /api/v1/cart/merge
// ---------------------------------------------------------------------------

test('merge inserts missing guest items into user cart', function () {
    $user = cartUser();
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 3,
        'expires_at' => now()->addHour(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cart/merge', ['cart_token' => $token]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('cart_token', null)
        ->assertJsonPath('guest_cart_empty', true)
        ->assertJsonPath('data.0.product_id', $product->id)
        ->assertJsonPath('data.0.quantity', 3);

    expect(CartItem::withoutGlobalScopes()->where('cart_token', $token)->exists())->toBeFalse();
});

test('merge increments quantity for shared products', function () {
    $user = cartUser();
    $token = Str::uuid()->toString();
    $product = cartProduct();

    CartItem::create(
        ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2, 'expires_at' => now()->addHour()]
    );
    CartItem::create(
        ['cart_token' => $token, 'product_id' => $product->id, 'quantity' => 3, 'expires_at' => now()->addHour()]
    );

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart/merge', ['cart_token' => $token])
        ->assertStatus(200)
        ->assertJsonPath('data.0.quantity', 5);
});

test('merge combines matching variants and preserves different variants', function () {
    $user = cartUser();
    $token = Str::uuid()->toString();
    $product = cartProduct(['type' => 'variable']);
    $firstVariant = $product->defaultVariant()->firstOrFail();
    $secondVariant = cartVariant($product);

    CartItem::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'product_variant_id' => $firstVariant->id,
        'quantity' => 2,
        'expires_at' => now()->addHour(),
    ]);
    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'product_variant_id' => $firstVariant->id,
        'quantity' => 3,
        'expires_at' => now()->addHour(),
    ]);
    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'product_variant_id' => $secondVariant->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cart/merge', ['cart_token' => $token])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $items = collect($response->json('data'))->keyBy('product_variant_id');

    expect($items[$firstVariant->id]['quantity'])->toBe(5)
        ->and($items[$secondVariant->id]['quantity'])->toBe(1)
        ->and(CartItem::withoutGlobalScopes()->where('cart_token', $token)->exists())->toBeFalse();
});

test('merge rolls back every change when a later item exceeds available stock', function () {
    $user = cartUser();
    $token = Str::uuid()->toString();
    $availableProduct = cartProduct();
    $limitedProduct = cartProduct(['manage_stock' => true, 'stock_quantity' => 1]);

    CartItem::create([
        'user_id' => $user->id,
        'product_id' => $availableProduct->id,
        'quantity' => 2,
        'expires_at' => now()->addHour(),
    ]);
    CartItem::create([
        'cart_token' => $token,
        'product_id' => $availableProduct->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);
    CartItem::create([
        'cart_token' => $token,
        'product_id' => $limitedProduct->id,
        'quantity' => 2,
        'expires_at' => now()->addHour(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart/merge', ['cart_token' => $token])
        ->assertStatus(422);

    expect(CartItem::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('product_id', $availableProduct->id)
        ->value('quantity'))->toBe(2)
        ->and(CartItem::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1)
        ->and(CartItem::withoutGlobalScopes()->where('cart_token', $token)->count())->toBe(2);
});

test('storefront merge keeps the token while another storefront still has guest items', function () {
    $user = cartUser();
    $token = Str::uuid()->toString();
    $hairId = (string) Str::ulid();
    $beautyId = (string) Str::ulid();

    DB::table('categories')->insert([
        ['id' => $hairId, 'name' => 'Hair', 'slug' => 'hair', 'parent_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => $beautyId, 'name' => 'Beauty', 'slug' => 'beauty', 'parent_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $hairProduct = cartProduct(['category_id' => $hairId]);
    $beautyProduct = cartProduct(['category_id' => $beautyId]);

    foreach ([$hairProduct, $beautyProduct] as $product) {
        CartItem::create([
            'cart_token' => $token,
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->addHour(),
        ]);
    }

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/storefronts/hair/cart/merge', ['cart_token' => $token])
        ->assertOk()
        ->assertJsonPath('guest_cart_empty', false)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product_id', $hairProduct->id);

    expect(CartItem::withoutGlobalScopes()->where('user_id', $user->id)->where('product_id', $hairProduct->id)->exists())->toBeTrue()
        ->and(CartItem::withoutGlobalScopes()->where('cart_token', $token)->where('product_id', $beautyProduct->id)->exists())->toBeTrue();
});

test('unauthenticated merge returns 401', function () {
    $this->postJson('/api/v1/cart/merge', ['cart_token' => Str::uuid()->toString()])
        ->assertStatus(401);
});

// ---------------------------------------------------------------------------
// Cart identifier validation (auth or X-Cart-Token required)
// ---------------------------------------------------------------------------

test('get cart with token returns items', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['name' => 'Token Product', 'slug' => 'token-product-2']);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/cart')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('update without auth or token returns 401', function () {
    $product = cartProduct();

    $this->patchJson("/api/v1/cart/{$product->id}", ['quantity' => 2])
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

test('remove without auth or token returns 401', function () {
    $product = cartProduct();

    $this->deleteJson("/api/v1/cart/{$product->id}")
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

test('clear without auth or token returns 401', function () {
    $this->deleteJson('/api/v1/cart')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

// ---------------------------------------------------------------------------
// Stock validation
// ---------------------------------------------------------------------------

test('add returns 422 when requested quantity exceeds stock', function () {
    $product = cartProduct(['manage_stock' => true, 'stock_quantity' => 3]);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 5])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('add succeeds when requested quantity is within stock', function () {
    $product = cartProduct(['manage_stock' => true, 'stock_quantity' => 5]);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3])
        ->assertStatus(201);
});

test('add returns 422 when incrementing exceeds stock', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['manage_stock' => true, 'stock_quantity' => 3]);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 2,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('update returns 422 when quantity exceeds stock', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['manage_stock' => true, 'stock_quantity' => 4]);

    CartItem::create([
        'cart_token' => $token,
        'product_id' => $product->id,
        'quantity' => 1,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(guestHeaders($token))
        ->patchJson("/api/v1/cart/{$product->id}", ['quantity' => 10])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('stock check is skipped when manage_stock is false', function () {
    $product = cartProduct(['manage_stock' => false, 'stock_quantity' => 0]);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 999])
        ->assertStatus(201);
});

// ---------------------------------------------------------------------------
// Variant-aware cart behavior
// ---------------------------------------------------------------------------

test('simple product automatically uses its default variant', function () {
    $product = cartProduct(['price' => 2500]);
    $variant = $product->defaultVariant()->firstOrFail();

    $this->postJson('/api/v1/cart', ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.product_variant_id', $variant->id)
        ->assertJsonPath('data.sku', $variant->sku)
        ->assertJsonPath('data.unit_price', 2500);
});

test('simple product accepts an active default variant', function () {
    $product = cartProduct(['price' => 2500]);
    $variant = $product->defaultVariant()->firstOrFail();
    $variant->update(['status' => 'active']);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.product_variant_id', $variant->id);
});

test('variable product requires an explicit variant selection', function () {
    $product = cartProduct(['type' => 'variable']);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id])
        ->assertStatus(422)
        ->assertJsonPath('message', 'A product variant must be selected.');
});

test('variable product accepts an explicitly selected active variant', function () {
    $product = cartProduct(['type' => 'variable']);
    $variant = $product->defaultVariant()->firstOrFail();
    $variant->update(['status' => 'active']);

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.product_variant_id', $variant->id);
});

test('different variants of one product remain separate cart items', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['type' => 'variable']);
    $defaultVariant = $product->defaultVariant()->firstOrFail();
    $defaultVariant->update([
        'sku' => 'WIG-BLACK',
        'price' => 3500,
        'attributes' => [['type' => 'color', 'value' => 'BLACK']],
    ]);
    $redVariant = cartVariant($product, [
        'sku' => 'WIG-RED',
        'price' => 4200,
        'attributes' => [['type' => 'color', 'value' => 'RED']],
    ]);

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', [
            'product_id' => $product->id,
            'product_variant_id' => $defaultVariant->id,
        ])
        ->assertCreated();

    $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', [
            'product_id' => $product->id,
            'product_variant_id' => $redVariant->id,
            'quantity' => 2,
        ])
        ->assertCreated();

    $response = $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $items = collect($response->json('data'))->keyBy('product_variant_id');

    expect($items[$defaultVariant->id])
        ->sku->toBe('WIG-BLACK')
        ->unit_price->toBe(3500)
        ->attributes->toBe([['type' => 'color', 'value' => 'BLACK']]);
    expect($items[$redVariant->id])
        ->sku->toBe('WIG-RED')
        ->quantity->toBe(2)
        ->unit_price->toBe(4200);
});

test('selected variant must belong to the submitted product', function () {
    $product = cartProduct(['type' => 'variable']);
    $otherProduct = cartProduct(['type' => 'variable']);
    $otherVariant = $otherProduct->defaultVariant()->firstOrFail();

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'product_variant_id' => $otherVariant->id,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'The selected product variant is not available.');
});

test('item routes update and remove one selected variant', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct(['type' => 'variable']);
    $firstVariant = $product->defaultVariant()->firstOrFail();
    $secondVariant = cartVariant($product);

    $firstItemId = $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', [
            'product_id' => $product->id,
            'product_variant_id' => $firstVariant->id,
        ])
        ->json('data.id');

    $secondItemId = $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', [
            'product_id' => $product->id,
            'product_variant_id' => $secondVariant->id,
        ])
        ->json('data.id');

    $this->withHeaders(guestHeaders($token))
        ->patchJson("/api/v1/cart/items/{$firstItemId}", ['quantity' => 4])
        ->assertOk()
        ->assertJsonPath('data.quantity', 4)
        ->assertJsonPath('data.product_variant_id', $firstVariant->id);

    $this->withHeaders(guestHeaders($token))
        ->patchJson("/api/v1/cart/{$product->id}", ['quantity' => 2])
        ->assertStatus(409);

    $this->withHeaders(guestHeaders($token))
        ->deleteJson("/api/v1/cart/items/{$secondItemId}")
        ->assertNoContent();

    $this->withHeaders(guestHeaders($token))
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $firstItemId);
});

test('stock is validated independently for each selected variant', function () {
    $product = cartProduct(['type' => 'variable']);
    $limitedVariant = $product->defaultVariant()->firstOrFail();
    $limitedVariant->update([
        'manage_stock' => true,
        'stock_quantity' => 2,
        'reserved_quantity' => 1,
        'in_stock' => true,
    ]);
    $availableVariant = cartVariant($product, [
        'manage_stock' => true,
        'stock_quantity' => 5,
        'in_stock' => true,
    ]);

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'product_variant_id' => $limitedVariant->id,
        'quantity' => 2,
    ])->assertStatus(422);

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'product_variant_id' => $availableVariant->id,
        'quantity' => 2,
    ])->assertCreated();
});

test('checkout reserves and snapshots the selected variant', function () {
    Event::fake([OrderPlaced::class]);

    $product = cartProduct(['type' => 'variable']);
    $defaultVariant = $product->defaultVariant()->firstOrFail();
    $defaultVariant->update([
        'sku' => 'WIG-DEFAULT',
        'price' => 3000,
        'manage_stock' => true,
        'stock_quantity' => 10,
    ]);
    $selectedVariant = cartVariant($product, [
        'sku' => 'WIG-RED-LONG',
        'price' => 4500,
        'manage_stock' => true,
        'stock_quantity' => 5,
        'attributes' => [
            ['type' => 'color', 'value' => 'RED'],
            ['type' => 'length', 'value' => 'LONG'],
        ],
    ]);

    $zoneId = (string) Str::ulid();
    $methodId = (string) Str::ulid();
    $rateId = (string) Str::ulid();

    DB::table('shipping_zones')->insert([
        'id' => $zoneId,
        'name' => 'Lagos Zone',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'city' => 'Lagos',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('shipping_methods')->insert([
        'id' => $methodId,
        'name' => 'Standard Delivery',
        'code' => 'standard',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('shipping_rates')->insert([
        'id' => $rateId,
        'shipping_zone_id' => $zoneId,
        'shipping_method_id' => $methodId,
        'rate_type' => 'flat',
        'amount' => 1000,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cartResponse = $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'product_variant_id' => $selectedVariant->id,
        'quantity' => 2,
    ])->assertCreated();

    $headers = guestHeaders($cartResponse->json('cart_token')) + ['Idempotency-Key' => 'checkout-selected-variant-test'];
    $payload = [
        'shipping_rate_id' => $rateId,
        'payment_method' => 'paystack',
        'shipping_address' => [
            'full_name' => 'Variant Customer',
            'email' => 'variant@example.com',
            'phone' => '+2348000000000',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Lagos',
            'line1' => '1 Variant Street',
        ],
    ];

    $first = $this->withHeaders($headers)
        ->postJson('/api/v1/checkout/cart', $payload)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'false')
        ->assertJsonPath('data.subtotal', 9000)
        ->assertJsonPath('data.items.0.product_variant_id', $selectedVariant->id)
        ->assertJsonPath('data.items.0.sku', 'WIG-RED-LONG')
        ->assertJsonPath('data.items.0.product_snapshot.attributes.0.value', 'RED');

    $this->withHeaders($headers)
        ->postJson('/api/v1/checkout/cart', $payload)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true')
        ->assertJsonPath('data.id', $first->json('data.id'));

    $this->withHeaders($headers)
        ->postJson('/api/v1/checkout/cart', array_replace_recursive($payload, [
            'shipping_address' => ['line1' => 'Different Address'],
        ]))
        ->assertStatus(422)
        ->assertJsonPath('message', 'This Idempotency-Key was already used with a different checkout request.');

    expect($selectedVariant->fresh()->reserved_quantity)->toBe(2)
        ->and($defaultVariant->fresh()->reserved_quantity)->toBe(0)
        ->and(Order::query()->count())->toBe(1)
        ->and(CartItem::query()->where('cart_token', $cartResponse->json('cart_token'))->count())->toBe(0);
});
