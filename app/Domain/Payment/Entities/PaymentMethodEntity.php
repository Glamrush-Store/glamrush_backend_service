<?php

namespace App\Domain\Payment\Entities;

final class PaymentMethodEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly array $publicConfig = [],
    ) {
    }
}
