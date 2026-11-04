<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Cart\Exceptions\InvalidCartSelectionException;
use App\Domain\Catalog\Cart\Services\CartService;
use App\Presentation\Http\Requests\Cart\UpdateCartItemRequest;
use App\Presentation\Http\Resources\Catalog\CartItemResource;
use Illuminate\Http\JsonResponse;

final class UpdateCartItemByIdController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(UpdateCartItemRequest $request): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        try {
            $entity = $this->service->updateById(
                $id,
                (int) $request->route('itemId'),
                (int) $request->validated('quantity'),
            );
        } catch (InsufficientStockException|InvalidCartSelectionException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new CartItemResource($entity),
            'cart_token' => $id->isGuest() ? $id->cartToken : null,
        ]);
    }

    private function resolveIdentifier(UpdateCartItemRequest $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }
}
