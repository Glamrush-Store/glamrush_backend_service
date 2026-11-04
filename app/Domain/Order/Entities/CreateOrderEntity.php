<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Order\Entities;

final class CreateOrderEntity
{
    public function __construct(
        public readonly ?string $userId,
        public readonly ?string $guestId,
        public readonly string $orderNumber,
        public readonly float $subtotal,
        public readonly float $shippingAmount,
        public readonly string $currency,
        public readonly string $shippingRateId,
        public readonly string $shippingMethodName,
        public readonly string $shippingZoneName,
        public readonly array $shippingAddress,
        public readonly ?array $billingAddress,
        /** @var CreateOrderItemData[] */
        public readonly array $items,
    ) {
    }
}
