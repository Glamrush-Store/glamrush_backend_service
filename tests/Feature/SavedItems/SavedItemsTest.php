<?php

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\SavedItem;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
        $table->timestamps();
    });
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createUser(): User
{
    return User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

function createProduct(array $overrides = []): Product
{
    return Product::create(array_merge([
        'name' => 'Test Product',
        'slug' => 'test-product-' . uniqid(),
        'status' => 'published',
        'published_at' => now()->subDay(),
        'type' => 'simple',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// GET /api/v1/saved-items
// ---------------------------------------------------------------------------

test('unauthenticated list returns 401', function () {
    $this->getJson('/api/v1/saved-items')->assertStatus(401);
});

test('list returns empty collection for new user', function () {
    Sanctum::actingAs(createUser());

    $this->getJson('/api/v1/saved-items')
        ->assertStatus(200)
        ->assertJsonPath('data', []);
});

test('list returns saved items with correct shape', function () {
    $user = createUser();
    $product = createProduct(['name' => 'My Saved Product', 'slug' => 'my-saved-product']);

    SavedItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/saved-items')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'product_id', 'name', 'slug', 'thumb']],
        ])
        ->assertJsonPath('data.0.product_id', $product->id)
        ->assertJsonPath('data.0.name', 'My Saved Product')
        ->assertJsonPath('data.0.slug', 'my-saved-product')
        ->assertJsonPath('data.0.thumb', null);
});

// ---------------------------------------------------------------------------
// POST /api/v1/saved-items
// ---------------------------------------------------------------------------

test('unauthenticated add returns 401', function () {
    $product = createProduct();

    $this->postJson('/api/v1/saved-items', ['product_id' => $product->id])
        ->assertStatus(401);
});

test('add creates saved item and returns 201', function () {
    $user = createUser();
    $product = createProduct();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/saved-items', ['product_id' => $product->id])
        ->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'product_id', 'name', 'slug', 'thumb']])
        ->assertJsonPath('data.product_id', $product->id);

    expect(SavedItem::where('user_id', $user->id)->where('product_id', $product->id)->exists())
        ->toBeTrue();
});

test('add existing item is idempotent and returns 200', function () {
    $user = createUser();
    $product = createProduct();

    SavedItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/saved-items', ['product_id' => $product->id])
        ->assertStatus(200)
        ->assertJsonPath('data.product_id', $product->id);

    expect(SavedItem::where('user_id', $user->id)->where('product_id', $product->id)->count())
        ->toBe(1);
});

test('add with invalid product_id returns 422', function () {
    Sanctum::actingAs(createUser());

    $this->postJson('/api/v1/saved-items', ['product_id' => 'nonexistent-id'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['product_id']);
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/saved-items/{productId}
// ---------------------------------------------------------------------------

test('unauthenticated remove returns 401', function () {
    $this->deleteJson('/api/v1/saved-items/some-id')->assertStatus(401);
});

test('remove deletes saved item and returns 204', function () {
    $user = createUser();
    $product = createProduct();

    SavedItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/saved-items/{$product->id}")
        ->assertStatus(204);

    expect(SavedItem::where('user_id', $user->id)->where('product_id', $product->id)->exists())
        ->toBeFalse();
});

test('remove non-existent saved item returns 404', function () {
    $user = createUser();
    $product = createProduct();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/saved-items/{$product->id}")
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// POST /api/v1/saved-items/sync
// ---------------------------------------------------------------------------

test('unauthenticated sync returns 401', function () {
    $this->postJson('/api/v1/saved-items/sync', ['product_ids' => []])
        ->assertStatus(401);
});

test('sync inserts only missing items', function () {
    $user = createUser();
    $existing = createProduct(['slug' => 'existing-' . uniqid()]);
    $newOne = createProduct(['slug' => 'new-' . uniqid()]);

    SavedItem::create(['user_id' => $user->id, 'product_id' => $existing->id]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/saved-items/sync', [
        'product_ids' => [$existing->id, $newOne->id],
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');

    expect(SavedItem::where('user_id', $user->id)->count())->toBe(2);
});

test('sync with all already saved is a no-op', function () {
    $user = createUser();
    $product = createProduct();

    SavedItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/saved-items/sync', [
        'product_ids' => [$product->id],
    ])->assertStatus(200)->assertJsonCount(1, 'data');

    expect(SavedItem::where('user_id', $user->id)->count())->toBe(1);
});
