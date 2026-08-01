<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Product\Queries\ListProductsQuery;
use App\Domain\Catalog\Product\Services\CatalogService;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListProductController
{
    public function __construct(
        private CatalogService $catalogService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $collectionSlug = $request->route('collection') ?: (
            $request->filled('collection') ? (string) $request->string('collection') : null
        );

        $query = new ListProductsQuery(
            categorySlug: $request->filled('category') ? (string) $request->string('category') : null,
            brandSlug: $request->filled('brand') ? (string) $request->string('brand') : null,
            collectionSlug: $collectionSlug,
            sort: $request->filled('sort') ? (string) $request->string('sort') : null,
            direction: $request->filled('direction') ? (string) $request->string('direction') : 'asc',
            filters: $request->filled('filters') ? (array) json_decode((string) $request->string('filters'), associative: true) : [],
            featured: $request->filled('featured') ? $request->boolean('featured') : null,
            onSale: $request->filled('onSale') ? $request->boolean('onSale') : null,
            minPrice: $request->filled('price_min') ? $request->float('price_min') : null,
            maxPrice: $request->filled('price_max') ? $request->float('price_max') : null,
            search: $request->filled('search') ? (string) $request->string('search') : null,
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 20),
        );

        $products = $this->catalogService->paginateCatalog($query);
        $facets = $this->catalogService->getFacets($query);

        return ApiResponse::success(ProductResource::collection($products), facets: $facets);
    }
}
