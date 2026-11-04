<?php

namespace App\Presentation\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'payment_method' => $this->paymentMethodCode,
            'discount_code' => $this->discountCode,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'shipping_amount' => $this->shippingAmount,
            'shipping_discount_amount' => $this->shippingDiscountAmount,
            'total' => $this->total,
            'currency' => $this->currency,
            'shipping_rate_id' => $this->shippingRateId,
            'shipping_method_name' => $this->shippingMethodName,
            'shipping_zone_name' => $this->shippingZoneName,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'placed_at' => $this->placedAt?->format(DATE_ATOM),
            'paid_at' => $this->paidAt?->format(DATE_ATOM),
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
            'cancelled_at' => $this->cancelledAt?->format(DATE_ATOM),
            'items' => array_map(
                fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->productId,
                    'product_variant_id' => $item->productVariantId,
                    'product_name' => $item->productName,
                    'product_slug' => $item->productSlug,
                    'sku' => $item->sku,
                    'unit_price' => $item->unitPrice,
                    'quantity' => $item->quantity,
                    'line_subtotal' => $item->lineSubtotal,
                    'discount_amount' => $item->discountAmount,
                    'line_total' => $item->lineTotal,
                    'images' => $this->itemImages($item->productSnapshot),
                    'product_snapshot' => $item->productSnapshot,
                ],
                $this->items ?? [],
            ),
        ];
    }

    private function itemImages(?array $snapshot): array
    {
        return $snapshot['images'] ?? [];
    }
}
