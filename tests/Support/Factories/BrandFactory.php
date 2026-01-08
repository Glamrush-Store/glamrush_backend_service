<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace Tests\Support\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Brand;

final class BrandFactory
{
    public static function make(array $overrides = []): Brand
    {
        return new Brand(array_merge([
            'id' => '01KDBCVNGJKJJNMD7JDFT78H2D',
            'name' => 'Brand',
            'slug' => 'brand',
            'description' => 'a suitable description',
            'meta_title' => 'A title',
            'meta_description' => 'a long description',
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }
}
