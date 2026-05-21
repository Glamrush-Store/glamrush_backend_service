<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Vendor\DTOs;

use OpenApi\Attributes as OA;


#[OA\Schema(schema: 'vendors')]
final class VendorDto
{

    public function __construct(

        #[OA\Property(
            example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F'
        )]
        public readonly string $id,

        #[OA\Property(
            example: 'Skin Care'
        )]
        public readonly string $name,

        #[OA\Property(
            example: 'Glow Enterprise'
        )]
        public readonly string $business_name
    ) {
    }


}
