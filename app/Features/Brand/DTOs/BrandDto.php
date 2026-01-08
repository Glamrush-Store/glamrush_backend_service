<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand\DTOs;

use OpenApi\Attributes as OA;


#[OA\Schema(schema: 'Brands')]
final class BrandDto
{

    public function __construct(

        #[OA\Property(
            example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F'
        )]
        public readonly string $id,

        #[OA\Property(
            example: 'St Ive\'s'
        )]
        public readonly string $name,

        #[OA\Property(
            example: 'st-ives'
        )]
        public readonly string $slug,

        #[OA\Property(
            example: 'A long lipsium ipsium description'
        )]
        public readonly ?string $description,

        #[OA\Property(
            example: 'A long lipsium ipsium description'
        )]
        public readonly ?string $metaTitle,

        #[OA\Property(
            example: 'A long lipsium ipsium description'
        )]
        public readonly ?string $metaDescription,

        #[OA\Property(
            example: 4
        )]
        public readonly ?int $sortOrder,

        #[OA\Property(
            example: true
        )]
        public readonly bool $isActive


    ) {
    }

}
