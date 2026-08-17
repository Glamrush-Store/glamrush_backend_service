<?php

use App\Domain\Location\Services\LocationService;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShippingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

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
        $table->string('code');
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    Schema::create('shipping_rates', function ($table) {
        $table->string('id')->primary();
        $table->string('shipping_zone_id');
        $table->string('shipping_method_id');
        $table->string('rate_type');
        $table->decimal('amount', 12, 2);
        $table->decimal('free_over_amount', 12, 2)->nullable();
        $table->decimal('min_order_amount', 12, 2)->nullable();
        $table->decimal('max_order_amount', 12, 2)->nullable();
        $table->integer('estimated_days_min')->nullable();
        $table->integer('estimated_days_max')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
});

it('caches active payment-method configuration', function () {
    DB::table('payment_methods')->insert(paymentMethodRow('paystack', 'Paystack'));
    $repository = new EloquentPaymentMethodRepository;

    expect($repository->active())->toHaveCount(1);

    DB::table('payment_methods')->insert(paymentMethodRow('flutterwave', 'Flutterwave'));

    expect($repository->active())->toHaveCount(1)
        ->and($repository->findActiveByCode('flutterwave'))->toBeNull();

    QueryCache::forget(CacheTags::PAYMENT_METHODS);

    expect($repository->active())->toHaveCount(2)
        ->and($repository->findActiveByCode('flutterwave')?->name)->toBe('Flutterwave');
});

it('caches shipping zone and rate configuration independently of subtotal calculations', function () {
    $zoneId = (string) Str::ulid();
    $methodId = (string) Str::ulid();
    $rateId = (string) Str::ulid();

    DB::table('shipping_zones')->insert([
        'id' => $zoneId,
        'name' => 'Lagos Zone',
        'country' => 'NG',
        'state' => 'Lagos',
        'city' => null,
        'postal_code_pattern' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('shipping_methods')->insert([
        'id' => $methodId,
        'name' => 'Standard Delivery',
        'code' => 'standard',
        'description' => 'Three to seven business days.',
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('shipping_rates')->insert([
        'id' => $rateId,
        'shipping_zone_id' => $zoneId,
        'shipping_method_id' => $methodId,
        'rate_type' => 'flat',
        'amount' => 2500,
        'free_over_amount' => 50000,
        'min_order_amount' => null,
        'max_order_amount' => null,
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = new EloquentShippingRepository(app(LocationService::class));
    $address = new ShippingAddressEntity('NGA', 'LA', 'Ikeja', null);

    expect($repository->findBestZoneForAddress($address)?->name)->toBe('Lagos Zone')
        ->and($repository->getActiveRatesForZone($zoneId)[0]->amount)->toBe(2500.0);

    DB::table('shipping_zones')->where('id', $zoneId)->update(['name' => 'Updated Zone']);
    DB::table('shipping_rates')->where('id', $rateId)->update(['amount' => 3500]);

    expect($repository->findBestZoneForAddress($address)?->name)->toBe('Lagos Zone')
        ->and($repository->getActiveRatesForZone($zoneId)[0]->amount)->toBe(2500.0);

    QueryCache::forget(CacheTags::SHIPPING);

    expect($repository->findBestZoneForAddress($address)?->name)->toBe('Updated Zone')
        ->and($repository->getActiveRatesForZone($zoneId)[0]->amount)->toBe(3500.0);
});

function paymentMethodRow(string $code, string $name): array
{
    return [
        'id' => (string) Str::ulid(),
        'name' => $name,
        'code' => $code,
        'description' => null,
        'is_active' => true,
        'sort_order' => 1,
        'public_config' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
