<?php

namespace App\Presentation\Http\Controllers\Customer;

use App\Domain\User\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteAddressController
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    public function __invoke(Request $request, string $address): JsonResponse
    {
        $this->service->delete($request->user()->id, $address);

        return response()->json([
            'success' => true,
            'message' => 'Address deleted.',
        ]);
    }
}
