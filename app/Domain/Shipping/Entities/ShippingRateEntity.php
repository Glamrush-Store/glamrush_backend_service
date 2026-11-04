<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Entities;


final class ShippingRateEntity
{

    public function __construct(
        public readonly string $id,
        public readonly ShippingZoneEntity $shippingZone,
        public readonly ShippingMethodEntity $shippingMethod,
        public readonly string $rateType,
        public readonly float $amount,
        public readonly ?float $freeOverAmount,
        public readonly ?float $minOrderAmount,
        public readonly ?float $maxOrderAmount,
        public readonly ?int $estimatedDaysMin,
        public readonly ?int $estimatedDaysMax,
        public readonly int $isActive,
    ) {
    }

}
