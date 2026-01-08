<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent;

use App\Features\Brand\Contracts\BrandRepository;
use App\Features\Brand\DTOs\BrandDto;
use App\Features\Brand\Mappers\BrandDtoMapper;
use App\Features\Brand\Queries\GetBrandQuery;
use App\Features\Brand\Queries\ListBrandQuery;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentBrandRepository implements BrandRepository
{


    public function getBrandBySlug(GetBrandQuery $query): BrandDto
    {
        return QueryCache::remember(
            $query,
            function () use ($query) {
                $brand = Brand::where('slug', $query->slug)->firstOrFail();
                return BrandDtoMapper::fromModel($brand);
            }
        );
    }

    public function PaginateBrandList(ListBrandQuery $query): LengthAwarePaginator
    {
        return QueryCache::remember(
            $query,
            fn() => $this->getBrandList($query)
        );
    }

    public function getBrandList(ListBrandQuery $query)
    {
        $builder = Brand::query();

        if ($query->search) {
            $builder->where('name', 'like', "%{$query->search}%");
        }
        return $builder->paginate($query->perPage)
            ->through(fn(Brand $brand) => BrandDtoMapper::fromModel($brand));
    }

}
