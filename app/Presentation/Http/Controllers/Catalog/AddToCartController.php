<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Cart\Services\CartService;
use App\Presentation\Http\Requests\Cart\AddToCartRequest;
use App\Presentation\Http\Resources\Catalog\CartItemResource;
use Illuminate\Http\JsonResponse;

final class AddToCartController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(AddToCartRequest $request): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        try {
            $result = $this->service->add(
                $id,
                $request->validated('product_id'),
                (int) ($request->validated('quantity') ?? 1),
            );
        } catch (InsufficientStockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $responseId = new CartIdentifier(
            $id->userId,
            $result['cart_token'],
        );

        $status = $result['created'] ? 201 : 200;

        return $this->cartResponse($responseId, new CartItemResource($result['entity']), $status);
    }

    private function resolveIdentifier(AddToCartRequest $request): CartIdentifier
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
