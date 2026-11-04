<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Infrastructure\Persistence\Eloquent\Mappers\Shipping;

use App\Domain\Shipping\Entities\ShippingZoneEntity;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;

final class ShippingZoneMapper
{
    public static function toDomain(ShippingZone $model): ShippingZoneEntity
    {
        return new ShippingZoneEntity(
            id: (string)$model->id,
            name: $model->name,
            country: $model->country,
            state: $model->state,
            city: $model->city,
            postalCode: $model->postal_code_pattern

        );
    }
}
