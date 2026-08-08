<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Entities;

use DateTimeImmutable;

final class ProductVariantEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $sku,
        public readonly array $images,
        public readonly bool $isDefault,
        public float $price,
        public ?float $salePrice,
        private ?DateTimeImmutable $saleStartsAt,
        private ?DateTimeImmutable $saleEndsAt,
        private bool $manageStock,
        public int $stockQuantity,
        private bool $inStock,
        private array $attributes,
        private int $sortOrder,
        private string $status,
    ) {}

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function effectivePrice(DateTimeImmutable $now): float
    {
        return $this->currentSalePrice($now) ?? $this->price;
    }

    public function currentSalePrice(DateTimeImmutable $now): ?float
    {
        if (
            $this->salePrice !== null &&
            $this->salePrice > 0 &&
            $this->salePrice < $this->price &&
            $this->saleStartsAt !== null &&
            $this->saleEndsAt !== null &&
            $now >= $this->saleStartsAt &&
            $now <= $this->saleEndsAt
        ) {
            return $this->salePrice;
        }

        return null;
    }

    public function isOnSale(DateTimeImmutable $now): bool
    {
        return $this->currentSalePrice($now) !== null;
    }

    public function isAvailable(): bool
    {
        if (! in_array($this->status, ['active', 'published'], true)) {
            return false;
        }

        if (! $this->manageStock) {
            return true;
        }

        return $this->inStock && $this->stockQuantity > 0;
    }
}
