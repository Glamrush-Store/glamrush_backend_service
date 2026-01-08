<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Shared\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PaginationMeta')]
final class PaginationMetaDto
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $currentPage,

        #[OA\Property(example: 20)]
        public int $perPage,

        #[OA\Property(example: 120)]
        public int $total,
    ) {
    }
}
