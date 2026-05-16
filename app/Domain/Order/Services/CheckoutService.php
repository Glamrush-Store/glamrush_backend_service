<?php

namespace App\Domain\Order\Services;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Order\Contracts\CheckoutRepository;
use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Shipping\Entities\ShippingAddressEntity;

final class CheckoutService
{
    public function __construct(
        private readonly CheckoutRepository $checkoutRepository,
    ) {
    }

    public function checkoutCart(
        CartIdentifier $cartIdentifier,
        ShippingAddressEntity $shippingAddress,
        array $shippingAddressPayload,
        ?array $billingAddressPayload,
        string $shippingRateId,
        string $paymentMethod,
        ?int $userId,
    ): OrderEntity {
        return $this->checkoutRepository->createPendingOrderFromCart(
            cartIdentifier: $cartIdentifier,
            shippingAddress: $shippingAddress,
            shippingAddressPayload: $shippingAddressPayload,
            billingAddressPayload: $billingAddressPayload,
            shippingRateId: $shippingRateId,
            paymentMethod: $paymentMethod,
            userId: $userId,
        );
    }
}
