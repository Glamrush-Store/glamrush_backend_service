<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Category\Entities;

final class CategoryEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public array $children = [],
        public array $images = []
    ) {
    }
}
