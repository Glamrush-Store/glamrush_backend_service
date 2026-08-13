<?php

namespace App\Presentation\Http\Controllers\Order;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Order\Services\RestoreFailedOrderToCartService;
use App\Presentation\Http\Requests\Order\RestoreFailedOrderToCartRequest;
use App\Presentation\Http\Responses\ApiResponse;
use RuntimeException;

final class RestoreFailedOrderToCartController
{
    public function __construct(private readonly RestoreFailedOrderToCartService $restoreFailedOrderToCart) {}

    public function __invoke(string $order, RestoreFailedOrderToCartRequest $request)
    {
        try {
            $result = $this->restoreFailedOrderToCart->restore(
                orderId: $order,
                cartIdentifier: $this->cartIdentifier($request),
                replaceCart: $request->boolean('replace_cart'),
            );
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'Retry payment') ? 409 : 422;

            return ApiResponse::error($exception->getMessage(), status: $status);
        }

        return ApiResponse::success($result, 'Order items restored to cart.');
    }

    private function cartIdentifier(RestoreFailedOrderToCartRequest $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }
}
