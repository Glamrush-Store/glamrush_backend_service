<?php

namespace App\Domain\Order\Services;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Order\Contracts\CheckoutRepository;
use App\Domain\Order\Entities\CheckoutResult;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Shared\Idempotency\IdempotencyLock;
use App\Shared\Idempotency\IdempotencyOwner;

final class CheckoutService
{
    public function __construct(
        private readonly CheckoutRepository $checkoutRepository,
        private readonly IdempotencyLock $idempotencyLock,
    ) {}

    public function checkoutCart(
        CartIdentifier $cartIdentifier,
        ShippingAddressEntity $shippingAddress,
        array $shippingAddressPayload,
        ?array $billingAddressPayload,
        string $shippingRateId,
        string $paymentMethod,
        ?string $discountCode,
        ?int $userId,
        string $idempotencyKey,
        string $requestHash,
    ): CheckoutResult {
        $owner = IdempotencyOwner::from($userId, $cartIdentifier->cartToken);

        return $this->idempotencyLock->run(
            'checkout',
            $owner,
            $idempotencyKey,
            fn (): CheckoutResult => $this->checkoutRepository->createPendingOrderFromCart(
                cartIdentifier: $cartIdentifier,
                shippingAddress: $shippingAddress,
                shippingAddressPayload: $shippingAddressPayload,
                billingAddressPayload: $billingAddressPayload,
                shippingRateId: $shippingRateId,
                paymentMethod: $paymentMethod,
                discountCode: $discountCode,
                userId: $userId,
                idempotencyOwner: $owner,
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
            ),
        );
    }
}
