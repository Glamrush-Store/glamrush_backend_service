<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Brand\Contracts\BrandRepository;
use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Brand\Mappers\BrandDtoMapper;
use App\Domain\Catalog\Brand\Queries\GetBrandQuery;
use App\Domain\Catalog\Brand\Queries\ListBrandQuery;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\BrandMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Brand;
use Illuminate\Support\Collection;

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


    public function getBrands(ListBrandQuery $query): Collection
    {
        $builder = Brand::query()
            ->select([
                'id',
                'name',
                'slug',
            ]);

        if ($query->search) {
            $builder->where('name', 'like', "%{$query->search}%");
        }

        $brands = $builder->get();

        return BrandMapper::collection($brands);
    }


}
