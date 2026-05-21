<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Product\Queries\GetProductQuery;
use App\Domain\Catalog\Product\Services\CatalogService;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetProductController
{

    public function __construct(
        private CatalogService $catalogService,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $query = new GetProductQuery(
            slug: $slug
        );


        $product = $this->catalogService->getProduct($query);

        return ApiResponse::success(new ProductResource($product));
    }
}
