<?php

namespace App\Domain\Order\Events;

final class OrderPendingOnDelivery
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
    ) {
    }
}
