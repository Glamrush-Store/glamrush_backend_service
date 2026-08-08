<?php

namespace App\Shared\Events;

use App\Domain\Order\Events\OrderPaid;
use App\Domain\Order\Events\OrderPendingOnDelivery;
use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\User\Events\UserRegistered;
use App\Shared\Events\Contracts\DomainEvent;
use RuntimeException;

final class DomainEventRegistry
{
    public function eventFrom(string $type, array $payload): DomainEvent
    {
        return match ($type) {
            'order.placed' => new OrderPlaced((string) $payload['order_id']),
            'order.paid' => new OrderPaid((string) $payload['order_id']),
            'order.pending_on_delivery' => new OrderPendingOnDelivery(
                (string) $payload['order_id'],
                (string) $payload['payment_id'],
            ),
            'payment.failed' => new PaymentFailed((string) $payload['payment_id']),
            'user.registered' => new UserRegistered((string) $payload['user_id']),
            default => throw new RuntimeException("Unsupported domain event type [{$type}]."),
        };
    }

    public function eventFromEnvelope(EventEnvelope $envelope): DomainEvent
    {
        return $this->eventFrom($envelope->type, $envelope->payload);
    }
}
