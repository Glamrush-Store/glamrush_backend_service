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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListProductController
{

    public function __construct(
        private CatalogService $catalogService,
    ) {
    }

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $query = new ListProductsQuery(
            categorySlug: $request->filled('category') ? $request->string('category') : null,
            brandSlug: $request->filled('brand') ? $request->string('brand') : null,
            sort: $request->filled('sort') ? $request->string('sort') : null,
            direction: $request->filled('direction') ? $request->string('direction') : 'asc',
            filters: $request->filled('filters') ? $request->json('filters') : [],
            featured: $request->filled('featured') ? $request->boolean('featured') : null,
            minPrice: $request->filled('price_min') ? $request->float('price_min') : null,
            maxPrice: $request->filled('price_max') ? $request->float('price_max') : null,
            search: $request->filled('search') ? $request->string('search') : null,
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 20),
        );


        $product = $this->catalogService->paginateCatalog($query);

        return ProductResource::collection($product);
    }
}
