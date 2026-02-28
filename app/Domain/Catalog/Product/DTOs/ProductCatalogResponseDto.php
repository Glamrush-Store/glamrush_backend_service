<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\DTOs;

use App\Shared\DTOs\PaginationMetaDto;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProductCatalogResponse')]
final class ProductCatalogResponseDto
{
    /**
     * @param ProductCatalogDto[] $data
     */
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductCatalog')
        )]
        public array $data,

        #[OA\Property(ref: '#/components/schemas/PaginationMeta')]
        public PaginationMetaDto $meta,
    ) {
    }
}
