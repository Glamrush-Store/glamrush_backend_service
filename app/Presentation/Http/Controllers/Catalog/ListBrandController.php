<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Brand\Queries\ListBrandQuery;
use App\Domain\Catalog\Brand\Services\BrandService;
use App\Presentation\Http\Resources\Catalog\BrandResource;
use Illuminate\Http\Request;

final class ListBrandController
{

    public function __construct(
        private BrandService $brandService
    ) {
    }

    public function __invoke(Request $request)
    {
        $query = new ListBrandQuery(
            search: $request->filled('search') ? $request->string('search') : null,
        );


        $brands = $this->brandService->getBrandList($query);

        return BrandResource::collection($brands);
    }
}
