<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPlaced;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Mail\Orders\AdminNewOrderMail;
use App\Mail\Orders\OrderPlacedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendOrderPlacedEmails implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $order = $this->order($event->orderId);

        if ($order === null) {
            return;
        }

        if ($email = $this->customerEmail($order)) {
            Mail::to($email, $this->customerName($order))->send(new OrderPlacedMail($order));
        }

        foreach ($this->adminRecipients() as $email) {
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

    /** @return list<string> */
    private function adminRecipients(): array
    {
        return collect([
            config('mail.admin.address'),
            ...explode(',', (string) config('services.notifications.new_order_emails', '')),
        ])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
