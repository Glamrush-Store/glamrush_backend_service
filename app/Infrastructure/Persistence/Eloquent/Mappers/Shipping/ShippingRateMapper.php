<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Infrastructure\Persistence\Eloquent\Mappers\Shipping;

use App\Domain\Shipping\Entities\ShippingMethodEntity;
use App\Domain\Shipping\Entities\ShippingRateEntity;
use App\Domain\Shipping\Entities\ShippingZoneEntity;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingRate;

final class ShippingRateMapper
{
    public static function toDomain(ShippingRate $model): ShippingRateEntity
    {
        return new ShippingRateEntity(
            id: (string)$model->id,
            shippingZone: self::mapZone($model),
            shippingMethod: self::mapMethod($model),
            rateType: $model->rate_type,
            amount: $model->amount,
            freeOverAmount: $model->free_over_amount,
            minOrderAmount: $model->min_order_amount,
            maxOrderAmount: $model->max_order_amount,
            estimatedDaysMin: $model->estimated_days_min,
            estimatedDaysMax: $model->estimated_days_max,
            isActive: $model->is_active
        );
    }

    private static function mapZone($model): ?ShippingZoneEntity
    {
        if (!$model->zone) {
            return null;
        }

        return new ShippingZoneEntity(
            id: (string)$model->zone->id,
            name: $model->zone->name,
            country: $model->zone->country,
            state: $model->zone->state,
            city: $model->zone->city,
            postalCode: $model->zone->postal_code_pattern
        );
    }

    private static function mapMethod($model): ?ShippingMethodEntity
    {
        if (!$model->relationLoaded('method') || !$model->method) {
            return null;
        }

        return new ShippingMethodEntity(
            id: (string)$model->method->id,
            code: (string)$model->method->name,
            name: $model->method->name,
            description: $model->method->description,
            is_active: (bool)$model->method->is_active,
        );
    }


}
