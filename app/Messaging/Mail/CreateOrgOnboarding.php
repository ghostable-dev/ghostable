<?php

namespace App\Messaging\Mail;

use App\Account\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CreateOrgOnboarding extends UnsubscribableMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Create your first Ghostable organization',
            to: $this->recipient->email
        );
    }

    protected function getContent(): Content
    {
        return new Content(
            view: 'mail.drip.create-org-onboarding',
            with: [
                'name' => $this->recipientName(),
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
