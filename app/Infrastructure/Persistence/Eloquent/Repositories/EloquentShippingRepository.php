<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Location\Services\LocationService;
use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Entities\ShippingRateEntity;
use App\Domain\Shipping\Entities\ShippingZoneEntity;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Mappers\Shipping\ShippingRateMapper;
use App\Infrastructure\Persistence\Eloquent\Mappers\Shipping\ShippingZoneMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingRate;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;

class EloquentShippingRepository implements ShippingRepository
{
    public function __construct(private readonly LocationService $locations) {}

    public function findBestZoneForAddress(ShippingAddressEntity $address): ?ShippingZoneEntity
    {
        $key = 'shipping:zone:'.md5(json_encode([
            'country' => trim($address->country),
            'state' => $address->state !== null ? trim($address->state) : null,
            'city' => $address->city !== null ? trim($address->city) : null,
            'postal_code' => $address->postalCode !== null ? trim($address->postalCode) : null,
        ]));

        return QueryCache::rememberTagged(
            $key,
            [CacheTags::SHIPPING],
            (int) config('api_cache.shipping_ttl', 300),
            function () use ($address): ?ShippingZoneEntity {
                $countries = $this->locations->countryIdentifiers($address->country);
                $states = $this->locations->stateIdentifiers($address->country, $address->state);

                $model = ShippingZone::query()
                    ->where('is_active', true)
                    ->whereIn('country', $countries)
                    ->where(function ($query) use ($address, $states) {
                        if ($address->state && $address->city) {
                            $query->orWhere(function ($q) use ($address, $states) {
                                $q->whereIn('state', $states)
                                    ->where('city', $address->city);
                            });
                        }

                        if ($address->state) {
                            $query->orWhere(function ($q) use ($states) {
                                $q->whereIn('state', $states)
                                    ->whereNull('city');
                            });
                        }

                        $query->orWhere(function ($q) {
                            $q->whereNull('state')
                                ->whereNull('city');
                        });
                    })
                    ->orderByRaw('CASE WHEN city = ? THEN 1 WHEN state IS NOT NULL THEN 2 ELSE 3 END', [$address->city])
                    ->first();

                return $model ? ShippingZoneMapper::toDomain($model) : null;
            },
        );
    }

    /**
     * @return ShippingRateEntity[]
     */
    public function getActiveRatesForZone(string $zoneId): array
    {
        return QueryCache::rememberTagged(
            "shipping:zone:{$zoneId}:active-rates:v1",
            [CacheTags::SHIPPING],
            (int) config('api_cache.shipping_ttl', 300),
            fn (): array => ShippingRate::query()
                ->where('shipping_zone_id', $zoneId)
                ->where('is_active', true)
                ->whereHas('method', fn ($q) => $q->where('is_active', true))
                ->with(['method', 'zone'])
                ->get()
                ->map(fn ($rate) => ShippingRateMapper::toDomain($rate))
                ->all(),
        );
    }
}
