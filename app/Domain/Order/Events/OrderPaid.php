<?php

namespace App\Domain\Order\Events;

use App\Shared\Events\Contracts\DomainEvent;

final class OrderPaid implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
    ) {}

    public function eventType(): string
    {
        return 'order.paid';
    }

    public function eventPayload(): array
    {
        return ['order_id' => $this->orderId];
    }
}
