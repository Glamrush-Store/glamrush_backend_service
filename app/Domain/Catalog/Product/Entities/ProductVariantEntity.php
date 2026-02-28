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
        public readonly array $images = [],
        public readonly bool $isDefault = false,
        public float $price,
        public ?float $salePrice,
        private ?DateTimeImmutable $saleStartsAt,
        private ?DateTimeImmutable $saleEndsAt,
        private bool $manageStock,
        private int $stockQuantity,
        private bool $inStock,
        private array $attributes,
        private int $sortOrder,
        private string $status,
    ) {
    }

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
        if (
            $this->salePrice !== null &&
            $this->saleStartsAt !== null &&
            $this->saleEndsAt !== null &&
            $now >= $this->saleStartsAt &&
            $now <= $this->saleEndsAt
        ) {
            return $this->salePrice;
        }

        return $this->price;
    }

    public function isOnSale(DateTimeImmutable $now): bool
    {
        if ($this->salePrice &&
            $this->saleStartsAt &&
            $this->saleEndsAt &&
            now()->between($this->saleStartsAt, $this->saleEndsAt)) {
            return true;
        }

        return false;
    }

    public function isAvailable(): bool
    {
        if (!$this->manageStock) {
            return true;
        }

        return $this->inStock && $this->stockQuantity > 0;
    }
}
