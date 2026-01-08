<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Application\UseCases\Catalog\GetProduct;

use App\Domain\Shared\ValueObjects\Uuid;

final class GetProductQuery
{
    public function __construct(
        public readonly int $productId
    ) {}
}
