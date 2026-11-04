<?php

namespace App\Mail\Contact;

use App\Infrastructure\Persistence\Eloquent\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ContactSubmissionReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactSubmission $submission)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Glamrush contact message · '.$this->submission->id);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact.submission-received');
    }
}
