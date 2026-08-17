<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Category\Entities\CategoryEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Support\Media\SafeMediaUrl;
use Illuminate\Support\Collection;

final class CategoryMapper
{
    public static function collection(Collection $models): Collection
    {
        return $models->map(
            fn (Category $category) => self::toDomain($category)
        );
    }

    public static function toDomain(Category $model): CategoryEntity
    {
        $children = [];

        if ($model->relationLoaded('childrenRecursive')) {
            $children = self::collection(
                $model->childrenRecursive
            )->all();
        }

        $media = $model->getFirstMedia('catalog-photos');

        return new CategoryEntity(
            id: (string) $model->id,
            name: $model->name,
            slug: $model->slug,
            children: $children,
            images: $media ? [
                'url' => SafeMediaUrl::get($media),
                'thumb' => SafeMediaUrl::get($media, 'thumb'),
                'medium' => SafeMediaUrl::get($media, 'medium'),
            ] : [],
        );
    }
}
