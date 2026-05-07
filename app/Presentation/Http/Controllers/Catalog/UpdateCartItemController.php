<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Cart\Services\CartService;
use App\Presentation\Http\Requests\Cart\UpdateCartItemRequest;
use App\Presentation\Http\Resources\Catalog\CartItemResource;
use Illuminate\Http\JsonResponse;

final class UpdateCartItemController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(UpdateCartItemRequest $request, string $productId): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        try {
            $entity = $this->service->update(
                $id,
                $productId,
                (int) $request->validated('quantity'),
            );
        } catch (InsufficientStockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->cartResponse($id, new CartItemResource($entity), 200);
    }

    private function resolveIdentifier(UpdateCartItemRequest $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }

    private function cartResponse(CartIdentifier $id, mixed $data, int $status): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'message'    => 'Success',
            'data'       => $data,
            'cart_token' => $id->isGuest() ? $id->cartToken : null,
        ], $status);
    }
}
