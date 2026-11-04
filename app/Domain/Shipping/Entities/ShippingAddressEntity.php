<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Entities;

final class ShippingAddressEntity
{
    public function __construct(
        public readonly string $country,
        public readonly ?string $state,
        public readonly ?string $city,
        public readonly ?string $postalCode,
    ) {
    }
}
