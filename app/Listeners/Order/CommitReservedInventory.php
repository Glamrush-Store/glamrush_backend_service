<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPaid;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class CommitReservedInventory
{
    public function handle(OrderPaid $event): void
    {
        DB::transaction(function () use ($event) {
            $order = Order::query()
                ->with('items')
                ->whereKey($event->orderId)
                ->firstOrFail();

            foreach ($order->items as $item) {
                $variant = ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $variant->decrement('reserved_quantity', $item->quantity);
                $variant->decrement('stock_quantity', $item->quantity);
            }
        });
    }
}
