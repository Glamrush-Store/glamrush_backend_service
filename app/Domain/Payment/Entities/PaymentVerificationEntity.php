<?php

namespace App\Domain\Payment\Entities;

final class PaymentVerificationEntity
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $transactionId,
        public readonly ?string $providerReference,
        public readonly string $status,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly array $payload = [],
    ) {
    }

    public function succeeded(): bool
    {
        return in_array($this->status, ['success', 'successful', 'paid'], true);
    }
}
