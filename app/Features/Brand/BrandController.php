<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Brand;


use App\Features\Brand\Contracts\BrandRepository;
use App\Features\Brand\Queries\GetBrandQuery;
use App\Features\Brand\Queries\ListBrandQuery;
use App\Features\Brand\Views\BrandView;
use Illuminate\Http\Request;

class BrandController
{

    public function __construct(protected BrandRepository $brands)
    {
    }


    public function index(ListBrandQuery $query, Request $request)
    {
        $query = new ListBrandQuery(
            search: $request->filled('search') ? $request->search : null,
        );

        return BrandView::collection($this->brands->PaginateBrandList($query));
    }


    public function show(string $slug)
    {
        $query = new GetBrandQuery($slug);

        return BrandView::make($this->brands->getBrandBySlug($query));
    }

}
