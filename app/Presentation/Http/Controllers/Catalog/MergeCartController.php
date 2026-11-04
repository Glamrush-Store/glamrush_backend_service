<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Cart\Exceptions\InvalidCartSelectionException;
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

        try {
            $result = $this->service->merge($userId, $cartToken);
        } catch (InsufficientStockException|InvalidCartSelectionException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $result['items']->map(fn ($e) => new CartItemResource($e))->values(),
            'cart_token' => null,
            'guest_cart_empty' => $result['guest_cart_empty'],
        ], 200);
    }
}
