<?php

use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Entities\ShippingMethodEntity;
use App\Domain\Shipping\Entities\ShippingRateEntity;
use App\Domain\Shipping\Entities\ShippingZoneEntity;
use App\Domain\Shipping\Services\ShippingQuoteService;
use App\Presentation\Http\Resources\Shipping\ShippingOptionResource;

function shippingFakeRepository(?ShippingZoneEntity $zone, array $rates): ShippingRepository
{
    return new class($zone, $rates) implements ShippingRepository {
        public function __construct(
            private readonly ?ShippingZoneEntity $zone,
            private readonly array $rates,
        ) {
        }


        public function findBestZoneForAddress(ShippingAddressEntity $address): null|ShippingZoneEntity
        {
            return $this->zone;
        }

        public function getActiveRatesForZone(string $zoneId): array
        {
            return $this->rates;
        }
    };
}

function shippingZone(array $overrides = []): ShippingZoneEntity
{
    return new ShippingZoneEntity(
        id: $overrides['id'] ?? 'zone_lagos',
        name: $overrides['name'] ?? 'Lagos',
        country: $overrides['country'] ?? 'Nigeria',
        state: $overrides['state'] ?? 'Lagos',
        city: $overrides['city'] ?? null,
        postalCode: $overrides['postalCode'] ?? null,
    );
}

function shippingMethod(array $overrides = []): ShippingMethodEntity
{
    return new ShippingMethodEntity(
        id: $overrides['id'] ?? 'method_standard',
        code: $overrides['code'] ?? 'standard',
        name: $overrides['name'] ?? 'Standard Delivery',
        description: $overrides['description'] ?? 'Standard delivery',
        is_active: $overrides['isActive'] ?? true,
    );
}

function shippingRate(ShippingZoneEntity $zone, ShippingMethodEntity $method, array $overrides = []): ShippingRateEntity
{
    return new ShippingRateEntity(
        id: $overrides['id'] ?? 'rate_standard_lagos',
        shippingZone: $zone,
        shippingMethod: $method,
        rateType: $overrides['rateType'] ?? 'flat',
        amount: $overrides['amount'] ?? 2000.0,
        freeOverAmount: $overrides['freeOverAmount'] ?? null,
        minOrderAmount: $overrides['minOrderAmount'] ?? null,
        maxOrderAmount: $overrides['maxOrderAmount'] ?? null,
        estimatedDaysMin: $overrides['estimatedDaysMin'] ?? 2,
        estimatedDaysMax: $overrides['estimatedDaysMax'] ?? 4,
        isActive: $overrides['isActive'] ?? true,
    );
}

it('returns no shipping options when no zone matches the address', function () {
    $service = new ShippingQuoteService(
        shippingFakeRepository(zone: null, rates: [])
    );

    $options = $service->getShippingOptions(
        new ShippingAddressEntity(
            country: 'Nigeria',
            state: 'Kano',
            city: 'Kano',
            postalCode: null,
        ),
        cartSubtotal: 15000.0,
    );

    expect($options)->toBe([]);
});

it('returns shipping options for all active rates in the resolved zone', function () {
    $zone = shippingZone();

    $standard = shippingMethod();
    $express = shippingMethod([
        'id' => 'method_express',
        'code' => 'express',
        'name' => 'Express Delivery',
    ]);

    $service = new ShippingQuoteService(
        shippingFakeRepository($zone, [
            shippingRate($zone, $standard, [
                'id' => 'rate_standard_lagos',
                'amount' => 2000.0,
            ]),
            shippingRate($zone, $express, [
                'id' => 'rate_express_lagos',
                'amount' => 4500.0,
                'estimatedDaysMin' => 1,
                'estimatedDaysMax' => 2,
            ]),
        ])
    );

    $options = $service->getShippingOptions(
        new ShippingAddressEntity('Nigeria', 'Lagos', 'Ikeja', null),
        cartSubtotal: 15000.0,
    );

    expect($options)->toHaveCount(2)
        ->and($options[0]->rateId)->toBe('rate_standard_lagos')
        ->and($options[0]->amount)->toBe(2000.0)
        ->and($options[0]->method->code)->toBe('standard')
        ->and($options[1]->rateId)->toBe('rate_express_lagos')
        ->and($options[1]->amount)->toBe(4500.0)
        ->and($options[1]->method->code)->toBe('express');
});

it('makes shipping free when cart subtotal reaches the rate free shipping threshold', function () {
    $zone = shippingZone();
    $method = shippingMethod();

    $service = new ShippingQuoteService(
        shippingFakeRepository($zone, [
            shippingRate($zone, $method, [
                'amount' => 2000.0,
                'freeOverAmount' => 50000.0,
            ]),
        ])
    );

    $options = $service->getShippingOptions(
        new ShippingAddressEntity('Nigeria', 'Lagos', 'Ikeja', null),
        cartSubtotal: 50000.0,
    );

    expect($options)->toHaveCount(1)
        ->and($options[0]->amount)->toBe(0.0);
});

it('filters out rates when the cart subtotal is outside the allowed order range', function () {
    $zone = shippingZone();
    $method = shippingMethod();

    $service = new ShippingQuoteService(
        shippingFakeRepository($zone, [
            shippingRate($zone, $method, [
                'id' => 'rate_too_expensive',
                'amount' => 2000.0,
                'minOrderAmount' => 10000.0,
                'maxOrderAmount' => 30000.0,
            ]),
            shippingRate($zone, $method, [
                'id' => 'rate_available',
                'amount' => 3500.0,
                'minOrderAmount' => 30000.0,
            ]),
        ])
    );

    $options = $service->getShippingOptions(
        new ShippingAddressEntity('Nigeria', 'Lagos', 'Ikeja', null),
        cartSubtotal: 40000.0,
    );

    expect($options)->toHaveCount(1)
        ->and($options[0]->rateId)->toBe('rate_available')
        ->and($options[0]->amount)->toBe(3500.0);
});

it('serializes shipping option labels as text instead of nested objects', function () {
    $zone = shippingZone();
    $method = shippingMethod();
    $option = (new ShippingQuoteService(
        shippingFakeRepository($zone, [shippingRate($zone, $method)])
    ))->getShippingOptions(
        new ShippingAddressEntity('Nigeria', 'Lagos', 'Ikeja', null),
        cartSubtotal: 15000.0,
    )[0];

    $payload = (new ShippingOptionResource($option))->resolve(request());

    expect($payload)
        ->method->toBe('Standard Delivery')
        ->method_code->toBe('standard')
        ->description->toBe('Standard delivery')
        ->zone->toBe('Lagos');
});
