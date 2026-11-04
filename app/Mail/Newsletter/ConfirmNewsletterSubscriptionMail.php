<?php

namespace App\Mail\Newsletter;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ConfirmNewsletterSubscriptionMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $confirmationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your newsletter subscription');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.newsletter.confirm-subscription');
    }
}
