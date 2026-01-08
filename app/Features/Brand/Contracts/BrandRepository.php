<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand\Contracts;


use App\Features\Brand\DTOs\BrandDto;
use App\Features\Brand\Queries\GetBrandQuery;
use App\Features\Brand\Queries\ListBrandQuery;
use Illuminate\Pagination\LengthAwarePaginator;

interface BrandRepository
{

    public function getBrandBySlug(GetBrandQuery $query): BrandDto;

    public function paginateBrandList(ListBrandQuery $query): LengthAwarePaginator;
}
