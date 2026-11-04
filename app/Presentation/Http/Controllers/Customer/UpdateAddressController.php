<?php

namespace App\Presentation\Http\Controllers\Customer;

use App\Domain\User\Services\AddressService;
use App\Presentation\Http\Requests\Address\UpdateAddressRequest;
use App\Presentation\Http\Resources\Customer\AddressResource;
use Illuminate\Http\JsonResponse;

final class UpdateAddressController
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    public function __invoke(UpdateAddressRequest $request, string $address): JsonResponse
    {
        $entity = $this->service->update($request->user()->id, $address, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Address updated.',
            'data'    => new AddressResource($entity),
        ]);
    }
}
