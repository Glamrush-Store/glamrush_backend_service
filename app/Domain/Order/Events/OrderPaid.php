<?php

namespace App\Domain\Order\Events;

final class OrderPaid
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}
