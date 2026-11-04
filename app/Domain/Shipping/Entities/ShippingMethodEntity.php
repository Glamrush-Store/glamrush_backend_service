<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Entities;

final class ShippingMethodEntity
{

    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $description,
        public readonly string $is_active,
    ) {
    }
}
