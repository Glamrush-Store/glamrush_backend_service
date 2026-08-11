<?php

namespace App\Presentation\Http\Controllers\Location;

use App\Domain\Location\Services\LocationService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ListCountryStatesController
{
    public function __invoke(string $country, LocationService $locations): JsonResponse
    {
        $states = $locations->states($country);

        return $states === null
            ? ApiResponse::error('Country not found.', status: 404)
            : ApiResponse::success($states);
    }
}
