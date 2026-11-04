<?php

namespace App\Presentation\Http\Controllers\Location;

use App\Domain\Location\Services\LocationService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ListCountriesController
{
    public function __invoke(LocationService $locations): JsonResponse
    {
        return ApiResponse::success($locations->countries());
    }
}
