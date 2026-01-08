<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent;


use App\Features\Category\Contracts\CategoryRepository;
use App\Features\Category\DTOs\CategoryDto;
use App\Features\Category\Mappers\CategoryDtoMapper;
use App\Features\Category\Queries\GetCategoryQuery;
use App\Features\Category\Queries\ListCategoryQuery;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function PaginateCategoryList(ListCategoryQuery $query, int $perPage = 15): LengthAwarePaginator
    {
        return QueryCache::remember(
            $query,
            fn() => $this->getCategoryList($query)
        );
    }

    public function getCategoryList(ListCategoryQuery $query): LengthAwarePaginator
    {
        $builder = Category::query()
            ->select(
                [
                    'id',
                    'name',
                    'slug',
                    'parent_id',
                    'description',
                    'meta_title',
                    'meta_description',
                    'sort_order',
                    'is_active'
                ]
            );

        if ($query->parent) {
            $builder->where('parent_id', $query->parent);
        }

        return $builder->paginate($query->perPage)
            ->through(fn(Category $category) => CategoryDtoMapper::fromModel($category));
    }


}
