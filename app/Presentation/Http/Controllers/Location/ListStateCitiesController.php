<?php

namespace App\Presentation\Http\Controllers\Location;

use App\Domain\Location\Services\LocationService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ListStateCitiesController
{
    public function __invoke(string $country, string $state, LocationService $locations): JsonResponse
    {
        $cities = $locations->cities($country, $state);

        return $cities === null
            ? ApiResponse::error('Country or state not found.', status: 404)
            : ApiResponse::success($cities);
    }
}
