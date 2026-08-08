<?php

namespace App\Domain\Order\Entities;

final class CheckoutResult
{
    public function __construct(
        public readonly OrderEntity $order,
        public readonly bool $replayed,
    ) {}
}
