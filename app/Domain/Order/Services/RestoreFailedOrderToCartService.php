<?php

namespace App\Domain\Order\Services;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\OrderItem;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RestoreFailedOrderToCartService
{
    private const CART_TTL_DAYS = 7;

    public function restore(string $orderId, CartIdentifier $cartIdentifier, bool $replaceCart = false): array
    {
        return DB::transaction(function () use ($orderId, $cartIdentifier, $replaceCart): array {
            $order = $this->ownedOrderQuery($orderId, $cartIdentifier)
                ->with('items')
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                throw new RuntimeException('Order not found or does not belong to this customer.');
            }

            $status = $order->status?->value ?? $order->status;

            if ($status === 'pending_payment') {
                throw new RuntimeException('This order is still pending payment. Retry payment for this order instead of restoring the cart.');
            }

            if ($status !== 'failed') {
                throw new RuntimeException('Only failed orders can be restored to cart.');
            }

            if ($replaceCart) {
                CartItem::withoutGlobalScopes()
                    ->tap(fn (Builder $query) => $this->constrainCart($query, $cartIdentifier))
                    ->delete();
            }

            $this->releaseReservedInventoryOnce($order);

            $restored = [];
            $skipped = [];
            $priceChanges = [];

            foreach ($order->items->sortBy('product_variant_id') as $item) {
                $restore = $this->restoreItem($item, $cartIdentifier);

                if ($restore['restored'] === true) {
                    $restored[] = $restore['item'];

                    if ($restore['price_changed'] !== null) {
                        $priceChanges[] = $restore['price_changed'];
                    }

                    continue;
                }

                $skipped[] = $restore['skipped'];
            }

            return [
                'order' => [
                    'id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'status' => $status,
                ],
                'cart_token' => $cartIdentifier->isGuest() ? $cartIdentifier->cartToken : null,
                'restored_count' => count($restored),
                'skipped_count' => count($skipped),
                'restored_items' => $restored,
                'skipped_items' => $skipped,
                'price_changes' => $priceChanges,
                'next_action' => count($restored) > 0 ? 'checkout' : 'browse_catalog',
            ];
        });
    }

    private function restoreItem(OrderItem $item, CartIdentifier $cartIdentifier): array
    {
        $product = Product::query()->whereKey($item->product_id)->first();

        if ($product === null) {
            return $this->skipped($item, 'product_unavailable', 'This product is no longer available.');
        }

        $variant = ProductVariant::query()
            ->whereKey($item->product_variant_id)
            ->where('product_id', $product->id)
            ->whereIn('status', ProductVariant::SELLABLE_STATUSES)
            ->lockForUpdate()
            ->first();

        if ($variant === null) {
            return $this->skipped($item, 'variant_unavailable', 'This product option is no longer available.');
        }

        $available = $this->availableQuantity($variant);
        $quantity = (int) $item->quantity;

        if ($variant->manage_stock && $available < $quantity) {
            return $this->skipped($item, 'out_of_stock', "Only {$available} item(s) are currently available.", $available);
        }

        $cartItem = CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrainCart($query, $cartIdentifier))
            ->where('product_variant_id', $variant->id)
            ->lockForUpdate()
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'expires_at' => now()->addDays(self::CART_TTL_DAYS),
            ]);
        } else {
            $cartItem = CartItem::create(array_merge($this->identifierAttrs($cartIdentifier), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'expires_at' => now()->addDays(self::CART_TTL_DAYS),
            ]));
        }

        $currentPrice = $this->effectiveVariantPrice($variant);
        $originalPrice = (float) $item->unit_price;

        return [
            'restored' => true,
            'item' => [
                'cart_item_id' => $cartItem->id,
                'product_id' => (string) $product->id,
                'product_variant_id' => (string) $variant->id,
                'name' => $product->name,
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'unit_price' => round($currentPrice, 2),
            ],
            'price_changed' => round($originalPrice, 2) !== round($currentPrice, 2)
                ? [
                    'product_id' => (string) $product->id,
                    'product_variant_id' => (string) $variant->id,
                    'name' => $product->name,
                    'sku' => $variant->sku,
                    'old_unit_price' => round($originalPrice, 2),
                    'new_unit_price' => round($currentPrice, 2),
                ]
                : null,
        ];
    }

    private function releaseReservedInventoryOnce(Order $order): void
    {
        if ($order->inventory_committed_at !== null || $order->inventory_released_at !== null) {
            return;
        }

        foreach ($order->items->sortBy('product_variant_id') as $item) {
            $variant = ProductVariant::query()
                ->whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if ($variant === null || ! $variant->manage_stock) {
                continue;
            }

            $releaseQuantity = min((int) $variant->reserved_quantity, (int) $item->quantity);

            if ($releaseQuantity > 0) {
                $variant->decrement('reserved_quantity', $releaseQuantity);
            }
        }

        $order->update(['inventory_released_at' => now()]);
    }

    private function skipped(OrderItem $item, string $reason, string $message, ?int $available = null): array
    {
        return [
            'restored' => false,
            'skipped' => [
                'order_item_id' => (string) $item->id,
                'product_id' => (string) $item->product_id,
                'product_variant_id' => (string) $item->product_variant_id,
                'name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => (int) $item->quantity,
                'reason' => $reason,
                'message' => $message,
                'available_quantity' => $available,
            ],
        ];
    }

    private function ownedOrderQuery(string $orderId, CartIdentifier $cartIdentifier): Builder
    {
        return Order::query()
            ->whereKey($orderId)
            ->when(
                ! $cartIdentifier->isGuest(),
                fn (Builder $query) => $query->where('user_id', $cartIdentifier->userId),
                fn (Builder $query) => $query->whereNull('user_id')->where('guest_id', $cartIdentifier->cartToken),
            );
    }

    private function constrainCart(Builder $query, CartIdentifier $cartIdentifier): void
    {
        if ($cartIdentifier->isGuest()) {
            $query->where('cart_token', $cartIdentifier->cartToken);

            return;
        }

        $query->where('user_id', $cartIdentifier->userId);
    }

    private function identifierAttrs(CartIdentifier $cartIdentifier): array
    {
        return $cartIdentifier->isGuest()
            ? ['cart_token' => $cartIdentifier->cartToken]
            : ['user_id' => $cartIdentifier->userId];
    }

    private function availableQuantity(ProductVariant $variant): int
    {
        if (! $variant->manage_stock) {
            return PHP_INT_MAX;
        }

        if (! $variant->in_stock) {
            return 0;
        }

        return max(0, (int) $variant->stock_quantity - (int) $variant->reserved_quantity);
    }

    private function effectiveVariantPrice(ProductVariant $variant): float
    {
        if (
            $variant->sale_price !== null
            && ($variant->sale_starts_at === null || now()->greaterThanOrEqualTo($variant->sale_starts_at))
            && ($variant->sale_ends_at === null || now()->lessThanOrEqualTo($variant->sale_ends_at))
        ) {
            return (float) $variant->sale_price;
        }

        return (float) $variant->price;
    }
}
