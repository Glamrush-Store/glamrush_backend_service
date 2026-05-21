<?php

namespace App\Mail\Orders;

use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PaymentSuccessfulMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?Payment $payment,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment confirmed for {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.payment-successful',
        );
    }
}
