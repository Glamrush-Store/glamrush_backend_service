<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Support\Media\SafeMediaUrl;

final class CartItemMapper
{
    public static function toDomain(CartItem $model): CartItemEntity
    {
        $product = $model->product;
        $variant = $model->variant;
        $variantMedia = $variant?->getFirstMedia('catalog-photos');
        $productMedia = $product->getFirstMedia('catalog-photos');
        $thumb = $variantMedia ? SafeMediaUrl::get($variantMedia, 'thumb') : '';

        if ($thumb === '' && $productMedia) {
            $thumb = SafeMediaUrl::get($productMedia, 'thumb');
        }

        if (! $variantMedia && ! $productMedia) {
            $thumb = null;
        }

        return new CartItemEntity(
            id: $model->id,
            productId: (string) $model->product_id,
            productVariantId: $variant ? (string) $variant->id : null,
            sku: $variant?->sku,
            attributes: $variant?->attributes ?? [],
            name: $product->name,
            slug: $product->slug,
            thumb: $thumb,
            quantity: $model->quantity,
            unitPrice: $variant ? self::resolveVariantPrice($variant) : self::resolveProductPrice($product),
            expiresAt: $model->expires_at,
        );
    }

    private static function resolveVariantPrice(ProductVariant $variant): float
    {
        $now = now();
        $saleStarted = $variant->sale_starts_at === null || $now->greaterThanOrEqualTo($variant->sale_starts_at);
        $saleNotEnded = $variant->sale_ends_at === null || $now->lessThanOrEqualTo($variant->sale_ends_at);

        if ($variant->sale_price !== null && $saleStarted && $saleNotEnded) {
            return (float) $variant->sale_price;
        }

        return (float) $variant->price;
    }

    private static function resolveProductPrice(Product $product): float
    {
        $now = now();

        if (
            $product->sale_price !== null &&
            $product->sale_starts_at !== null &&
            $product->sale_ends_at !== null &&
            $now->between($product->sale_starts_at, $product->sale_ends_at)
        ) {
            return (float) $product->sale_price;
        }

        return (float) $product->price;
    }
}
