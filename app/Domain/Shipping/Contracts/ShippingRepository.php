<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\Entities\ShippingAddressEntity;

interface ShippingRepository
{
    public function getShippingOptions(ShippingAddressEntity $address);

    public function findBestZoneForAddress(ShippingAddressEntity $address);

    public function getActiveRatesForZone(string $zoneId);


}
