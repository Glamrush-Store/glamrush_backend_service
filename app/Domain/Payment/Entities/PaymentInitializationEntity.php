<?php

namespace App\Domain\Payment\Entities;

final class PaymentInitializationEntity
{
    public function __construct(
        public readonly PaymentEntity $payment,
        public readonly ?string $authorizationUrl,
        public readonly ?string $accessCode,
        public readonly string $reference,
        public readonly string $provider,
        public readonly string $status,
    ) {
    }
}
