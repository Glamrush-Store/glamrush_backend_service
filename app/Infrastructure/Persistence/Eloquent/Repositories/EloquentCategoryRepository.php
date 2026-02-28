<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;


use App\Domain\Catalog\Category\Contracts\CategoryRepository;
use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Category\Mappers\CategoryDtoMapper;
use App\Domain\Catalog\Category\Queries\GetCategoryQuery;
use App\Domain\Catalog\Category\Queries\ListCategoryQuery;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\CategoryMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use Illuminate\Support\Collection;

class EloquentCategoryRepository implements CategoryRepository
{

    public function getCategoryBySlug(GetCategoryQuery $query): CategoryDto
    {
        return QueryCache::remember(
            $query,
            function () use ($query) {
                $category = Category::where('slug', $query->slug)->firstOrFail();
                return CategoryDtoMapper::fromModel($category);
            }
        );
    }


    public function getCategories(ListCategoryQuery $query): Collection
    {
        $builder = Category::query();
//            ->select([
//                'id',
//                'name',
//                'slug',
//                'parent_id',
//                'description',
//                'meta_title',
//                'meta_description',
//                'sort_order',
//                'is_active',
//            ]);

        $builder->with(['media', 'childrenRecursive.media']);


        if ($query->deep) {
            $builder->with('childrenRecursive')
                ->whereNull('parent_id');
        } else {
            $builder->whereNull('parent_id');
        }

        $categories = $builder->get();


        return CategoryMapper::collection($categories);
    }


}
