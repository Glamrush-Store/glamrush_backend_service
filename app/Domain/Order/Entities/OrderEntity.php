<?php

namespace App\Domain\Order\Entities;

use DateTimeImmutable;

final class OrderEntity
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $userId,
        public readonly ?string $guestId,
        public readonly string $orderNumber,
        public readonly string $status,
        public readonly float $subtotal,
        public readonly float $shippingAmount,
        public readonly string $total,
        public readonly string $currency,
        public readonly ?string $shippingRateId,
        public readonly string $shippingMethodName,
        public readonly string $shippingZoneName,
        public readonly array $shippingAddress,
        public readonly ?array $billingAddress,
        public readonly ?DateTimeImmutable $placedAt,
        public readonly ?DateTimeImmutable $paidAt,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $cancelledAt,
        public readonly ?array $items = null,
    ) {
    }

    public function total(): float
    {
        return $this->subtotal + $this->shippingAmount;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }


}
