<?php

namespace App\Listeners\Order;

use App\Domain\Order\Contracts\OrderRepository;
use App\Domain\Order\Events\OrderPaid;
use Illuminate\Support\Facades\DB;

final class CommitReservedInventory
{
    public function __construct(private readonly OrderRepository $orders) {}

    public function handle(OrderPaid $event): void
    {
        DB::transaction(
            fn (): bool => $this->orders->markPaidAndCommitInventory($event->orderId),
        );
    }
}
