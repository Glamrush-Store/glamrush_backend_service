<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Category\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'parentCategory')]
final class CategoryParentDto
{

    public function __construct(

        #[OA\Property(example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F')]
        public ?string $id,

        #[OA\Property(example: 'Skin Care')]
        public ?string $name,

        #[OA\Property(example: 'skin-care')]
        public ?string $slug
    ) {
    }

}
