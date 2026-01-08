<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Shared\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Price')]
final class PriceDto
{
    public function __construct(

        #[OA\Property(example: 1000)]
        public readonly int $amount,

        #[OA\Property(example: 'NGN')]
        public readonly string $currency,

        #[OA\Property(example: '800')]
        public readonly ?int $saleAmount,

        #[OA\Property(example: 'true')]
        public readonly bool $onSale,
    ) {
    }
}
