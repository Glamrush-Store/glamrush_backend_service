<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand\Mappers;

use App\Features\Brand\DTOs\BrandDto;

final class BrandDtoMapper
{

    public static function fromModel($brand)
    {
        return new BrandDto(
            id: $brand->id,
            name: $brand->name,
            slug: $brand->slug,
            description: $brand->description,
            metaTitle: $brand->meta_title,
            metaDescription: $brand->meta_description,
            sortOrder: $brand->sort_order,
            isActive: $brand->is_active,
        );
    }

}
