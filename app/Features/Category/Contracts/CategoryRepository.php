<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Category\Contracts;


use App\Features\Category\DTOs\CategoryDto;
use App\Features\Category\Queries\GetCategoryQuery;
use App\Features\Category\Queries\ListCategoryQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryRepository
{

    public function getCategoryBySlug(GetCategoryQuery $query): CategoryDto;

    /**
     * @return Collection<int, CategoryDto>
     */
    public function PaginateCategoryList(ListCategoryQuery $query, int $perPage = 15): LengthAwarePaginator;

}
