<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Entities;


final class ShippingOptionEntity
{
    public function __construct(
        public readonly string $rateId,
        public readonly ShippingMethodEntity $method,
        public readonly ShippingZoneEntity $zone,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?int $estimatedDaysMin,
        public readonly ?int $estimatedDaysMax,
    ) {
    }
}
