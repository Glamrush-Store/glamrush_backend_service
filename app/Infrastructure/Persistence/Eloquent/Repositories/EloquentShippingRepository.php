<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;


use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Entities\ShippingRateEntity;
use App\Domain\Shipping\Entities\ShippingZoneEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\Shipping\ShippingRateMapper;
use App\Infrastructure\Persistence\Eloquent\Mappers\Shipping\ShippingZoneMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingRate;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;

class EloquentShippingRepository implements ShippingRepository
{

    public function getShippingOptions(ShippingAddressEntity $address)
    {
        return [];
    }

    public function findBestZoneForAddress(ShippingAddressEntity $address): ?ShippingZoneEntity
    {
        $model = ShippingZone::query()
            ->where('is_active', true)
            ->where('country', $address->country)
            ->where(function ($query) use ($address) {
                if ($address->state && $address->city) {
                    $query->orWhere(function ($q) use ($address) {
                        $q->where('state', $address->state)
                            ->where('city', $address->city);
                    });
                }

                if ($address->state) {
                    $query->orWhere(function ($q) use ($address) {
                        $q->where('state', $address->state)
                            ->whereNull('city');
                    });
                }

                $query->orWhere(function ($q) {
                    $q->whereNull('state')
                        ->whereNull('city');
                });
            })
            ->orderByRaw(
                "
            CASE
                WHEN state = ? AND city = ? THEN 1
                WHEN state = ? AND city IS NULL THEN 2
                WHEN state IS NULL AND city IS NULL THEN 3
                ELSE 4
            END
            ",
                [
                    $address->state,
                    $address->city,
                    $address->state,
                ]
            )
            ->first();

        return $model
            ? ShippingZoneMapper::toDomain($model)
            : null;
    }


    /**
     * @return ShippingRateEntity[]
     */
    public function getActiveRatesForZone(string $zoneId): array
    {
        return ShippingRate::query()
            ->where('shipping_zone_id', $zoneId)
            ->where('is_active', true)
            ->whereHas('method', fn($q) => $q->where('is_active', true))
            ->with('method')
            ->get()
            ->map(fn($rate) => ShippingRateMapper::toDomain($rate))
            ->all();
    }

}
