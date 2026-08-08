<?php

use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentTransaction;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    config()->set('services.paystack.secret_key', 'test-secret-key');

    Schema::create('shipping_rates', function ($table) {
        $table->string('id')->primary();
    });

    Schema::create('payment_methods', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('code')->unique();
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->json('public_config')->nullable();
        $table->timestamps();
    });

    if (! Schema::hasTable('product_variants')) {
        Schema::create('product_variants', function ($table) {
            $table->string('id')->primary();
            $table->string('product_id');
            $table->string('sku');
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->timestamps();
        });
    }
});

it('requires an idempotency key to initialize payment', function () {
    $order = paymentOrder();

    $this->withHeader('X-Cart-Token', $order->guest_id)
        ->postJson('/api/v1/payments/initialize', [
            'order_id' => $order->id,
            'payment_method' => 'paystack',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'A valid Idempotency-Key header is required.');
});

it('does not initialize a guest order for a different cart token', function () {
    paymentMethod('paystack', 'Paystack');
    $order = paymentOrder();

    $this->withHeaders([
        'X-Cart-Token' => (string) Str::uuid(),
        'Idempotency-Key' => 'payment-wrong-owner-test-key',
    ])->postJson('/api/v1/payments/initialize', [
        'order_id' => $order->id,
        'payment_method' => 'paystack',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Order not found or does not belong to this customer.');

    expect(Payment::query()->count())->toBe(0);
});

it('replays payment initialization without creating another payment or gateway session', function () {
    paymentMethod('paystack', 'Paystack');
    paymentMethod('flutterwave', 'Flutterwave');
    $order = paymentOrder();

    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://checkout.test/paystack',
                'access_code' => 'ACCESS-123',
                'reference' => 'provider-reference',
            ],
        ]),
    ]);

    $headers = [
        'X-Cart-Token' => $order->guest_id,
        'Idempotency-Key' => 'payment-initialization-test-key',
    ];
    $payload = ['order_id' => $order->id, 'payment_method' => 'paystack'];

    $first = $this->withHeaders($headers)
        ->postJson('/api/v1/payments/initialize', $payload)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'false');

    $this->withHeaders($headers)
        ->postJson('/api/v1/payments/initialize', $payload)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true')
        ->assertJsonPath('data.payment.id', $first->json('data.payment.id'))
        ->assertJsonPath('data.reference', $first->json('data.reference'));

    $this->withHeaders($headers)
        ->postJson('/api/v1/payments/initialize', [
            'order_id' => $order->id,
            'payment_method' => 'flutterwave',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This Idempotency-Key was already used with a different payment request.');

    expect(Payment::query()->count())->toBe(1)
        ->and(PaymentTransaction::query()->where('type', 'create')->count())->toBe(1)
        ->and(PaymentTransaction::query()->where('type', 'initialize')->count())->toBe(1);

    Http::assertSentCount(1);
});

it('applies a verified provider transaction and inventory commitment exactly once', function () {
    $methodId = paymentMethod('paystack', 'Paystack');
    $variantId = (string) Str::ulid();
    $order = paymentOrder();

    DB::table('product_variants')->insert([
        'id' => $variantId,
        'product_id' => (string) Str::ulid(),
        'sku' => 'IDEMPOTENT-SKU',
        'stock_quantity' => 5,
        'reserved_quantity' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('order_items')->insert([
        'id' => (string) Str::ulid(),
        'order_id' => $order->id,
        'product_id' => (string) Str::ulid(),
        'product_variant_id' => $variantId,
        'product_name' => 'Idempotent Product',
        'product_slug' => 'idempotent-product',
        'sku' => 'IDEMPOTENT-SKU',
        'unit_price' => 5000,
        'quantity' => 2,
        'line_total' => 10000,
        'product_snapshot' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $methodId,
        'provider' => 'paystack',
        'reference' => 'PAY-IDEMPOTENT-VERIFY',
        'amount' => 10000,
        'currency' => 'NGN',
        'status' => 'initialized',
        'metadata' => [],
    ]);

    Http::fake([
        'https://api.paystack.co/transaction/*' => Http::response([
            'status' => true,
            'data' => [
                'reference' => $payment->reference,
                'id' => 'provider-transaction-100',
                'status' => 'success',
                'amount' => 1000000,
                'currency' => 'NGN',
            ],
        ]),
    ]);

    $payload = ['provider' => 'paystack', 'transaction_id' => 'provider-transaction-100'];

    $this->postJson('/api/v1/payments/verify', $payload)->assertOk();
    $this->postJson('/api/v1/payments/verify', $payload)->assertOk();

    $variant = ProductVariant::query()->findOrFail($variantId);
    $order->refresh();
    $payment->refresh();

    expect($variant->stock_quantity)->toBe(3)
        ->and($variant->reserved_quantity)->toBe(0)
        ->and($order->status->value)->toBe('paid')
        ->and($order->inventory_committed_at)->not->toBeNull()
        ->and($payment->status)->toBe('paid')
        ->and(PaymentTransaction::query()->where('type', 'verify')->count())->toBe(1)
        ->and(PaymentTransaction::query()->where('type', 'paid')->count())->toBe(1)
        ->and(DB::table('outbox_messages')->where('event_key', "order:{$order->id}:paid")->count())->toBe(1);
});

function paymentMethod(string $code, string $name): string
{
    $id = (string) Str::ulid();
    DB::table('payment_methods')->insert([
        'id' => $id,
        'name' => $name,
        'code' => $code,
        'description' => null,
        'is_active' => true,
        'sort_order' => 1,
        'public_config' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function paymentOrder(): Order
{
    return Order::query()->create([
        'guest_id' => (string) Str::uuid(),
        'order_number' => 'GR-'.Str::upper(Str::random(12)),
        'status' => 'pending_payment',
        'subtotal' => 10000,
        'shipping_amount' => 0,
        'total' => 10000,
        'currency' => 'NGN',
        'shipping_method_name' => 'Standard',
        'shipping_zone_name' => 'Lagos',
        'shipping_address' => [
            'full_name' => 'Payment Customer',
            'email' => 'payment@example.com',
            'phone' => '+2348000000000',
        ],
        'placed_at' => now(),
        'expires_at' => now()->addMinutes(30),
    ]);
}
