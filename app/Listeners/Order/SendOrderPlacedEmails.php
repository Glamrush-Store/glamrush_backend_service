<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Setting\Services\NotificationRecipientResolver;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Mail\Orders\AdminNewOrderMail;
use App\Mail\Orders\OrderPlacedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendOrderPlacedEmails implements ShouldQueue
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $this->order($event->orderId);

        if ($order === null) {
            return;
        }

        if ($email = $this->customerEmail($order)) {
            Mail::to($email, $this->customerName($order))->send(new OrderPlacedMail($order));
        }

        foreach ($this->recipients->resolve(
            'NEW_ORDER_EMAILS',
            'services.notifications.new_order_emails',
            [config('mail.admin.address')],
        ) as $email) {
            Mail::to($email, config('mail.admin.name'))->send(new AdminNewOrderMail($order));
        }
    }

    private function order(string $orderId): ?Order
    {
        return Order::query()
            ->with(['items', 'user'])
            ->whereKey($orderId)
            ->first();
    }

    private function customerEmail(Order $order): ?string
    {
        return $order->shipping_address['email'] ?? $order->user?->email;
    }

    private function customerName(Order $order): ?string
    {
        return $order->shipping_address['full_name'] ?? $order->user?->name;
    }
}
