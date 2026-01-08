<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace Tests\Support\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Category;

final class CategoryFactory
{
    public static function parent(array $overrides = []): Category
    {
        return new Category(array_merge([
            'id' => '01KDBCVNGJKJJN343JDFT78H2D',
            'name' => 'Fragrance',
            'slug' => 'fragrance',
        ], $overrides));
    }

    public static function childOf(Category $parent, array $overrides = []): Category
    {
        $category = self::make($overrides);

        $category->setRelation('parent', $parent);

        return $category;
    }

    public static function make(array $overrides = []): Category
    {
        return new Category(array_merge([
            'id' => '01KDBCVNGJKJJNMD7JDFT78H2D',
            'name' => 'Perfumes',
            'slug' => 'perfume',
            'description' => 'a very long description',
            'meta_title' => 'a short title',
            'meta_description' => 'a short description',
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }
}
