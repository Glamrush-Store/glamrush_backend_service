<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Order\Entities;


final class CreateOrderItemEntity
{
    public function __construct(
        public readonly string $productId,
        public readonly ?string $productVariantId,
        public readonly string $productName,
        public readonly string $productSlug,
        public readonly ?string $sku,
        public readonly float $unitPrice,
        public readonly int $quantity,
        public readonly array $productSnapshot,
    ) {
    }
}
