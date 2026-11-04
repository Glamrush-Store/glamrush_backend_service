<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Domain\Catalog\Category\Services;

use App\Domain\Catalog\Category\Contracts\CategoryRepository;
use App\Domain\Catalog\Category\Queries\ListCategoryQuery;
use App\Infrastructure\Caching\QueryCache;
use Illuminate\Support\Collection;

final class CategoryService
{

    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }


    public function getCategoryList(
        ListCategoryQuery $query
    ): Collection {
        return QueryCache::remember($query, function () use ($query) {
            return $this->categoryRepository->getCategories($query);
        });
    }

}
