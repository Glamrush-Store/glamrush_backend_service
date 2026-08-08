<?php

namespace App\Domain\Order\Entities;

final class OrderItemEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly string $productId,
        public readonly ?string $productVariantId,
        public readonly string $productName,
        public readonly string $productSlug,
        public readonly string $sku,
        public readonly float $unitPrice,
        public readonly int $quantity,
        public readonly string $lineSubtotal,
        public readonly string $discountAmount,
        public readonly string $lineTotal,
        public readonly ?array $productSnapshot,
    ) {
    }
}
