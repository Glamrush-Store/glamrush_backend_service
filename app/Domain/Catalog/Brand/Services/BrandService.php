<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Domain\Catalog\Brand\Services;

use App\Domain\Catalog\Brand\Contracts\BrandRepository;
use App\Domain\Catalog\Brand\Queries\ListBrandQuery;
use App\Infrastructure\Caching\QueryCache;
use Illuminate\Support\Collection;

final class BrandService
{

    public function __construct(
        private BrandRepository $brandRepository
    ) {
    }


    public function getBrandList(
        ListBrandQuery $query
    ): Collection {
        return QueryCache::remember($query, function () use ($query) {
            return $this->brandRepository->getBrands($query);
        });
    }

}
