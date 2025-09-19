<?php

namespace App\Messaging\Mail;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CreateOrgOnboarding extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(protected User|MailingListEmail $recipient)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Create your first Ghostable organization',
            to: $this->recipient->email
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-mail',
            with: [
                'name' => $this->recipientName(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function recipientName(): string
    {
        if ($this->recipient instanceof User && filled($this->recipient->name)) {
            return $this->recipient->name;
        }

        return $this->recipient->email;
    }
}
