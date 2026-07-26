<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $siteName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->siteName}] Test email",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.settings-test',
        );
    }
}
