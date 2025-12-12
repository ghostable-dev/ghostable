<?php

namespace App\Messaging\Mail\Transactional;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SalesNotificationMailable extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $details,
        public ?string $summary = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            to: 'sales@ghostable.dev'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.sales.notification',
            with: [
                'headline' => $this->headline,
                'summary' => $this->summary ?? 'New sales notification from Ghostable.',
                'details' => $this->details,
            ],
        );
    }
}
