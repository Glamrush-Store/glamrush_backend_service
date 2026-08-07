<?php

namespace App\Presentation\Http\Controllers\Checkout;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Order\Services\CheckoutService;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Presentation\Http\Requests\Cart\CheckOutCartRequest;
use App\Presentation\Http\Resources\Order\OrderResource;
use App\Presentation\Http\Responses\ApiResponse;
use App\Shared\Idempotency\IdempotencyFingerprint;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class CheckoutCartController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function __invoke(CheckOutCartRequest $request): JsonResponse
    {
        $cartIdentifier = $this->resolveCartIdentifier($request);

        $shippingAddress = new ShippingAddressEntity(
            country: $request->string('shipping_address.country')->toString(),
            state: $request->string('shipping_address.state')->toString(),
            city: $request->string('shipping_address.city')->toString(),
            postalCode: $request->filled('shipping_address.postal_code')
                ? $request->string('shipping_address.postal_code')->toString()
                : null,
        );

        try {
            $result = $this->checkoutService->checkoutCart(
                cartIdentifier: $cartIdentifier,
                shippingAddress: $shippingAddress,
                shippingAddressPayload: $request->shippingAddressPayload(),
                billingAddressPayload: $request->billingAddressPayload(),
                shippingRateId: $request->validated('shipping_rate_id'),
                paymentMethod: $request->validated('payment_method'),
                userId: $request->user('sanctum')?->id,
                idempotencyKey: (string) $request->attributes->get('idempotency_key'),
                requestHash: IdempotencyFingerprint::from([
                    'storefront' => $request->route('storefront'),
                    'payload' => $request->validated(),
                ]),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), status: 422);
        }

        return ApiResponse::success(new OrderResource($result->order), 'Order created successfully.', 201)
            ->header('Idempotent-Replayed', $result->replayed ? 'true' : 'false');
    }

    private function resolveCartIdentifier(CheckOutCartRequest $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }
}
