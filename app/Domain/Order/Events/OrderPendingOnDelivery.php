<?php

namespace App\Domain\Order\Events;

use App\Shared\Events\Contracts\DomainEvent;

final class OrderPendingOnDelivery implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
    ) {}

    public function eventType(): string
    {
        return 'order.pending_on_delivery';
    }

    public function eventPayload(): array
    {
        return [
            'order_id' => $this->orderId,
            'payment_id' => $this->paymentId,
        ];
    }
}
