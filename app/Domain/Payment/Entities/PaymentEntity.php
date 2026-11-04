<?php

namespace App\Domain\Payment\Entities;

use DateTimeImmutable;

final class PaymentEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly ?string $paymentMethodId,
        public readonly string $provider,
        public readonly string $reference,
        public readonly ?string $providerReference,
        public readonly ?string $transactionId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $authorizationUrl,
        public readonly ?DateTimeImmutable $paidAt,
        public readonly ?DateTimeImmutable $failedAt,
        public readonly array $metadata = [],
        public readonly ?string $idempotencyOwner = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $idempotencyRequestHash = null,
    ) {}

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
