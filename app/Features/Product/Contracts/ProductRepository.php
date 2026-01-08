<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Product\Contracts;

use App\Features\Product\DTOs\ProductDetailDto;
use App\Features\Product\Queries\GetProductQuery;
use App\Features\Product\Queries\ListProductsQuery;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    public function getProductDetails(GetProductQuery $query): productDetailDto;


    public function paginateForCatalog(
        ListProductsQuery $query,
        int $perPage = 20
    ): LengthAwarePaginator;
}
