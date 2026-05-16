<?php

namespace App\Domain\Order\Contracts;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Shipping\Entities\ShippingAddressEntity;

interface CheckoutRepository
{
    public function createPendingOrderFromCart(
        CartIdentifier $cartIdentifier,
        ShippingAddressEntity $shippingAddress,
        array $shippingAddressPayload,
        ?array $billingAddressPayload,
        string $shippingRateId,
        string $paymentMethod,
        ?int $userId,
    ): OrderEntity;
}
