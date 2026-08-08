<?php

namespace App\Console\Commands;

use App\Domain\Order\Contracts\OrderRepository;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use Illuminate\Console\Command;

final class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire-pending {--limit=200}';
    protected $description = 'Cancel expired pending-payment orders and release inventory and discount reservations';

    public function handle(OrderRepository $orders): int
    {
        $ids = Order::query()
            ->where('status', 'pending_payment')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->pluck('id');

        foreach ($ids as $id) {
            $orders->cancelPendingOrder((string) $id);
        }

        $this->info("Expired {$ids->count()} pending orders.");
        return self::SUCCESS;
    }
}
