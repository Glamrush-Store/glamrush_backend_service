<?php

namespace App\Presentation\Http\Controllers\Customer;

use App\Domain\User\Services\AddressService;
use App\Presentation\Http\Requests\Address\StoreAddressRequest;
use App\Presentation\Http\Resources\Customer\AddressResource;
use Illuminate\Http\JsonResponse;

final class StoreAddressController
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    public function __invoke(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->service->create($request->user()->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Address created.',
            'data'    => new AddressResource($address),
        ], 201);
    }
}
