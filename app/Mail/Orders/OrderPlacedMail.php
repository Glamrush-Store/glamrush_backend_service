<?php

namespace App\Mail\Orders;

use App\Infrastructure\Persistence\Eloquent\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->order_number} received",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.placed',
        );
    }
}
