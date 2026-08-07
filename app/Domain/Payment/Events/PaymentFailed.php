<?php

namespace App\Domain\Payment\Events;

use App\Shared\Events\Contracts\DomainEvent;

final class PaymentFailed implements DomainEvent
{
    public function __construct(
        public readonly string $paymentId,
    ) {}

    public function eventType(): string
    {
        return 'payment.failed';
    }

    public function eventPayload(): array
    {
        return ['payment_id' => $this->paymentId];
    }
}
