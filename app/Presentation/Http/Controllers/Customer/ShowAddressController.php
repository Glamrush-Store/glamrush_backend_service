<?php

namespace App\Presentation\Http\Controllers\Customer;

use App\Domain\User\Services\AddressService;
use App\Presentation\Http\Resources\Customer\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowAddressController
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    public function __invoke(Request $request, string $address): JsonResponse
    {
        $entity = $this->service->find($request->user()->id, $address);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => new AddressResource($entity),
        ]);
    }
}
