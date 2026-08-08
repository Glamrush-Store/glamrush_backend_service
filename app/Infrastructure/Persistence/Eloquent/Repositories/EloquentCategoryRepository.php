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
                ->when(
                    $query->storefrontRootSlug,
                    fn ($categoryQuery, string $slug) => $categoryQuery
                        ->where('slug', $slug)
                        ->where('is_active', true),
                    fn ($categoryQuery) => $categoryQuery->whereNull('parent_id'),
                );
        } else {
            $builder->when(
                $query->storefrontRootSlug,
                fn ($categoryQuery, string $slug) => $categoryQuery
                    ->where('slug', $slug)
                    ->where('is_active', true),
                fn ($categoryQuery) => $categoryQuery->whereNull('parent_id'),
            );
        }

        $categories = $builder->get();

        if ($query->storefrontRootSlug) {
            $categories->each(fn (Category $category) => $this->removeInactiveDescendants($category));
        }

        return CategoryMapper::collection($categories);
    }

    private function removeInactiveDescendants(Category $category): void
    {
        if (! $category->relationLoaded('childrenRecursive')) {
            return;
        }

        $activeChildren = $category->childrenRecursive
            ->filter(fn (Category $child) => $child->is_active)
            ->values();

        $activeChildren->each(fn (Category $child) => $this->removeInactiveDescendants($child));
        $category->setRelation('childrenRecursive', $activeChildren);
    }
}
