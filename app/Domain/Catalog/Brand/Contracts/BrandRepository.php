<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Brand\Contracts;


use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Brand\Queries\GetBrandQuery;
use App\Domain\Catalog\Brand\Queries\ListBrandQuery;
use Illuminate\Support\Collection;

interface BrandRepository
{

    public function getBrandBySlug(GetBrandQuery $query): BrandDto;

    /**
     * @return Collection<int, BrandDto>
     */
    public function getBrands(ListBrandQuery $query): Collection;
}
