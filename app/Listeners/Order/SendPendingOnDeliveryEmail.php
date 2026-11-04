<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPendingOnDelivery;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Mail\Orders\PendingOnDeliveryMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendPendingOnDeliveryEmail implements ShouldQueue
{
    public function handle(OrderPendingOnDelivery $event): void
    {
        $order = Order::query()
            ->with(['items', 'user'])
            ->whereKey($event->orderId)
            ->first();
        $payment = Payment::query()->whereKey($event->paymentId)->first();

        if ($order === null || $payment === null) {
            return;
        }

        $email = $order->shipping_address['email'] ?? $order->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email, $order->shipping_address['full_name'] ?? $order->user?->name)
            ->send(new PendingOnDeliveryMail($order, $payment));
    }
}
