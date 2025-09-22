<?php

namespace App\Messaging\Mail\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Mail\UnsubscribableMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CliSetupNudgeMailable extends UnsubscribableMail
{
    public function __construct(
        protected User|MailingListEmail $recipient,
        protected bool $reminder = false
    ) {
        parent::__construct($recipient);
    }

    public function envelope(): Envelope
    {
        $subject = $this->reminder
            ? 'Still haven’t installed the Ghostable CLI? Finish setup in 60 seconds'
            : 'Set up the Ghostable CLI in minutes';

        return new Envelope(
            subject: $subject,
            to: $this->recipient->email
        );
    }

    protected function getContent(): Content
    {
        return new Content(
            view: 'mail.drip.cli-setup-nudge',
            with: [
                'name' => $this->recipientName(),
                'reminder' => $this->reminder,
            ],
        );
    }

    private function recipientName(): string
    {
        if ($this->recipient instanceof User && filled($this->recipient->name)) {
            return $this->recipient->name;
        }

        return $this->recipient->email;
    }
}
