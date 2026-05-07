<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\SavedItem\Entities\SavedItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\SavedItem;

final class SavedItemMapper
{
    public static function toDomain(SavedItem $model): SavedItemEntity
    {
        $thumb = $model->product->getFirstMediaUrl('catalog-photos', 'thumb');

        return new SavedItemEntity(
            id: $model->id,
            productId: (string) $model->product_id,
            name: $model->product->name,
            slug: $model->product->slug,
            thumb: $thumb !== '' ? $thumb : null,
        );
    }
}
