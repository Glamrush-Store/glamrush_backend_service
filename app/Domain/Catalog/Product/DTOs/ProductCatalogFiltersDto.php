<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProductCatalogFilters')]
final class ProductCatalogFiltersDto
{
    #[OA\Property(
        example: 'bathing salts',
        description: 'Search across product name and slug'
    )]
    public ?string $search;
}
