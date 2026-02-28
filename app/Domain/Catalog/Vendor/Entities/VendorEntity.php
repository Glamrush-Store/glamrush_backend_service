<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Domain\Catalog\Vendor\Entities;

final class VendorEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $business_name,
    ) {
    }
}
