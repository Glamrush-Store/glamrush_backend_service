<?php

namespace App\Domain\Payment\Events;

final class PaymentFailed
{
    public function __construct(
        public readonly string $paymentId,
    ) {
    }
}
