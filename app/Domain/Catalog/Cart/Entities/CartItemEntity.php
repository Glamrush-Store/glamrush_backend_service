<?php

namespace App\Domain\Catalog\Cart\Entities;

use Carbon\Carbon;

final class CartItemEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string $productId,
        public readonly ?string $productVariantId,
        public readonly ?string $sku,
        public readonly array $attributes,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $thumb,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly Carbon $expiresAt,
    ) {}
}
