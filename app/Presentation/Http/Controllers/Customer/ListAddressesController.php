<?php

namespace App\Presentation\Http\Controllers\Customer;

use App\Domain\User\Services\AddressService;
use App\Presentation\Http\Resources\Customer\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListAddressesController
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $addresses = $this->service->list($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => AddressResource::collection($addresses),
        ]);
    }
}
