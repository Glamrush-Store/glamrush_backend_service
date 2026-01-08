<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Category;

use App\Features\Category\Contracts\CategoryRepository;
use App\Features\Category\Queries\GetCategoryQuery;
use App\Features\Category\Queries\ListCategoryQuery;
use App\Features\Category\Views\CategoryView;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController
{

    public function __construct(
        protected CategoryRepository $categories
    ) {
    }

    #[OA\Get(
        path: '/api/categories',
        summary: 'Get categories',
        tags: ['Categories']
    )]
    #[OA\Parameter(
        name: 'parent',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated category list',
        content: new OA\JsonContent(
            ref: '#/components/schemas/Categories'
        )
    )]
    public function index(Request $request)
    {
        $query = new ListCategoryQuery(
            parent: $request->filled('parent') ? $request->string('parent') : null,
            perPage: $request->filled('per_page') ? $request->integer('per_page') : 20,
        );

        return CategoryView::collection($this->categories->PaginateCategoryList($query));
    }


    #[OA\Get(
        path: '/api/categories/{slug}',
        summary: 'Get category details',
        tags: ['Categories']
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Category details',
        content: new OA\JsonContent(
            ref: '#/components/schemas/Categories'
        )
    )]
    public function show(string $slug)
    {
        $query = new GetCategoryQuery($slug);

        return CategoryView::make($this->categories->getCategoryBySlug($query));
    }


}
