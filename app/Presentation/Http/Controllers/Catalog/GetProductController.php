<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Services\CatalogService;
use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetProductController
{
    public function __construct(
        private CatalogService $catalogService,
        private readonly StorefrontContext $storefrontContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = new GetProductQuery(
            slug: (string) $request->route('slug'),
            storefrontRootSlug: $this->storefrontContext->rootCategorySlug(),
        );

        $product = $this->catalogService->getProduct($query);

        return ApiResponse::success(new ProductResource($product));
    }
}
