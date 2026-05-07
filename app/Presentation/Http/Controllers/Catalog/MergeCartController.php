<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Services\CartService;
use App\Presentation\Http\Requests\Cart\MergeCartRequest;
use App\Presentation\Http\Resources\Catalog\CartItemResource;
use Illuminate\Http\JsonResponse;

final class MergeCartController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(MergeCartRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cartToken = $request->validated('cart_token');

        $items = $this->service->merge($userId, $cartToken);

        return response()->json([
            'success'    => true,
            'message'    => 'Success',
            'data'       => $items->map(fn($e) => new CartItemResource($e))->values(),
            'cart_token' => null,
        ], 200);
    }
}
