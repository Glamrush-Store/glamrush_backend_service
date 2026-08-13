<?php

namespace App\Listeners\Payment;

use App\Domain\Payment\Events\PaymentFailed;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Mail\Orders\PaymentFailedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendPaymentFailedEmail implements ShouldQueue
{
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

        foreach ($this->adminRecipients() as $recipient) {
            Mail::to($recipient, config('mail.admin.name'))->send(new PaymentFailedMail($order, $payment));
        }
    }

    /** @return list<string> */
    private function adminRecipients(): array
    {
        return collect(explode(',', (string) config('services.notifications.payment_failed_emails', '')))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
