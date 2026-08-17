<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\SavedItem\Entities\SavedItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\SavedItem;
use App\Support\Media\SafeMediaUrl;

final class SavedItemMapper
{
    public static function toDomain(SavedItem $model): SavedItemEntity
    {
        $media = $model->product->getFirstMedia('catalog-photos');
        $thumb = $media ? SafeMediaUrl::get($media, 'thumb') : null;

        return new SavedItemEntity(
            id: $model->id,
            productId: (string) $model->product_id,
            name: $model->product->name,
            slug: $model->product->slug,
            thumb: $thumb,
        );
    }
}
