<?php

namespace App\Domain\Order\Events;

final class OrderPlaced
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}
