<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Repositories;


use App\Domain\Order\Contracts\OrderRepository;
use App\Domain\Order\Entities\CreateOrderEntity;
use App\Domain\Order\Entities\OrderEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\Order\OrderMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentOrderRepository implements OrderRepository
{
    public function createPendingOrder(CreateOrderEntity $data): OrderEntity
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'user_id' => $data->userId,
                'guest_id' => $data->guestId,
                'order_number' => $data->orderNumber,
                'status' => 'pending_payment',
                'subtotal' => $data->subtotal,
                'shipping_amount' => $data->shippingAmount,
                'total' => $data->subtotal + $data->shippingAmount,
                'currency' => $data->currency,
                'shipping_rate_id' => $data->shippingRateId,
                'shipping_method_name' => $data->shippingMethodName,
                'shipping_zone_name' => $data->shippingZoneName,
                'shipping_address' => $data->shippingAddress,
                'billing_address' => $data->billingAddress,
                'placed_at' => now(),
            ]);

            foreach ($data->items as $item) {
                $order->items()->create([
                    'product_id' => $item->productId,
                    'product_variant_id' => $item->productVariantId,
                    'product_name' => $item->productName,
                    'product_slug' => $item->productSlug,
                    'sku' => $item->sku,
                    'unit_price' => $item->unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $item->unitPrice * $item->quantity,
                    'product_snapshot' => $item->productSnapshot,
                ]);
            }

            return OrderMapper::toDomain(
                $order->load('items')
            );
        });
    }


    public function findById(string $id): ?OrderEntity
    {
        $order = Order::query()
            ->with([
                'items.product' => fn($query) => $query->withoutGlobalScopes()->with('media'),
                'items.productVariant.media',
            ])
            ->whereKey($id)
            ->first();

        return $order
            ? OrderMapper::toDomain($order)
            : null;
    }

    public function findByOrderNumber(string $orderNumber): ?OrderEntity
    {
        $order = Order::query()
            ->with([
                'items.product' => fn($query) => $query->withoutGlobalScopes()->with('media'),
                'items.productVariant.media',
            ])
            ->where('order_number', $orderNumber)
            ->first();

        return $order
            ? OrderMapper::toDomain($order)
            : null;
    }

    public function markAsPaid(string $orderId): void
    {
        Order::where('id', $orderId)->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsProcessing(string $orderId): void
    {
        Order::where('id', $orderId)->update([
            'status' => 'processing',
        ]);
    }

    public function markAsPendingOnDelivery(string $orderId): void
    {
        Order::where('id', $orderId)->update([
            'status' => 'pending_on_delivery',
        ]);
    }


    public function cancelPendingOrder(string $orderId): void
    {
        $order = Order::where('id', $orderId)->firstOrFail();

        DB::transaction(function () use ($order) {
            if ($order->status !== 'pending_payment') {
                return;
            }

            $order->load('items');

            foreach ($order->items as $item) {
                $variant = ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $variant->decrement('reserved_quantity', $item->quantity);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });
    }


    public function paginateByUserId(
        string $userId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        $paginator = Order::query()
            ->with([
                'items.product' => fn($query) => $query->withoutGlobalScopes()->with('media'),
                'items.productVariant.media',
            ])
            ->where('user_id', $userId)
            ->latest()
            ->paginate(
                perPage: $perPage,
                page: $page
            );

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn(Order $order): OrderEntity => OrderMapper::toDomain($order))
        );

        return $paginator;
    }


    public function orderNumberExists(string $orderNumber): bool
    {
        return Order::query()
            ->where('order_number', $orderNumber)
            ->exists();
    }


}
