<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Mappers;

use App\Domain\Catalog\Product\DTOs\ProductVariantDto;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Shared\DTOs\PriceDto;

final class ProductVariantMapper
{
    public static function fromModel(ProductVariant $variant): ProductVariantDto
    {
        return new ProductVariantDto(
            id: (string)$variant->id,
            productId: (string)$variant->product_id,
            sku: $variant->sku,
            isDefault: (bool)$variant->is_default,

            price: self::mapPrice($variant),
            salePrice: self::mapSalePrice($variant),

            saleStartsAt: $variant->sale_starts_at,
            saleEndsAt: $variant->sale_ends_at,

            manageStock: (bool)$variant->manage_stock,
            stockQuantity: (int)$variant->stock_quantity,
            inStock: self::isInStock($variant),

            variantAttributes: $variant->attributes ?? [],
            sortOrder: (int)$variant->sort_order,
            status: $variant->status
        );
    }

    private static function mapPrice(ProductVariant $variant): PriceDto
    {
        return new PriceDto(
            amount: (int)$variant->price,
            currency: 'NGN',
            saleAmount: $variant->sale_price,
            onSale: self::isOnSale($variant)
        );
    }

    private static function isOnSale(ProductVariant $variant): bool
    {
        if ($variant->sale_price === null) {
            return false;
        }

        $now = now();

        if ($variant->sale_starts_at && $variant->sale_starts_at->isFuture()) {
            return false;
        }

        if ($variant->sale_ends_at && $variant->sale_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    private static function mapSalePrice(ProductVariant $variant): ?PriceDto
    {
        if (!self::isOnSale($variant)) {
            return null;
        }

        return new PriceDto(
            amount: (int)$variant->sale_price,
            currency: 'NGN',
            saleAmount: null,
            onSale: true
        );
    }

    private static function isInStock(ProductVariant $variant): bool
    {
        if (!$variant->manage_stock) {
            return true;
        }

        return $variant->stock_quantity > 0;
    }
}
