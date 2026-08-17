<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Order;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Order\Entities\OrderItemEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\OrderItem;
use App\Support\Media\SafeMediaUrl;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

final class OrderMapper
{
    public static function toDomain(Order $model): OrderEntity
    {
        $items = $model->relationLoaded('items')
            ? $model->items->map(fn (OrderItem $item) => self::itemToDomain($item))->all()
            : null;

        $paymentMethodCode = null;

        if ($model->relationLoaded('payments')) {
            $payment = $model->payments
                ->sortByDesc(fn ($payment) => $payment->created_at?->getTimestamp() ?? 0)
                ->first();
            $paymentMethodCode = $payment?->relationLoaded('paymentMethod')
                ? $payment->paymentMethod?->code
                : null;
        }

        return new OrderEntity(
            id: (string) $model->id,
            userId: $model->user_id !== null ? (string) $model->user_id : null,
            guestId: $model->guest_id,
            orderNumber: $model->order_number,
            status: $model->status->value,
            paymentMethodCode: $paymentMethodCode,
            discountCode: $model->discount_code,
            subtotal: (string) $model->subtotal,
            discountAmount: (string) ($model->discount_amount ?? 0),
            shippingAmount: (string) $model->shipping_amount,
            shippingDiscountAmount: (string) ($model->shipping_discount_amount ?? 0),
            total: (string) $model->total,
            currency: $model->currency,
            shippingRateId: $model->shipping_rate_id,
            shippingMethodName: $model->shipping_method_name,
            shippingZoneName: $model->shipping_zone_name,
            shippingAddress: (array) $model->shipping_address,
            billingAddress: $model->billing_address !== null ? (array) $model->billing_address : null,
            placedAt: $model->placed_at !== null
                ? DateTimeImmutable::createFromInterface($model->placed_at)
                : null,
            paidAt: $model->paid_at !== null
                ? DateTimeImmutable::createFromInterface($model->paid_at)
                : null,
            expiresAt: $model->expires_at !== null
                ? DateTimeImmutable::createFromInterface($model->expires_at)
                : null,
            cancelledAt: $model->cancelled_at !== null
                ? DateTimeImmutable::createFromInterface($model->cancelled_at)
                : null,
            items: $items,
        );
    }

    public static function itemToDomain(OrderItem $model): OrderItemEntity
    {
        return new OrderItemEntity(
            id: (string) $model->id,
            orderId: (string) $model->order_id,
            productId: (string) $model->product_id,
            productVariantId: $model->product_variant_id,
            productName: $model->product_name,
            productSlug: $model->product_slug,
            sku: $model->sku,
            unitPrice: (string) $model->unit_price,
            quantity: (int) $model->quantity,
            lineSubtotal: (string) ($model->line_subtotal ?? $model->line_total),
            discountAmount: (string) ($model->discount_amount ?? 0),
            lineTotal: (string) $model->line_total,
            productSnapshot: self::snapshotWithImages($model),
        );
    }

    private static function snapshotWithImages(OrderItem $model): ?array
    {
        $snapshot = $model->product_snapshot;

        if ($snapshot === null) {
            return null;
        }

        if (! empty($snapshot['images'])) {
            return $snapshot;
        }

        $variantImages = $model->relationLoaded('productVariant')
            ? self::mediaImages($model->productVariant)
            : [];

        $productImages = $model->relationLoaded('product')
            ? self::mediaImages($model->product)
            : [];

        return array_merge($snapshot, [
            'images' => $variantImages !== [] ? $variantImages : $productImages,
        ]);
    }

    private static function mediaImages(?Model $model): array
    {
        if ($model === null || ! method_exists($model, 'getMedia')) {
            return [];
        }

        return $model->getMedia('catalog-photos')
            ->map(fn ($media) => SafeMediaUrl::image($media))
            ->all();
    }
}
