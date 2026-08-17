<?php

namespace App\Listeners\Payment;

use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Setting\Services\NotificationRecipientResolver;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Mail\Orders\AdminPaymentFailedMail;
use App\Mail\Orders\PaymentFailedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendPaymentFailedEmail implements ShouldQueue
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handle(PaymentFailed $event): void
    {
        $payment = Payment::query()
            ->with(['order.items', 'order.user'])
            ->whereKey($event->paymentId)
            ->first();

        if ($payment === null || $payment->order === null) {
            return;
        }

        $order = $payment->order;
        $email = $order->shipping_address['email'] ?? $order->user?->email;

        if ($email) {
            Mail::to($email, $order->shipping_address['full_name'] ?? $order->user?->name)
                ->send(new PaymentFailedMail($order, $payment));
        }

        foreach ($this->recipients->resolve(
            'PAYMENT_FAILED_EMAILS',
            'services.notifications.payment_failed_emails',
        ) as $recipient) {
            Mail::to($recipient, config('mail.admin.name'))->send(new AdminPaymentFailedMail($order, $payment));
        }
    }
}
