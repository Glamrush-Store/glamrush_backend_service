<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Contracts;

use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    public function findBySlug(GetProductQuery $query): ?ProductEntity;

    public function paginate(ListProductsQuery $query): LengthAwarePaginator;

    public function getFacets(ListProductsQuery $query): array;
}
