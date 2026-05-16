<?php

use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    return Product::create(array_merge([
        'name' => 'Cart Product',
        'slug' => 'cart-product-' . uniqid(),
        'status' => 'published',
        'published_at' => now()->subDay(),
        'type' => 'simple',
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

    expect(
        CartItem::withoutGlobalScopes()
            ->where('cart_token', $token)
            ->where('product_id', $product->id)
            ->exists()
    )->toBeTrue();
});

test('guest add with existing token reuses token', function () {
    $token = Str::uuid()->toString();
    $product = cartProduct();

    $response = $this->withHeaders(guestHeaders($token))
        ->postJson('/api/v1/cart', ['product_id' => $product->id]);

    $response->assertStatus(201)
        ->assertJsonPath('cart_token', $token);
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
        'quantity'   => 1,
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
        'quantity'   => 2,
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
        'quantity'   => 1,
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
