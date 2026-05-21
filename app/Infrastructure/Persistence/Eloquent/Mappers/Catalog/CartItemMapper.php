<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;

final class CartItemMapper
{
    public static function toDomain(CartItem $model): CartItemEntity
    {
        $product = $model->product;
        $thumb = $product->getFirstMediaUrl('catalog-photos', 'thumb');
        $unitPrice = self::resolvePrice($product);

        return new CartItemEntity(
            id: $model->id,
            productId: (string) $model->product_id,
            name: $product->name,
            slug: $product->slug,
            thumb: $thumb !== '' ? $thumb : null,
            quantity: $model->quantity,
            unitPrice: $unitPrice,
            expiresAt: $model->expires_at,
        );
    }

    private static function resolvePrice(\App\Infrastructure\Persistence\Eloquent\Models\Product $product): float
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
