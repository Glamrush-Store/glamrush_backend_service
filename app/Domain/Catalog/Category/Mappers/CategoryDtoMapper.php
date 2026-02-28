<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Category\Mappers;


use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Category\DTOs\CategoryParentDto;

class CategoryDtoMapper
{

    public static function fromModel($category)
    {
        return new CategoryDto(
            id: $category->id,
            name: $category->name,
            parent: new CategoryParentDto(
                id: $category->parent_id ?? null,
                name: $category->parent->name ?? null,
                slug: $category->parent->slug ?? null
            ),
            slug: $category->slug,
            description: $category->description ?? null,
            metaTitle: $category->meta_title ?? null,
            metaDescription: $category->meta_description ?? null,
            sortOrder: $category->sort_order ?? null,
            isActive: $category->is_active
        );
    }


}
