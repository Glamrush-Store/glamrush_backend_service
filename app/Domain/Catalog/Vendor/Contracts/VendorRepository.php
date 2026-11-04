<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Domain\Catalog\Vendor\Contracts;


use App\Domain\Catalog\Vendor\DTOs\VendorDto;
use App\Domain\Catalog\Vendor\Queries\GetVendorQuery;
use App\Domain\Catalog\Vendor\Queries\ListVendorQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VendorRepository
{

    public function getVendorBySlug(GetVendorQuery $query): VendorDto;

    /**
     * @return Collection<int, VendorDto>
     */
    public function PaginateVendorList(ListVendorQuery $query, int $perPage = 15): LengthAwarePaginator;

}
