<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Product;

use App\Features\Product\Contracts\ProductRepository;
use App\Features\Product\Queries\GetProductQuery;
use App\Features\Product\Queries\ListProductsQuery;
use App\Features\Product\Views\ProductCatalogView;
use App\Features\Product\Views\ProductDetailView;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController
{
    public function __construct(
        protected ProductRepository $products
    ) {
    }


    #[OA\Get(
        path: '/api/products',
        summary: 'Get products',
        tags: ['Products']
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'brand',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'filters[search][$contains]',
        description: 'Search products by name or slug',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated product catalog list',
        content: new OA\JsonContent(
            ref: '#/components/schemas/ProductCatalogResponse'
        )
    )]
    public function index(Request $request)
    {
        $query = new ListProductsQuery(
            categorySlug: $request->filled('category') ? $request->string('category') : null,
            brandSlug: $request->filled('brand') ? $request->string('brand') : null,
            sort: $request->filled('sort') ? $request->string('sort') : null,
            direction: $request->string('direction', 'asc'),
            filters: $request->filled('filters') ? $request->input('filters', []) : null,
            featured: $request->filled('featured') ? $request->boolean('featured') : null,
            minPrice: $request->filled('price_min') ? $request->float('price_min') : null,
            maxPrice: $request->filled('price_max') ? $request->float('price_max') : null,
            search: $request->filled('search') ? $request->string('search') : null,
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 20)
        );

        return ProductCatalogView::collection(
            $this->products->paginateForCatalog($query)
        );
    }


    #[OA\Get(
        path: '/api/product/{slug}',
        summary: 'Get product details',
        tags: ['Products']
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Product details',
        content: new OA\JsonContent(
            ref: '#/components/schemas/ProductDetail'
        )
    )]
    public function show(string $slug)
    {
        $query = new GetProductQuery($slug);

        return new ProductDetailView($this->products->getProductDetails($query));
    }


}
