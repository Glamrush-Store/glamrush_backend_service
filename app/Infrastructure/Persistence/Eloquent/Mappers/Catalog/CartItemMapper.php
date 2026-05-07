<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;

final class CartItemMapper
{
    public static function toDomain(CartItem $model): CartItemEntity
    {
        $thumb = $model->product->getFirstMediaUrl('catalog-photos', 'thumb');

        return new CartItemEntity(
            id: $model->id,
            productId: (string) $model->product_id,
            name: $model->product->name,
            slug: $model->product->slug,
            thumb: $thumb !== '' ? $thumb : null,
            quantity: $model->quantity,
            expiresAt: $model->expires_at,
        );
    }
}
