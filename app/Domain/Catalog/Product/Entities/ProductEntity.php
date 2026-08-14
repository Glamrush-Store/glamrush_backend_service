<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Entities;

use App\Domain\Catalog\Brand\Entities\BrandEntity;
use App\Domain\Catalog\Category\Entities\CategoryEntity;
use App\Domain\Catalog\Vendor\Entities\VendorEntity;
use DateTimeImmutable;

final class ProductEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $sku,
        public readonly string $type,
        public readonly bool $isFeatured,
        public readonly int $sortOrder,
        public readonly ?vendorEntity $vendor,
        public readonly ?CategoryEntity $category,
        public readonly ?BrandEntity $brand,
        public readonly array $images,
        public float $price,
        private ?float $salePrice,
        private ?string $saleStartsAt,
        private ?string $saleEndsAt,
        private bool $manageStock,
        public int $stockQuantity,
        private bool $inStock,
        private array $variants = [],
        public readonly array $categories = [],
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
    ) {}

    /* ------------------------------------------
     |  Aggregate Behavior
     -------------------------------------------*/

    public function effectivePrice(DateTimeImmutable $now): float
    {
        return $this->currentSalePrice($now) ?? $this->originalPrice();
    }

    public function originalPrice(): float
    {
        return $this->isVariable() && $this->defaultVariant()
            ? $this->defaultVariant()->price
            : $this->price;
    }

    public function currentSalePrice(DateTimeImmutable $now): ?float
    {
        if ($this->isVariable()) {
            return $this->defaultVariant()?->currentSalePrice($now);
        }

        if (
            $this->salePrice !== null &&
            $this->salePrice > 0 &&
            $this->salePrice < $this->price &&
            $this->saleStartsAt &&
            $this->saleEndsAt &&
            $now >= new DateTimeImmutable($this->saleStartsAt) &&
            $now <= new DateTimeImmutable($this->saleEndsAt)
        ) {
            return $this->salePrice;
        }

        return null;
    }

    public function isOnSale(DateTimeImmutable $now): bool
    {
        return $this->currentSalePrice($now) !== null;
    }

    public function defaultAttributes(): array
    {
        return $this->defaultVariant()?->attributes() ?? [];
    }

    public function displayDefaultAttributes(): array
    {
        if (! $this->isVariable()) {
            return [];
        }

        return $this->defaultAttributes();
    }

    public function defaultVariant(): ?ProductVariantEntity
    {
        foreach ($this->variants as $variant) {
            if ($variant->isDefault()) {
                return $variant;
            }
        }

        return null;
    }

    public function isAvailable(): bool
    {
        if ($this->isVariable()) {
            foreach ($this->variants as $variant) {
                if ($variant->isAvailable()) {
                    return true;
                }
            }

            return false;
        }

        if (! $this->manageStock) {
            return true;
        }

        return $this->inStock && $this->stockQuantity > 0;
    }

    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    public function variants(): array
    {
        return $this->variants;
    }

    public function displayVariants(): array
    {
        if (! $this->isVariable()) {
            return [];
        }

        return $this->variants;
    }
}
