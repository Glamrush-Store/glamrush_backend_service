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
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $sku,
        public readonly string $type,
        public readonly bool $isFeatured,
        public readonly int $sortOrder,
        public readonly ?vendorEntity $vendor,
        public readonly ?CategoryEntity $category,
        public readonly ?BrandEntity $brand,
        public readonly array $images = [],
        private float $price,
        private ?float $salePrice,
        private ?string $saleStartsAt,
        private ?string $saleEndsAt,
        private bool $manageStock,
        private int $stockQuantity,
        private bool $inStock,
        private array $variants = [],
    ) {
    }

    /* ------------------------------------------
     |  Aggregate Behavior
     -------------------------------------------*/

// correct
    public function effectivePrice(DateTimeImmutable $now): float
    {
        if (
            $this->salePrice &&
            $this->saleStartsAt &&
            $this->saleEndsAt &&
            $now >= new DateTimeImmutable($this->saleStartsAt) &&
            $now <= new DateTimeImmutable($this->saleEndsAt)
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

    public function defaultAttributes(): array
    {
        return $this->defaultVariant()?->attributes() ?? [];
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

        if (!$this->manageStock) {
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
}
