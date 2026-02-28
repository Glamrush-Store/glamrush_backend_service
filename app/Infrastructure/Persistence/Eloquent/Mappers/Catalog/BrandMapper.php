<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Brand\Entities\BrandEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Brand;
use Illuminate\Support\Collection;

final class BrandMapper
{


    public static function collection(Collection $models): Collection
    {
        return $models->map(
            fn(Brand $brand) => self::toDomain($brand)
        );
    }

    public static function toDomain(Brand $model): BrandEntity
    {
        return new BrandEntity(
            id: (string)$model->id,
            name: $model->name,
            slug: $model->slug,
        );
    }

}
