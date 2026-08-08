<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPaid;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Mail\Orders\PaymentSuccessfulMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendPaymentSuccessfulEmail implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        $order = Order::query()
            ->with(['items', 'user'])
            ->whereKey($event->orderId)
            ->first();

        if ($order === null) {
            return;
        }

        $email = $order->shipping_address['email'] ?? $order->user?->email;

        if (! $email) {
            return;
        }

        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->latest()
            ->first();

        Mail::to($email, $order->shipping_address['full_name'] ?? $order->user?->name)
            ->send(new PaymentSuccessfulMail($order, $payment));
    }
}
