<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Product\DTOs;

use App\Shared\DTOs\PriceDto;
use DateTime;
use OpenApi\Attributes as OA;


#[OA\Schema(schema: 'Variants')]
final class ProductVariantDto
{
    /**
     * @param ProductVariantDto[] $variants
     */
    public function __construct(

        #[OA\Property(example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F')]
        public readonly string $id,

        #[OA\Property(example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F')]
        public readonly string $productId,


        #[OA\Property(example: 'FACE-SCRUB-LEM-099009')]
        public readonly string $sku,

        #[OA\Property(
            example: 'false'
        )]
        public readonly bool $isDefault,

        #[OA\Property(ref: '#/components/schemas/Price')]
        public readonly ?PriceDto $price,

        #[OA\Property(ref: '#/components/schemas/Price')]
        public readonly ?PriceDto $salePrice,

        #[OA\Property(example: '2025-01-01T00:00:00.000000Z')]
        public readonly ?DateTime $saleStartsAt,

        #[OA\Property(example: '2025-03-01T00:00:00.000000Z')]
        public readonly ?DateTime $saleEndsAt,

        #[OA\Property(example: true)]
        public readonly bool $manageStock,

        #[OA\Property(example: 10)]
        public readonly int $stockQuantity,

        #[OA\Property(example: true)]
        public readonly bool $inStock,

        #[OA\Property(
            example: ['color' => 'red', 'size' => 'large']
        )]
        public readonly array $variantAttributes,

        #[OA\Property(example: 1)]
        public readonly int $sortOrder,

        #[OA\Property(example: 'active')]
        public readonly string $status

    ) {
    }


}
