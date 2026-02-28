<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Contracts;

use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    public function findBySlug(string $slug): ?ProductEntity;

    public function paginate(ListProductsQuery $query): LengthAwarePaginator;

}
