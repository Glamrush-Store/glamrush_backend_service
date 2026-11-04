<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Category\Contracts;


use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Category\Queries\GetCategoryQuery;
use App\Domain\Catalog\Category\Queries\ListCategoryQuery;
use Illuminate\Support\Collection;

interface CategoryRepository
{

    public function getCategoryBySlug(GetCategoryQuery $query): CategoryDto;

    /**
     * @return Collection<int, CategoryDto>
     */
    public function GetCategories(ListCategoryQuery $query): Collection;

}
