<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Category\Queries\ListCategoryQuery;
use App\Domain\Catalog\Category\Services\CategoryService;
use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Presentation\Http\Resources\Catalog\CategoryResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListCategoryController
{
    public function __construct(
        private CategoryService $categoryService,
        private readonly StorefrontContext $storefrontContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = new ListCategoryQuery(
            deep: ! $request->filled('deep') || $request->boolean('deep'),
            storefrontRootSlug: $this->storefrontContext->rootCategorySlug(),
        );

        $category = $this->categoryService->getCategoryList($query);

        return ApiResponse::success(CategoryResource::collection($category));
    }
}
