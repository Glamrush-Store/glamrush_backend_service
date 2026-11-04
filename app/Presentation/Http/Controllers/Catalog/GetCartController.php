<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Services\CartService;
use App\Presentation\Http\Resources\Catalog\CartItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCartController
{
    public function __construct(
        private readonly CartService $service,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $id = $this->resolveIdentifier($request);
        $result = $this->service->getCart($id);
        $items = $result['items'];

        $subtotal = $items->sum(fn($e) => $e->unitPrice * $e->quantity);

        return $this->cartResponse($id, $items->map(fn($e) => new CartItemResource($e))->values(), $subtotal, 200);
    }

    private function resolveIdentifier(Request $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }

    private function cartResponse(CartIdentifier $id, mixed $data, float $subtotal, int $status): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'message'    => 'Success',
            'data'       => $data,
            'subtotal'   => $subtotal,
            'cart_token' => $id->isGuest() ? $id->cartToken : null,
        ], $status);
    }
}
