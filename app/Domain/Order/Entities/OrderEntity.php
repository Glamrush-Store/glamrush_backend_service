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
        public readonly ?string $paymentMethodCode,
        public readonly ?string $discountCode,
        public readonly float $subtotal,
        public readonly float $discountAmount,
        public readonly float $shippingAmount,
        public readonly float $shippingDiscountAmount,
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
        return (float) $this->total;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }


}
