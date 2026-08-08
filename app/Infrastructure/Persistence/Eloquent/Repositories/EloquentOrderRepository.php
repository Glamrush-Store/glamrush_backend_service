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
use Illuminate\Support\Facades\Schema;

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
                    'line_subtotal' => $item->unitPrice * $item->quantity,
                    'discount_amount' => 0,
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
                'items.product' => fn ($query) => $query->withoutGlobalScopes()->with('media'),
                'items.productVariant.media',
            ])
            ->whereKey($id)
            ->first();

        return $order
            ? OrderMapper::toDomain($order)
            : null;
    }

    public function findByIdForOwner(string $id, ?int $userId, ?string $guestId): ?OrderEntity
    {
        $order = Order::query()
            ->with('items')
            ->whereKey($id)
            ->when(
                $userId !== null,
                fn ($query) => $query->where('user_id', $userId),
                fn ($query) => $query->whereNull('user_id')->where('guest_id', $guestId),
            )
            ->first();

        return $order ? OrderMapper::toDomain($order) : null;
    }

    public function findByOrderNumber(string $orderNumber): ?OrderEntity
    {
        $order = Order::query()
            ->with([
                'items.product' => fn ($query) => $query->withoutGlobalScopes()->with('media'),
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

    public function markPaidAndCommitInventory(string $orderId): bool
    {
        $order = Order::query()
            ->with('items')
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($order->inventory_committed_at !== null) {
            return false;
        }

        foreach ($order->items->sortBy('product_variant_id') as $item) {
            $variant = ProductVariant::query()
                ->whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $variant->reserved_quantity < (int) $item->quantity
                || (int) $variant->stock_quantity < (int) $item->quantity
            ) {
                throw new \RuntimeException("Reserved inventory is insufficient for order {$order->order_number}.");
            }

            $variant->decrement('reserved_quantity', $item->quantity);
            $variant->decrement('stock_quantity', $item->quantity);
        }

        $order->update([
            'status' => 'paid',
            'paid_at' => $order->paid_at ?? now(),
            'inventory_committed_at' => now(),
        ]);

        if (Schema::hasTable('discount_redemptions')) {
            DB::table('discount_redemptions')
                ->where('order_id', $order->id)
                ->where('status', 'reserved')
                ->update(['status' => 'redeemed', 'redeemed_at' => now(), 'updated_at' => now()]);
        }

        return true;
    }

    public function markAsProcessing(string $orderId): void
    {
        Order::where('id', $orderId)->update([
            'status' => 'processing',
        ]);
    }

    public function markAsPendingOnDelivery(string $orderId): bool
    {
        $updated = Order::query()
            ->whereKey($orderId)
            ->where('status', 'pending_payment')
            ->update(['status' => 'pending_on_delivery']) === 1;

        if ($updated && Schema::hasTable('discount_redemptions')) {
            DB::table('discount_redemptions')
                ->where('order_id', $orderId)
                ->where('status', 'reserved')
                ->update(['status' => 'redeemed', 'redeemed_at' => now(), 'updated_at' => now()]);
        }

        return $updated;
    }

    public function cancelPendingOrder(string $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->status->value !== 'pending_payment') {
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

            if (Schema::hasTable('discount_redemptions')) {
                DB::table('discount_redemptions')
                    ->where('order_id', $order->id)
                    ->where('status', 'reserved')
                    ->update(['status' => 'released', 'released_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function paginateByUserId(
        string $userId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        $paginator = Order::query()
            ->with([
                'items.product' => fn ($query) => $query->withoutGlobalScopes()->with('media'),
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
                ->map(fn (Order $order): OrderEntity => OrderMapper::toDomain($order))
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
