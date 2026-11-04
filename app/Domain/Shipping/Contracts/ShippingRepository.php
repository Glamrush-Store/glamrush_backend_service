<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Entities\ShippingZoneEntity;

interface ShippingRepository
{
    public function findBestZoneForAddress(ShippingAddressEntity $address): null|ShippingZoneEntity;

    public function getActiveRatesForZone(string $zoneId): array;


}
